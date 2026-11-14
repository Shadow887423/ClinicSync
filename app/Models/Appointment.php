<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RESCHEDULE_REQUESTED = 'reschedule_requested';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'appointment_number',
        'patient_id',
        'dentist_id',
        'service_type',
        'requested_date',
        'requested_time',
        'confirmed_date',
        'confirmed_time',
        'proposed_date',
        'proposed_time',
        'status',
        'patient_notes',
        'staff_notes',
        'rejection_reason',
        'cancellation_reason',
        'reschedule_reason',
        'deletion_reason',
        'reminder_sent_at',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'dentist_id' => 'integer',
        'requested_date' => 'date',
        'confirmed_date' => 'date',
        'proposed_date' => 'date',
        'reminder_sent_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    public function smsNotifications()
    {
        return $this->hasMany(SmsNotification::class)->latest();
    }

    public function activityLogs()
    {
        return $this->hasMany(AppointmentActivityLog::class)->latest();
    }

    public function treatmentRecord()
    {
        return $this->hasOne(TreatmentRecord::class);
    }

    /**
     * Generate Google Calendar add URL
     */
    public function getGoogleCalendarUrlAttribute(): string
    {
        $title = urlencode("Dental Appointment: {$this->service_type} - CLINICSYNC");
        $dentistName = $this->dentist ? $this->dentist->name : 'Clinic Staff Dentist';
        $details = urlencode("Appointment Ref: {$this->appointment_number}\nDentist: {$dentistName}\nService: {$this->service_type}\nLocation: CLINICSYNC Dental Healthcare Clinic");
        $location = urlencode("CLINICSYNC Dental Care Clinic, Medical Arts Building");

        $dateStr = $this->effective_date->format('Ymd');
        $timeStr = \Carbon\Carbon::parse($this->effective_time)->format('His');
        $startIso = "{$dateStr}T{$timeStr}";
        $endIso = \Carbon\Carbon::parse("{$this->effective_date->format('Y-m-d')} {$this->effective_time}")
            ->addMinutes($this->dentist?->slot_duration_minutes ?? 30)
            ->format('Ymd\THis');

        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$startIso}/{$endIso}&details={$details}&location={$location}";
    }

    public function logActivity($action, $prevStatus = null, $newStatus = null, $details = null, $userId = null)
    {
        return $this->activityLogs()->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'previous_status' => $prevStatus,
            'new_status' => $newStatus,
            'details' => $details,
        ]);
    }

    public function getEffectiveDateAttribute()
    {
        return $this->confirmed_date ?? $this->requested_date;
    }

    public function getEffectiveTimeAttribute()
    {
        return $this->confirmed_time ?? $this->requested_time;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_RESCHEDULE_REQUESTED
        ]);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED
        ]);
    }

    public function canBeConfirmed(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_RESCHEDULE_REQUESTED
        ]);
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_CONFIRMED => 'bg-success',
            self::STATUS_REJECTED => 'bg-danger',
            self::STATUS_RESCHEDULE_REQUESTED => 'bg-info text-dark',
            self::STATUS_COMPLETED => 'bg-primary',
            self::STATUS_CANCELLED => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
}
