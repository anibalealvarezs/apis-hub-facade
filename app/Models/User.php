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
            return $this->hasRole('super_admin') && $this->is_active;
        }

        return $this->is_active;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'logout_at',
        'is_active',
        'locale',
        'pending_email',
        'tier',
    ];

    /**
     * SaaS Relationship: A user can belong to multiple project instances via the pivot table.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class)->using(ProjectUser::class);
    }

    /**
     * Relationship: Legacy owner link (Optional but kept for safety).
     */
    public function ownedProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * Relationship: A user can have multiple billing profiles.
     */
    public function billingProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BillingProfile::class);
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
            'tier' => UserTier::class,
        ];
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

    /**
     * Check if the user has reached their project creation limit based on their Tier.
     * (Future implementation will use Cashier).
     */
    public function canCreateMoreProjects(): bool
    {
        $currentProjects = $this->projects()->count();

        return match ($this->tier) {
            UserTier::FREE => $currentProjects < 1,
            UserTier::PRO => $currentProjects < 5,
            UserTier::ENTERPRISE => true,
        };
    }
}
