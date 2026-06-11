<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'from_user_id',
        'to_user_id',
        'token',
        'expires_at',
        'retain_access',
        'billing_action',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'retain_access' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
