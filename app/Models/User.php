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

class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

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
            return $this->is_admin && $this->is_active;
        }

        return $this->is_active;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'logout_at',
        'is_admin',
        'is_active',
    ];

    /**
     * SaaS Relationship: A user can belong to multiple project instances via the pivot table.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Relationship: Legacy owner link (Optional but kept for safety).
     */
    public function ownedProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
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
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Force Laravel's native Email Verification into the Job Queue.
     */
    public function sendEmailVerificationNotification()
    {
        // Encola correo de verificación para el cliente
        $this->notify(new \App\Notifications\QueuedVerifyEmail());

        // Enrola notificación al administrador por la misma vía probada, liberada del try-catch de silenciamiento
        $name = $this->name ?? 'Nuevo Registrado';
        $email = $this->email ?? 'Sin Correo';
        
        $admins = \App\Models\User::where('is_admin', true)->where('is_active', true)->get();

        foreach ($admins as $admin) {
            \Illuminate\Support\Facades\Mail::to($admin->email)
                ->queue(new \App\Mail\AdminRegistrationAlert($name, $email));
        }
    }

    /**
     * Force Laravel's native Password Reset into the Job Queue.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\QueuedResetPassword($token));
    }
}
