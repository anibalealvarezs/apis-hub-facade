<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class BillingProfile extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'user_id',
        'is_default',
        'type',
        'tier',
        'status',
        'name',
        'reference_name',
        'tax_id',
        'address_line_1',
        'city',
        'state',
        'postal_code',
        'country_code',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'paypal_id',
        'paypal_email',
        'health_status',
        'current_cycle_starts_at',
        'current_cycle_ends_at',
    ];

    /**
     * Get displayable name for the billing profile (reference name falls back to company/person name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->reference_name ?: $this->name;
    }

    protected $casts = [
        'is_default' => 'boolean',
        'trial_ends_at' => 'datetime',
        'current_cycle_starts_at' => 'datetime',
        'current_cycle_ends_at' => 'datetime',
        'tier' => \App\Enums\UserTier::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (BillingProfile $profile) {
            if ($profile->tier === \App\Enums\UserTier::FREE) {
                $hasFree = static::where('user_id', $profile->user_id)
                    ->where('tier', \App\Enums\UserTier::FREE)
                    ->exists();
                if ($hasFree) {
                    throw new \InvalidArgumentException('Ya tienes un perfil de facturación de plan gratuito ("free"). Por favor, sube de nivel tu perfil actual.');
                }
            }
        });

        static::updating(function (BillingProfile $profile) {
            if ($profile->isDirty('tier') && $profile->getOriginal('tier') === \App\Enums\UserTier::ENTERPRISE) {
                throw new \InvalidArgumentException('Enterprise users cannot be downgraded to protect system-level tracking.');
            }

            if ($profile->isDirty('tier') && $profile->tier === \App\Enums\UserTier::FREE) {
                $hasOtherFree = static::where('user_id', $profile->user_id)
                    ->where('id', '!=', $profile->id)
                    ->where('tier', \App\Enums\UserTier::FREE)
                    ->exists();
                if ($hasOtherFree) {
                    $profile->tier = \App\Enums\UserTier::SUSPENDED;
                    $profile->status = 'suspended';
                }
            }
        });

        static::updated(function (BillingProfile $profile) {
            if ($profile->wasChanged('tier')) {
                app(\App\Services\BillingLifecycleService::class)->enforceTierLimits($profile, $profile->tier);
            }
        });

        static::deleting(function (BillingProfile $profile) {
            $projects = $profile->projects;
            foreach ($projects as $project) {
                $project->update([
                    'billing_status' => 'suspended',
                    'is_active' => false,
                ]);
                
                \App\Jobs\SuspendProjectDomainJob::dispatch($project);
            }
        });
    }

    /**
     * Get the user that owns the billing profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users this profile is shared with.
     */
    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'billing_profile_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the projects this profile is paying for.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the invoices associated with this billing profile.
     */
    public function unifiedInvoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Override Cashier's default customer name.
     */
    public function stripeName()
    {
        return $this->name;
    }
}
