<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Dentist extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'user_id',
        'name',
        'specialization',
        'license_number',
        'phone',
        'email',
        'slot_duration_minutes',
        'status',
        'bio',
        'deletion_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function availabilities()
    {
        return $this->hasMany(DentistAvailability::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if dentist has general availability configured for the day of week of $date
     */
    public function getAvailabilityForDate($date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0=Sunday, 1=Monday, etc.
        return $this->availabilities()
            ->where('status', 'active')
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    /**
     * Check if a specific date and time slot is available (not conflicted with confirmed appointments)
     */
    public function isSlotAvailable($date, $time, $excludeAppointmentId = null): bool
    {
        $availability = $this->getAvailabilityForDate($date);
        if (!$availability) {
            return false;
        }

        $slotTime = Carbon::parse($time)->format('H:i:s');
        $startTime = Carbon::parse($availability->start_time)->format('H:i:s');
        $endTime = Carbon::parse($availability->end_time)->format('H:i:s');

        if ($slotTime < $startTime || $slotTime >= $endTime) {
            return false;
        }

        // Check if there is already a confirmed appointment at this date & time
        $query = $this->appointments()
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->where('confirmed_date', $date)
            ->where('confirmed_time', $slotTime);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return !$query->exists();
    }
}
