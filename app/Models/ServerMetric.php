<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cpu_usage',
        'ram_usage',
        'ram_used_bytes',
        'ram_total_bytes',
        'disk_usage',
        'disk_used_bytes',
        'disk_total_bytes',
        'db_response_time_ms',
        'db_connections',
        'status',
        'created_at',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'ram_usage' => 'float',
        'ram_used_bytes' => 'integer',
        'ram_total_bytes' => 'integer',
        'disk_usage' => 'float',
        'disk_used_bytes' => 'integer',
        'disk_total_bytes' => 'integer',
        'db_response_time_ms' => 'float',
        'db_connections' => 'integer',
        'created_at' => 'datetime',
    ];
}
