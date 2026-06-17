<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate',
        'is_revised',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'is_revised' => 'boolean',
    ];
}
