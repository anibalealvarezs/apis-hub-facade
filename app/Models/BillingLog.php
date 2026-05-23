<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class BillingLog extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'user_id',
        'billing_profile_id',
        'project_id',
        'event_type',
        'gateway',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subYear());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function billingProfile()
    {
        return $this->belongsTo(BillingProfile::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
