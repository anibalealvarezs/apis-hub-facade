<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_profile_id',
        'email',
        'role',
        'token',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }
}
