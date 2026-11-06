<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = ['pop_id', 'paid_by', 'title', 'amount_cents', 'spent_on'];

    protected $casts = [
        'spent_on' => 'date',
        'amount_cents' => 'integer',
    ];

    public function shares(): HasMany
    {
        return $this->hasMany(ExpenseShare::class);
    }
}
