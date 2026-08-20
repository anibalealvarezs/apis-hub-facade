<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertCalculationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'label',
        'asset_filter',
        'sort_order',
    ];

    protected $casts = [
        'asset_filter' => 'array',
        'sort_order' => 'integer',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }
}
