<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUser extends Pivot
{
    protected $table = 'project_user';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $fillable = [
        'project_id',
        'user_id',
        'asset_access_unrestricted',
    ];

    protected $casts = [
        'asset_access_unrestricted' => 'boolean',
    ];
}
