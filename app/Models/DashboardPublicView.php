<?php

namespace App\Models;

use App\Services\PublicViewService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardPublicView extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'dashboard_id',
        'asset_group_id',
        'name',
        'token',
        'token_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function assetGroup(): BelongsTo
    {
        return $this->belongsTo(AssetGroup::class);
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
