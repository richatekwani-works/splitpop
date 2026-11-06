<?php

namespace App\Http\Controllers;

use App\Models\Pop;
use App\Support\Balances;
use App\Support\Look;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PopController extends Controller
{
    public function home()
    {
        return view('home');
    }

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

    public function show(Pop $pop)
    {
        $members = $pop->members()->get();
        $byId = $members->keyBy('id');
        $expenses = $pop->expenses()
            ->with('shares')
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->get();
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
            'byId' => $byId,
            'memberIds' => $members->pluck('id')->all(),
            'looks' => Look::for($members),
            'expenses' => $expenses,
            'settlements' => $settlements,
            'net' => $result['net'],
            'transfers' => $result['transfers'],
            'total' => (int) $expenses->sum('amount_cents'),
        ]);
    }
}
