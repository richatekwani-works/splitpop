<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Pop;
use App\Models\Settlement;
use App\Support\Balances;
use App\Support\Look;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PopController extends Controller
{
    public function home()
    {
        return view('home');
    }

    // ---- create a pop --------------------------------------------------------------------

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'emoji' => ['nullable', Rule::in(Look::VIBES)],
            'currency' => ['required', Rule::in(Look::CURRENCIES)],
            'members' => 'required|array|max:20',
            'members.*' => 'nullable|string|max:40',
        ]);

        // trim, drop blanks, drop duplicate names (case-insensitive)
        $names = collect($data['members'])
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->values();

        if ($names->count() < 2) {
            return back()
                ->withErrors(['members' => 'A pop needs at least 2 different friends to split with 👯'])
                ->withInput();
        }

        $pop = DB::transaction(function () use ($data, $names) {
            $pop = Pop::create([
                'slug' => Pop::newSlug(),
                'name' => trim($data['name']),
                'emoji' => $data['emoji'] ?? '🍕',
                'currency' => $data['currency'],
            ]);
            $pop->members()->createMany($names->map(fn ($n) => ['name' => $n])->all());

            return $pop;
        });

        return redirect()
            ->route('pops.show', $pop)
            ->with('ok', 'Your pop is live! Copy the link and send it to the gang 🎈');
    }

    // ---- the pop page --------------------------------------------------------------------

    public function show(Pop $pop)
    {
        $members = $pop->members()->get();
        $expenses = $pop->expenses()->with('shares')->orderByDesc('spent_on')->orderByDesc('id')->get();
        $settlements = $pop->settlements()->orderByDesc('id')->get();

        $result = Balances::compute(
            $members->pluck('id')->all(),
            $expenses->map(fn ($e) => [
                'paid_by' => (int) $e->paid_by,
                'amount' => (int) $e->amount_cents,
                'shares' => $e->shares->pluck('share_cents', 'member_id')->map(fn ($c) => (int) $c)->all(),
            ])->all(),
            $settlements->map(fn ($s) => [
                'from' => (int) $s->from_member_id,
                'to' => (int) $s->to_member_id,
                'amount' => (int) $s->amount_cents,
            ])->all(),
        );

        return view('pops.show', [
            'pop' => $pop,
            'members' => $members,
            'byId' => $members->keyBy('id'),
            'memberIds' => $members->pluck('id')->all(),
            'looks' => Look::for($members),
            'expenses' => $expenses,
            'settlements' => $settlements,
            'net' => $result['net'],
            'transfers' => $result['transfers'],
            'total' => (int) $expenses->sum('amount_cents'),
        ]);
    }

    // ---- friends -------------------------------------------------------------------------

    public function addMember(Request $request, Pop $pop)
    {
        $data = $request->validate(['name' => 'required|string|max:40']);
        $name = trim($data['name']);

        if ($pop->members()->count() >= 20) {
            return back()->withErrors(['name' => 'This pop is full (20 friends max) 🎈']);
        }

        if ($pop->members()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists()) {
            return back()->withErrors(['name' => "$name is already in this pop!"]);
        }

        $pop->members()->create(['name' => $name]);

        return redirect()->route('pops.show', $pop)->with('ok', "$name joined the pop 🎉");
    }

    // ---- bills ---------------------------------------------------------------------------

    public function addExpense(Request $request, Pop $pop)
    {
        $inPop = Rule::exists('members', 'id')->where('pop_id', $pop->id);

        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'paid_by' => ['required', 'integer', $inPop],
            'spent_on' => 'nullable|date',
            'split_mode' => 'required|in:equal,custom',
            'split_with' => 'required_if:split_mode,equal|nullable|array',
            'split_with.*' => ['integer', $inPop],
            'custom' => 'required_if:split_mode,custom|nullable|array',
            'custom.*' => 'nullable|numeric|min:0',
        ]);

        $total = Money::toCents($data['amount']);

        if ($data['split_mode'] === 'equal') {
            $ids = array_values(array_unique(array_map('intval', $data['split_with'] ?? [])));
            if (! $ids) {
                return back()->withErrors(['split_with' => 'Pick at least one person to split with 🍰'])->withInput();
            }
            $shares = Money::splitEqual($total, $ids);
        } else {
            $memberIds = $pop->members()->pluck('id')->all();
            $shares = [];
            foreach (($data['custom'] ?? []) as $memberId => $value) {
                $cents = Money::toCents($value);
                if ($cents > 0 && in_array((int) $memberId, $memberIds, true)) {
                    $shares[(int) $memberId] = $cents;
                }
            }
            if (! $shares) {
                return back()->withErrors(['custom' => 'Type an amount next to at least one person 🎯'])->withInput();
            }
            if (($sum = array_sum($shares)) !== $total) {
                return back()->withErrors([
                    'custom' => 'The custom amounts add up to ' . Money::fmt($sum, $pop->currency)
                        . ' but the bill is ' . Money::fmt($total, $pop->currency) . '. Close the gap! 🧮',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($pop, $data, $total, $shares) {
            $expense = $pop->expenses()->create([
                'paid_by' => (int) $data['paid_by'],
                'title' => trim($data['title'] ?? '') ?: 'Mystery spend',
                'amount_cents' => $total,
                'spent_on' => $data['spent_on'] ?? now()->toDateString(),
            ]);

            foreach ($shares as $memberId => $cents) {
                $expense->shares()->create(['member_id' => $memberId, 'share_cents' => $cents]);
            }
        });

        return redirect()->route('pops.show', $pop)->with('ok', 'Bill added. Receipts +1 🧾');
    }

    public function deleteExpense(Pop $pop, Expense $expense)
    {
        abort_unless($expense->pop_id === $pop->id, 404);
        $expense->delete();

        return redirect()->route('pops.show', $pop)->with('ok', 'Bill gone. Poof 💨');
    }

    // ---- "X paid Y back" -----------------------------------------------------------------

    public function settle(Request $request, Pop $pop)
    {
        $inPop = Rule::exists('members', 'id')->where('pop_id', $pop->id);

        $data = $request->validate([
            'from_member_id' => ['required', 'integer', $inPop],
            'to_member_id' => ['required', 'integer', 'different:from_member_id', $inPop],
            'amount_cents' => 'required|integer|min:1|max:1000000000',
        ]);

        $pop->settlements()->create($data);

        return redirect()->route('pops.show', $pop)->with('ok', 'Debt cleared! Friendship saved 🤝');
    }

    public function unsettle(Pop $pop, Settlement $settlement)
    {
        abort_unless($settlement->pop_id === $pop->id, 404);
        $settlement->delete();

        return redirect()->route('pops.show', $pop)->with('ok', 'Payment undone ↩️');
    }
}
