<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'billing_profile_id',
        'type',
        'status',
        'description',
        'external_ref',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function internalUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_internal_users');
    }

    public function internalProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'ticket_internal_projects');
    }

    public function internalBillingProfiles(): BelongsToMany
    {
        return $this->belongsToMany(BillingProfile::class, 'ticket_internal_billing_profiles');
    }

    public function scopeAccessibleBy(Builder $query, ?User $user = null): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('support_tickets.user_id', $user->id)
              ->orWhereHas('project', function (Builder $pq) use ($user) {
                  $pq->where('user_id', $user->id)
                     ->orWhereHas('users', fn (Builder $pu) => $pu->where('users.id', $user->id));
              })
              ->orWhereHas('billingProfile', fn (Builder $bq) => $bq->where('user_id', $user->id));
        });
    }

    public function isReplyAllowed(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // User must be the ticket creator or the subject
        if ($this->user_id !== $user->id) {
            return false;
        }

        // No association → creator can reply
        if (!$this->project_id && !$this->billing_profile_id) {
            return true;
        }

        // Project associated → user must be owner or editor
        if ($this->project_id) {
            if ($this->project->user_id === $user->id) {
                return true;
            }
            return $this->project->users()->where('users.id', $user->id)->exists();
        }

        // Billing profile associated → user must be owner
        if ($this->billing_profile_id) {
            return $this->billingProfile->user_id === $user->id;
        }

        return false;
    }
}
