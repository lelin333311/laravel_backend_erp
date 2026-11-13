<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $fillable = [
        'party_code', 'name', 'type', 'phone', 'email',
        'address', 'opening_balance', 'balance_type', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];
}