<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Pop;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function store(Request $request, Pop $pop)
    {
        $inPop = Rule::exists('members', 'id')->where('pop_id', $pop->id);

        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'paid_by' => ['required', 'integer', $inPop],
            'spent_on' => 'nullable|date',
            // The bill photo is optional (an amount alone is fine). 3 MB keeps us under Vercel's 4.5 MB request limit.
            'bill' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:3072',
            'split_mode' => 'required|in:equal,custom',
            'split_with' => 'required_if:split_mode,equal|nullable|array',
            'split_with.*' => ['integer', $inPop],
            'custom' => 'required_if:split_mode,custom|nullable|array',
            'custom.*' => 'nullable|numeric|min:0',
        ]);

        $total = Money::toCents($data['amount']);
        $symbol = $pop->currency;

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
                $memberId = (int) $memberId;
                $cents = Money::toCents($value);
                if ($cents > 0 && in_array($memberId, $memberIds, true)) {
                    $shares[$memberId] = $cents;
                }
            }
            if (! $shares) {
                return back()->withErrors(['custom' => 'Type an amount next to at least one person 🎯'])->withInput();
            }
            $sum = array_sum($shares);
            if ($sum !== $total) {
                return back()->withErrors([
                    'custom' => 'The custom amounts add up to ' . Money::fmt($sum, $symbol)
                        . ' but the bill is ' . Money::fmt($total, $symbol) . '. Close the gap! 🧮',
                ])->withInput();
            }
        }

        // Keep the photo as base64 text inside the database row.
        $billData = null;
        $billMime = null;
        if ($file = $request->file('bill')) {
            $mime = $file->getMimeType();
            if (in_array($mime, self::IMAGE_TYPES, true)) {
                $billData = base64_encode(file_get_contents($file->getRealPath()));
                $billMime = $mime;
            }
        }

        DB::transaction(function () use ($pop, $data, $total, $shares, $billData, $billMime) {
            $expense = $pop->expenses()->create([
                'paid_by' => (int) $data['paid_by'],
                'title' => trim($data['title'] ?? '') ?: 'Mystery spend',
                'amount_cents' => $total,
                'bill_data' => $billData,
                'bill_mime' => $billMime,
                'spent_on' => $data['spent_on'] ?? now()->toDateString(),
            ]);

            foreach ($shares as $memberId => $cents) {
                $expense->shares()->create(['member_id' => $memberId, 'share_cents' => $cents]);
            }
        });

        return redirect()->route('pops.show', $pop)->with('ok', 'Bill added. Receipts +1 🧾');
    }

    // Serves the stored bill photo.
    public function bill(Pop $pop, Expense $expense)
    {
        abort_unless($expense->pop_id === $pop->id && $expense->bill_data, 404);

        return response(base64_decode($expense->bill_data), 200, [
            'Content-Type' => $expense->bill_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function destroy(Pop $pop, Expense $expense)
    {
        abort_unless($expense->pop_id === $pop->id, 404);
        $expense->delete();

        return redirect()->route('pops.show', $pop)->with('ok', 'Bill gone. Poof 💨');
    }
}
