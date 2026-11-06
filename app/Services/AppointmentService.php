<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistAvailability;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    protected SmsGatewayService $smsService;

    public function __construct(SmsGatewayService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Find appointment and apply row lock (for MySQL/Postgres; graceful fallback on SQLite)
     */
    protected function findAndLockAppointment(int $id): Appointment
    {
        $query = Appointment::where('id', $id);
        if (DB::getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }
        return $query->firstOrFail();
    }

    /**
     * Generate unique human-readable appointment number (APT-YYYYMMDD-XXXX)
     */
    public function generateAppointmentNumber(): string
    {
        $prefix = 'APT-' . date('Ymd') . '-';
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
        
        while (Appointment::where('appointment_number', $prefix . $random)->exists()) {
            $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
        }

        return $prefix . $random;
    }

    /**
     * Calculate available appointment time slots for a specific dentist on a specific date
     */
    public function getAvailableSlotsForDentist(int $dentistId, string $dateString): array
    {
        $date = Carbon::parse($dateString);
        
        // Cannot book appointments in the past
        if ($date->isPast() && !$date->isToday()) {
            return [];
        }

        $dentist = Dentist::find($dentistId);
        if (!$dentist || $dentist->status !== 'active') {
            return [];
        }

        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 1=Monday, etc.
        $availability = DentistAvailability::where('dentist_id', $dentistId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->first();

        if (!$availability) {
            return [];
        }

        $slotDuration = $availability->slot_duration_minutes ?: 30;
        $startTime = Carbon::parse($dateString . ' ' . $availability->start_time);
        $endTime = Carbon::parse($dateString . ' ' . $availability->end_time);

        // Fetch already booked and confirmed slots for this dentist and date
        $bookedTimes = Appointment::where('dentist_id', $dentistId)
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->where(function ($query) use ($dateString) {
                $query->whereDate('confirmed_date', $dateString)
                    ->orWhere(function ($q) use ($dateString) {
                        $q->whereNull('confirmed_date')
                          ->whereDate('requested_date', $dateString);
                    });
            })
            ->pluck('confirmed_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i:s');
            })
            ->toArray();

        $slots = [];
        $currentTime = $startTime->copy();

        while ($currentTime->copy()->addMinutes($slotDuration)->lte($endTime)) {
            $formattedTime = $currentTime->format('H:i:s');
            $displayTime = $currentTime->format('g:i A');

            // If today, filter out past hours
            $isPastTime = false;
            if ($date->isToday() && $currentTime->lte(now())) {
                $isPastTime = true;
            }

            $isBooked = in_array($formattedTime, $bookedTimes);

            $slots[] = [
                'time' => $formattedTime,
                'display' => $displayTime,
                'is_available' => !$isBooked && !$isPastTime,
                'is_booked' => $isBooked,
                'is_past' => $isPastTime,
            ];

            $currentTime->addMinutes($slotDuration);
        }

        return $slots;
    }

    /**
     * Submit an appointment request (Status: Pending)
     */
    public function requestAppointment(User $patient, array $data): Appointment
    {
        $requestedDate = Carbon::parse($data['requested_date'])->format('Y-m-d');
        $requestedTime = Carbon::parse($data['requested_time'])->format('H:i:s');

        // Server-side past date/time validation
        $appointmentDateTime = Carbon::parse("{$requestedDate} {$requestedTime}");
        if ($appointmentDateTime->isPast()) {
            throw ValidationException::withMessages([
                'requested_date' => 'Appointments cannot be booked in the past.',
            ]);
        }

        $dentist = null;
        if (!empty($data['dentist_id'])) {
            $dentist = Dentist::findOrFail($data['dentist_id']);
            
            // Check availability
            $dayOfWeek = Carbon::parse($requestedDate)->dayOfWeek;
            $availability = DentistAvailability::where('dentist_id', $dentist->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('status', 'active')
                ->first();

            if (!$availability) {
                throw ValidationException::withMessages([
                    'requested_date' => "The selected dentist is not available on {$requestedDate}.",
                ]);
            }
        }

        return DB::transaction(function () use ($patient, $dentist, $data, $requestedDate, $requestedTime) {
            $appointment = Appointment::create([
                'appointment_number' => $this->generateAppointmentNumber(),
                'patient_id' => $patient->id,
                'dentist_id' => $dentist ? $dentist->id : null,
                'service_type' => $data['service_type'],
                'requested_date' => $requestedDate,
                'requested_time' => $requestedTime,
                'status' => Appointment::STATUS_PENDING,
                'patient_notes' => $data['patient_notes'] ?? null,
            ]);

            $appointment->logActivity(
                'created',
                null,
                Appointment::STATUS_PENDING,
                'Patient submitted appointment request for ' . Carbon::parse($requestedDate)->format('M d, Y') . ' at ' . Carbon::parse($requestedTime)->format('g:i A'),
                $patient->id
            );

            return $appointment;
        });
    }

    /**
     * Confirm an appointment with concurrency check to prevent overlapping bookings
     */
    public function confirmAppointment(Appointment $appointment, array $data, User $staffUser): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $staffUser) {
            $lockedAppointment = $this->findAndLockAppointment($appointment->id);

            $dentistId = $data['dentist_id'] ?? $lockedAppointment->dentist_id;
            if (!$dentistId) {
                throw ValidationException::withMessages([
                    'dentist_id' => 'A dentist must be assigned before confirming an appointment.',
                ]);
            }

            $confirmedDate = $data['confirmed_date'] ?? $lockedAppointment->requested_date->format('Y-m-d');
            $confirmedTime = Carbon::parse($data['confirmed_time'] ?? $lockedAppointment->requested_time)->format('H:i:s');

            // Concurrency protection: Check if another appointment is already confirmed for this dentist and slot
            $conflictQuery = Appointment::where('dentist_id', $dentistId)
                ->where('id', '!=', $lockedAppointment->id)
                ->where('status', Appointment::STATUS_CONFIRMED)
                ->whereDate('confirmed_date', $confirmedDate)
                ->where('confirmed_time', $confirmedTime);

            if (DB::getDriverName() !== 'sqlite') {
                $conflictQuery->lockForUpdate();
            }

            if ($conflictQuery->exists()) {
                throw ValidationException::withMessages([
                    'confirmed_time' => 'Conflict detected: Another confirmed appointment already exists for this dentist at the chosen date and time.',
                ]);
            }

            $prevStatus = $lockedAppointment->status;

            $lockedAppointment->update([
                'dentist_id' => $dentistId,
                'confirmed_date' => $confirmedDate,
                'confirmed_time' => $confirmedTime,
                'status' => Appointment::STATUS_CONFIRMED,
                'staff_notes' => $data['staff_notes'] ?? $lockedAppointment->staff_notes,
            ]);

            $dentist = Dentist::find($dentistId);
            $dentistName = $dentist ? $dentist->name : 'Staff Dentist';

            $lockedAppointment->logActivity(
                'confirmed',
                $prevStatus,
                Appointment::STATUS_CONFIRMED,
                "Confirmed for {$confirmedDate} at " . Carbon::parse($confirmedTime)->format('g:i A') . " with {$dentistName}.",
                $staffUser->id
            );

            // Automatically send SMS confirmation to patient
            $this->smsService->sendConfirmation($lockedAppointment);

            return $lockedAppointment;
        });
    }

    /**
     * Reject an appointment request
     */
    public function rejectAppointment(Appointment $appointment, string $reason, User $staffUser): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $staffUser) {
            $locked = $this->findAndLockAppointment($appointment->id);
            $prevStatus = $locked->status;

            $locked->update([
                'status' => Appointment::STATUS_REJECTED,
                'rejection_reason' => $reason,
            ]);

            $locked->logActivity(
                'rejected',
                $prevStatus,
                Appointment::STATUS_REJECTED,
                "Appointment rejected. Reason: {$reason}",
                $staffUser->id
            );

            // Send notification to patient
            $this->smsService->sendRejectionNotice($locked, $reason);

            return $locked;
        });
    }

    /**
     * Staff modifies or proposes a new schedule for an appointment
     */
    public function modifyAppointment(Appointment $appointment, array $data, User $staffUser): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $staffUser) {
            $locked = $this->findAndLockAppointment($appointment->id);
            $prevStatus = $locked->status;

            $newDate = $data['date'];
            $newTime = Carbon::parse($data['time'])->format('H:i:s');
            $dentistId = $data['dentist_id'] ?? $locked->dentist_id;

            // Check conflicts if confirming immediately or updating confirmed schedule
            if ($locked->status === Appointment::STATUS_CONFIRMED) {
                $conflict = Appointment::where('dentist_id', $dentistId)
                    ->where('id', '!=', $locked->id)
                    ->where('status', Appointment::STATUS_CONFIRMED)
                    ->whereDate('confirmed_date', $newDate)
                    ->where('confirmed_time', $newTime)
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'time' => 'Conflict detected: Dentist already has a confirmed appointment at that time slot.',
                    ]);
                }

                $locked->update([
                    'dentist_id' => $dentistId,
                    'confirmed_date' => $newDate,
                    'confirmed_time' => $newTime,
                    'staff_notes' => $data['staff_notes'] ?? $locked->staff_notes,
                ]);

                $locked->logActivity(
                    'modified',
                    $prevStatus,
                    $locked->status,
                    "Schedule modified to {$newDate} at " . Carbon::parse($newTime)->format('g:i A'),
                    $staffUser->id
                );

                $this->smsService->sendRescheduledNotice($locked);
            } else {
                // If pending, staff can propose updated slot
                $locked->update([
                    'dentist_id' => $dentistId,
                    'proposed_date' => $newDate,
                    'proposed_time' => $newTime,
                    'staff_notes' => $data['staff_notes'] ?? $locked->staff_notes,
                ]);

                $locked->logActivity(
                    'modified',
                    $prevStatus,
                    $locked->status,
                    "Proposed alternative date/time: {$newDate} at " . Carbon::parse($newTime)->format('g:i A'),
                    $staffUser->id
                );
            }

            return $locked;
        });
    }

    /**
     * Patient requests rescheduling
     */
    public function requestReschedule(Appointment $appointment, array $data, User $patient): Appointment
    {
        if ((int)$appointment->patient_id !== (int)$patient->id) {
            throw new Exception("Unauthorized access to appointment.");
        }

        if (!$appointment->canBeRescheduled()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment is not eligible for rescheduling.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $data, $patient) {
            $locked = $this->findAndLockAppointment($appointment->id);
            $prevStatus = $locked->status;

            $locked->update([
                'status' => Appointment::STATUS_RESCHEDULE_REQUESTED,
                'reschedule_reason' => $data['reason'] ?? 'Patient requested reschedule.',
                'proposed_date' => $data['preferred_date'] ?? null,
                'proposed_time' => isset($data['preferred_time']) ? Carbon::parse($data['preferred_time'])->format('H:i:s') : null,
            ]);

            $locked->logActivity(
                'reschedule_requested',
                $prevStatus,
                Appointment::STATUS_RESCHEDULE_REQUESTED,
                "Patient requested reschedule. Reason: " . ($data['reason'] ?? 'Not specified'),
                $patient->id
            );

            return $locked;
        });
    }

    /**
     * Cancel an appointment (either by patient or staff)
     */
    public function cancelAppointment(Appointment $appointment, string $reason, User $actingUser): Appointment
    {
        // If patient, ensure they own the appointment
        if ($actingUser->isPatient() && (int)$appointment->patient_id !== (int)$actingUser->id) {
            throw new Exception("Unauthorized appointment cancellation.");
        }

        if (!$appointment->canBeCancelled()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment cannot be cancelled in its current status.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $reason, $actingUser) {
            $locked = $this->findAndLockAppointment($appointment->id);
            $prevStatus = $locked->status;

            $locked->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            $locked->logActivity(
                'cancelled',
                $prevStatus,
                Appointment::STATUS_CANCELLED,
                "Cancelled by " . ($actingUser->isPatient() ? 'Patient' : 'Staff') . ". Reason: {$reason}",
                $actingUser->id
            );

            // Send cancellation notification
            $this->smsService->sendCancellationNotice($locked, $reason);

            return $locked;
        });
    }

    /**
     * Mark appointment completed (by dentist or staff)
     */
    public function markCompleted(Appointment $appointment, User $actingUser): Appointment
    {
        return DB::transaction(function () use ($appointment, $actingUser) {
            $locked = $this->findAndLockAppointment($appointment->id);
            $prevStatus = $locked->status;

            $locked->update([
                'status' => Appointment::STATUS_COMPLETED,
            ]);

            $locked->logActivity(
                'completed',
                $prevStatus,
                Appointment::STATUS_COMPLETED,
                "Marked as completed by {$actingUser->name}.",
                $actingUser->id
            );

            return $locked;
        });
    }
}
