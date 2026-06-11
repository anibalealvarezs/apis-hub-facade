<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelProfile extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_account_id',
        'name',
        'email',
        'access_token',
        'refresh_token',
        'expires_at',
        'authorized_channels',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'authorized_channels' => 'array',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
