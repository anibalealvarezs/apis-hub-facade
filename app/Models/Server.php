<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_private_key',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ssh_private_key' => 'encrypted',
        'ssh_port' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the projects associated with this server.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
