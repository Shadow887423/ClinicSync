<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\SmsNotification;
use App\Models\User;
use App\Services\SmsGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $patient;
    protected Dentist $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::create([
            'name' => 'John Doe',
            'email' => 'john.sms@example.com',
            'phone' => '+15559876543',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Aris Thorne',
            'email' => 'thorne.sms@example.com',
            'phone' => '+15558765432',
            'role' => User::ROLE_DENTIST,
            'password' => Hash::make('password123'),
        ]);

        $this->dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'name' => 'Dr. Aris Thorne',
            'email' => 'thorne.sms@example.com',
            'phone' => '+15558765432',
            'specialization' => 'General Dentistry',
        ]);
    }

    public function test_send_confirmation_sms_records_gateway_details()
    {
        $appointment = Appointment::create([
            'appointment_number' => 'APT-SMS-001',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Cleaning',
            'requested_date' => Carbon::tomorrow()->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'confirmed_date' => Carbon::tomorrow()->format('Y-m-d'),
            'confirmed_time' => '10:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $service = app(SmsGatewayService::class);
        $notification = $service->sendConfirmation($appointment);

        $this->assertNotNull($notification);
        $this->assertEquals(SmsNotification::STATUS_SIMULATED, $notification->status);
        $this->assertStringContainsString('John Doe', $notification->message);
        $this->assertStringContainsString('Dr. Aris Thorne', $notification->message);
        $this->assertStringContainsString('10:00 AM', $notification->message);
        $this->assertNotNull($notification->gateway_reference);
    }

    public function test_send_appointment_reminders_command_dispatches_reminders()
    {
        // Create an appointment occurring in 12 hours (within 24h reminder window)
        $appointmentTime = Carbon::now()->addHours(12);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-REMIND-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Checkup',
            'requested_date' => $appointmentTime->format('Y-m-d'),
            'requested_time' => $appointmentTime->format('H:i:s'),
            'confirmed_date' => $appointmentTime->format('Y-m-d'),
            'confirmed_time' => $appointmentTime->format('H:i:s'),
            'status' => Appointment::STATUS_CONFIRMED,
            'reminder_sent_at' => null,
        ]);

        // Run artisan reminder command
        $exitCode = Artisan::call('appointments:send-reminders', ['--hours' => 24]);
        $this->assertEquals(0, $exitCode);

        // Verify reminder was sent and marked on appointment
        $appointment->refresh();
        $this->assertNotNull($appointment->reminder_sent_at);

        $this->assertDatabaseHas('sms_notifications', [
            'appointment_id' => $appointment->id,
            'notification_type' => SmsNotification::TYPE_REMINDER,
            'recipient_phone' => '+15559876543',
        ]);

        // Run again to ensure idempotency (no duplicate reminder sent)
        $initialCount = SmsNotification::where('appointment_id', $appointment->id)->where('notification_type', SmsNotification::TYPE_REMINDER)->count();
        Artisan::call('appointments:send-reminders', ['--hours' => 24]);
        $subsequentCount = SmsNotification::where('appointment_id', $appointment->id)->where('notification_type', SmsNotification::TYPE_REMINDER)->count();

        $this->assertEquals($initialCount, $subsequentCount);
    }

    public function test_retry_notification_dispatches_failed_message()
    {
        $notification = SmsNotification::create([
            'recipient_phone' => '+15559876543',
            'notification_type' => SmsNotification::TYPE_GENERAL,
            'message' => 'Test retry message',
            'status' => SmsNotification::STATUS_FAILED,
            'gateway' => 'simulated',
            'error_message' => 'Simulated prior failure',
        ]);

        $service = app(SmsGatewayService::class);
        $updated = $service->retryNotification($notification);

        $this->assertEquals(SmsNotification::STATUS_SIMULATED, $updated->status);
        $this->assertNull($updated->error_message);
        $this->assertNotNull($updated->gateway_reference);
    }
}
