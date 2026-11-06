<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model
{
    use HasFactory;

    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_SIMULATED = 'simulated';

    const TYPE_CONFIRMATION = 'confirmation';
    const TYPE_REMINDER = 'reminder';
    const TYPE_RESCHEDULED = 'rescheduled';
    const TYPE_CANCELLATION = 'cancellation';
    const TYPE_REJECTION = 'rejection';
    const TYPE_GENERAL = 'general';

    protected $fillable = [
        'appointment_id',
        'user_id',
        'recipient_phone',
        'notification_type',
        'message',
        'status',
        'gateway',
        'gateway_reference',
        'scheduled_at',
        'sent_at',
        'error_message',
        'payload_response',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT, self::STATUS_DELIVERED => 'bg-success',
            self::STATUS_SIMULATED => 'bg-info text-dark',
            self::STATUS_QUEUED => 'bg-warning text-dark',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
