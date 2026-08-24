<?php

namespace App\Models;

use App\Services\PublicViewService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class DashboardPublicView extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'dashboard_id',
        'asset_group_ids',
        'name',
        'token',
        'token_secret',
        'is_active',
        'allow_pdf_export',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_pdf_export' => 'boolean',
        'asset_group_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (DashboardPublicView $pv) {
            if (empty($pv->token_secret)) {
                $pv->token_secret = bin2hex(random_bytes(32));
            }
            if (empty($pv->token)) {
                // Temporary dummy token to pass saving, will set real token after saving or during creation
                $pv->token = 'pending_' . bin2hex(random_bytes(16));
            }
        });

        static::created(function (DashboardPublicView $pv) {
            if (str_starts_with($pv->token, 'pending_')) {
                $pv->token = app(PublicViewService::class)->generateToken($pv);
                $pv->saveQuietly();
            }
        });
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Get the AssetGroup models referenced by asset_group_ids.
     */
    public function assetGroups(): Collection
    {
        $ids = $this->asset_group_ids ?? [];
        $dashAllowed = (array) ($this->dashboard?->controls['asset_group'] ?? []);
        $dashAllowed = array_values(array_filter(array_map('strval', $dashAllowed)));

        if (!empty($ids)) {
            $ids = array_map('strval', $ids);
            if (!empty($dashAllowed)) {
                $ids = array_values(array_intersect($ids, $dashAllowed));
            }
            if (empty($ids)) {
                return collect();
            }
            return AssetGroup::whereIn('id', $ids)->get();
        }

        // If public view has no specific asset groups selected, it inherits all dashboard-allowed groups
        if (!empty($dashAllowed)) {
            return AssetGroup::whereIn('id', $dashAllowed)->get();
        }

        return AssetGroup::where('project_id', $this->dashboard?->project_id)->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPublicUrl(): string
    {
        return route('public-view.show', ['token' => $this->token]);
    }

    public function getEmbedUrl(): string
    {
        return route('public-view.embed', ['token' => $this->token]);
    }

    public function regenerateToken(): void
    {
        app(PublicViewService::class)->regenerateToken($this);
    }
}
