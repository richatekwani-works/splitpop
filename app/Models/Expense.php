<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = ['pop_id', 'paid_by', 'title', 'amount_cents', 'bill_path', 'spent_on'];

    protected $casts = [
        'spent_on' => 'date',
        'amount_cents' => 'integer',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'paid_by');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ExpenseShare::class);
    }

    // asset() follows whatever host/port you're browsing on, so images work on any dev URL.
    // Needs `php artisan storage:link` once.
    public function billUrl(): ?string
    {
        return $this->bill_path ? asset('storage/' . $this->bill_path) : null;
    }
}
