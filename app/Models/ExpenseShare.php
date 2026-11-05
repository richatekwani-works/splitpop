<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseShare extends Model
{
    protected $fillable = ['expense_id', 'member_id', 'share_cents'];

    protected $casts = ['share_cents' => 'integer'];
}
