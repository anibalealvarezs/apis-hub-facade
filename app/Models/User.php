<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use App\Enums\UserTier;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;

class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use HasPanelShield;

    /**
     * Filament Multi-tenancy: Return the projects owned by this user.
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->projects;
    }

    /**
     * Filament Multi-tenancy: Verify if the user owns this specific instance.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->projects->contains($tenant);
    }

    /**
     * Filament Panel Access Logic
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return (bool) ($this->hasRole('super_admin') && $this->is_active);
        }

        return (bool) ($this->is_active ?? true);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'logout_at',
        'is_active',
        'locale',
        'pending_email',
    ];

    /**
     * SaaS Relationship: A user can belong to multiple project instances via the pivot table.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class)->using(ProjectUser::class);
    }

    /**
     * Relationship: Support tickets where this user is tagged internally (admin-only associations).
     */
    public function supportTicketInternalAssociations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SupportTicket::class, 'ticket_internal_users');
    }

    /**
     * Relationship: Legacy owner link (Optional but kept for safety).
     */
    public function ownedProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /**
     * Relationship: A user can have multiple billing profiles.
     */
    public function billingProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BillingProfile::class);
    }

    /**
     * Relationship: Billing profiles shared with this user by other users.
     */
    public function sharedBillingProfiles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(BillingProfile::class, 'billing_profile_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Helper: Get all billing profiles the user has access to (owned + shared).
     */
    public function getAvailableBillingProfiles()
    {
        return $this->billingProfiles->merge($this->sharedBillingProfiles);
    }

    /**
     * Relationship: A user can have multiple channel profiles connected (e.g. Google, Meta).
     */
    public function channelProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChannelProfile::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'logout_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if the user's default billing profile can create more projects.
     */
    public function canCreateMoreProjects(): bool
    {
        $defaultProfile = $this->billingProfiles()->where('is_default', true)->first() 
            ?? $this->billingProfiles()->first();

        if (!$defaultProfile) {
            return false;
        }

        // Count projects currently assigned to this profile
        $projectCount = $defaultProfile->projects()->count();

        // Get max projects allowed for the profile's tier
        $maxProjects = app(\App\Services\BillingLifecycleService::class)
            ->getMaxProjectsForTier($defaultProfile->tier);

        return $projectCount < $maxProjects;
    }

    /**
     * Check if the user only has FREE billing profiles (no paid profiles).
     */
    public function hasOnlyFreeProfiles(): bool
    {
        $profiles = $this->billingProfiles;
        if ($profiles->isEmpty()) {
            return true;
        }
        return $profiles->every(fn ($profile) => $profile->tier === \App\Enums\UserTier::FREE);
    }

    /**
     * Count the total number of projects the user has active access to (owned or collaborated).
     */
    public function getTotalAccessibleProjectsCount(): int
    {
        return $this->projects()->count();
    }

    /**
     * Force Laravel's native Email Verification into the Job Queue.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\QueuedVerifyEmail());
    }

    /**
     * Force Laravel's native Password Reset into the Job Queue.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\QueuedResetPassword($token));
    }
}
