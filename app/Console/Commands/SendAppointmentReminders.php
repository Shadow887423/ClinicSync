<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\SmsGatewayService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders {--hours= : Hours before appointment to send reminder}';
    protected $description = 'Dispatch automated SMS reminders for upcoming confirmed appointments';

    public function handle(SmsGatewayService $smsService)
    {
        $hours = (int)($this->option('hours') ?: env('SMS_REMINDER_HOURS_BEFORE', 24));
        $now = now();
        $targetWindow = $now->copy()->addHours($hours);

        $this->info("Scanning confirmed appointments occurring between {$now->toDateTimeString()} and {$targetWindow->toDateTimeString()} (within {$hours} hours)...");

        // Fetch eligible confirmed appointments
        $appointments = Appointment::where('status', Appointment::STATUS_CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('confirmed_date')
            ->whereNotNull('confirmed_time')
            ->get()
            ->filter(function ($appointment) use ($now, $targetWindow) {
                $aptDateTime = Carbon::parse($appointment->confirmed_date->format('Y-m-d') . ' ' . $appointment->confirmed_time);
                return $aptDateTime->gte($now) && $aptDateTime->lte($targetWindow);
            });

        if ($appointments->isEmpty()) {
            $this->info("No pending reminders found within the specified time window.");
            return 0;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($appointments as $apt) {
            $patientName = $apt->patient ? $apt->patient->name : 'Patient #' . $apt->patient_id;
            $this->line("Processing reminder for [{$apt->appointment_number}] {$patientName}...");

            $notification = $smsService->sendReminder($apt);

            if ($notification && in_array($notification->status, ['sent', 'delivered', 'simulated'])) {
                $this->info(" -> Reminder successfully sent ({$notification->gateway} ref: {$notification->gateway_reference})");
                $sentCount++;
            } else {
                $this->error(" -> Reminder failed: " . ($notification->error_message ?? 'Unknown error'));
                $failedCount++;
            }
        }

        $this->info("Completed sending reminders. Successfully processed: {$sentCount}, Failed: {$failedCount}");
        return 0;
    }
}
