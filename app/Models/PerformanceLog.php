<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'url',
        'fcp',
        'lcp',
        'inp',
        'cls',
        'ttfb',
        'dom_loaded',
        'fully_loaded',
        'response_time_ms',
        'classification',
        'created_at',
    ];

    protected $casts = [
        'fcp' => 'float',
        'lcp' => 'float',
        'inp' => 'float',
        'cls' => 'float',
        'ttfb' => 'float',
        'dom_loaded' => 'float',
        'fully_loaded' => 'float',
        'response_time_ms' => 'float',
        'created_at' => 'datetime',
    ];
}
