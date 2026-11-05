<?php

namespace App\Http\Controllers;

use App\Models\Pop;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function store(Request $request, Pop $pop)
    {
        $data = $request->validate(['name' => 'required|string|max:40']);
        $name = trim($data['name']);

        if ($pop->members()->count() >= 20) {
            return back()->withErrors(['name' => 'This pop is full (20 friends max) 🎈']);
        }

        $exists = $pop->members()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return back()->withErrors(['name' => "$name is already in this pop!"]);
        }

        $pop->members()->create(['name' => $name]);

        return redirect()->route('pops.show', $pop)->with('ok', "$name joined the pop 🎉");
    }
}
