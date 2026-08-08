<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(AssetGroupItem::class);
    }

    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'project_user_asset_groups', 'asset_group_id', 'user_id');
    }

    public function getActiveItemsAttribute()
    {
        $project = $this->project;
        if (!$project) {
            return collect();
        }

        $syncConfig = $project->sync_config ?? [];
        $items = $this->items;

        return $items->filter(function ($item) use ($syncConfig) {
            $channelConfig = $syncConfig[$item->channel] ?? [];
            if (!is_array($channelConfig)) {
                return false;
            }

            $isActive = false;
            
            $scan = function ($data) use (&$scan, &$isActive, $item) {
                if ($isActive || !is_array($data)) {
                    return;
                }

                if (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('platformId', $data)) {
                    $id = $data['id'] ?? $data['url'] ?? $data['platformId'] ?? null;
                    if ($id !== null && (string)$id === (string)$item->asset_id) {
                        if (!empty($data['enabled']) && empty($data['lost_access'])) {
                            $isActive = true;
                        }
                        return;
                    }
                }

                foreach ($data as $val) {
                    if (is_array($val)) {
                        $scan($val);
                    }
                }
            };

            $scan($channelConfig);

            return $isActive;
        });
    }
}
