<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UptimeLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'status',
        'http_code',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'http_code' => 'integer',
        'response_time_ms' => 'float',
        'checked_at' => 'datetime',
    ];
}
