<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringAlert extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'affected_service',
        'threshold_value',
        'actual_value',
        'status',
        'acknowledged_at',
        'resolved_at',
        'created_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isActionable(): bool
    {
        return $this->status === 'active';
    }
}
