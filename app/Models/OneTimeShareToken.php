<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneTimeShareToken extends Model
{
    protected $fillable = [
        'project_id',
        'token',
        'email',
        'created_by',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
