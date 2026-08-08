<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetGroupItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_group_id',
        'channel',
        'asset_id',
    ];

    public function group()
    {
        return $this->belongsTo(AssetGroup::class, 'asset_group_id');
    }
}
