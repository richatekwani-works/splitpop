<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = ['pop_id', 'from_member_id', 'to_member_id', 'amount_cents'];

    protected $casts = ['amount_cents' => 'integer'];
}
