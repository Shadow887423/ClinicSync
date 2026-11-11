<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'session_hash',
        'url',
        'route_name',
        'method',
        'status_code',
        'response_time_ms',
        'inbound_bytes',
        'outbound_bytes',
        'device_type',
        'browser',
        'os',
        'country_code',
        'country_name',
        'referer',
        'is_error',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'float',
        'inbound_bytes' => 'integer',
        'outbound_bytes' => 'integer',
        'is_error' => 'boolean',
        'created_at' => 'datetime',
    ];
}
