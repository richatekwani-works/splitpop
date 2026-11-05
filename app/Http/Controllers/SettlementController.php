<?php

namespace App\Http\Controllers;

use App\Models\Pop;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettlementController extends Controller
{
    public function store(Request $request, Pop $pop)
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

    public function destroy(Pop $pop, Settlement $settlement)
    {
        abort_unless($settlement->pop_id === $pop->id, 404);
        $settlement->delete();

        return redirect()->route('pops.show', $pop)->with('ok', 'Payment undone ↩️');
    }
}
