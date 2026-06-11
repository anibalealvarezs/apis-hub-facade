<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'stripe_promotion_code_id',
        'trial_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_days' => 'integer',
    ];
}
