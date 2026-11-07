<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\SmsNotification;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\FreeOtpApiService;
use App\Services\SmsGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityHardeningRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $admin;
    protected Dentist $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'name' => 'Staff Receptionist',
            'email' => 'receptionist@clinicsync.com',
            'phone' => '+15551112222',
            'role' => User::ROLE_STAFF,
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->admin = User::create([
            'name' => 'System Administrator',
            'email' => 'director@clinicsync.com',
            'phone' => '+15553334444',
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Alexander Smith',
            'email' => 'dr.smith@clinicsync.com',
            'phone' => '+15555556666',
            'role' => User::ROLE_DENTIST,
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'name' => 'Dr. Alexander Smith',
            'email' => 'dr.smith@clinicsync.com',
            'phone' => '+15555556666',
            'specialization' => 'Orthodontics',
            'status' => 'active',
            'slot_duration_minutes' => 30,
        ]);
    }

    /**
     * Fix 1: Staff vs. Admin Role Separation
     */
    public function test_staff_cannot_access_admin_settings_or_manage_dentists()
    {
        // 1. Staff cannot view settings
        $response = $this->actingAs($this->staff)->get(route('staff.settings'));
        $response->assertStatus(403);

        // 2. Staff cannot create new dentists
        $response = $this->actingAs($this->staff)->post(route('staff.dentists.store'), [
            'name' => 'Dr. Rogue',
            'email' => 'rogue@clinicsync.com',
            'phone' => '+15559998888',
            'specialization' => 'General',
            'slot_duration_minutes' => 30,
            'password' => 'Password123!',
        ]);
        $response->assertStatus(403);

        // 3. Staff cannot dispatch arbitrary test SMS
        $response = $this->actingAs($this->staff)->post(route('staff.test-sms'), [
            'phone' => '+15559998888',
            'message' => 'Unauthorized broadcast',
        ]);
        $response->assertStatus(403);

        // 4. Admin CAN access settings
        $response = $this->actingAs($this->admin)->get(route('staff.settings'));
        $response->assertStatus(200);
    }

    /**
     * Fix 3: Appointment State Machine Integrity (Cannot Confirm Cancelled Appointment)
     */
    public function test_appointment_cannot_be_confirmed_if_cancelled()
    {
        $patient = User::create([
            'name' => 'Patient Sample',
            'email' => 'patient.sample@clinicsync.com',
            'phone' => '+15557778888',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-SEC-001',
            'patient_id' => $patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Consultation',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        $service = app(AppointmentService::class);

        $this->expectException(ValidationException::class);
        $service->confirmAppointment($appointment, [
            'dentist_id' => $this->dentist->id,
            'confirmed_date' => now()->addDays(2)->format('Y-m-d'),
            'confirmed_time' => '10:00:00',
        ], $this->staff);
    }

    /**
     * Fix 3: Appointment State Machine Integrity (Cannot Complete Pending or Cancelled Appointment)
     */
    public function test_appointment_cannot_be_completed_if_not_confirmed()
    {
        $patient = User::create([
            'name' => 'Patient Sample 2',
            'email' => 'patient2@clinicsync.com',
            'phone' => '+15557778889',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-SEC-002',
            'patient_id' => $patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Cleaning',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $service = app(AppointmentService::class);

        $this->expectException(ValidationException::class);
        $service->markCompleted($appointment, $this->staff);
    }

    /**
     * Fix 4: User Model Mass Assignment Hardening
     */
    public function test_user_model_role_and_status_are_protected_from_mass_assignment()
    {
        User::reguard();

        $user = User::create([
            'name' => 'Malicious Submitter',
            'email' => 'attacker@example.com',
            'phone' => '+15556667777',
            'password' => Hash::make('Password123!'),
            'role' => User::ROLE_ADMIN,
            'status' => 'inactive',
        ]);

        $fresh = $user->fresh();
        // Role must remain default 'patient' and status 'active', ignoring mass assignment
        $this->assertEquals(User::ROLE_PATIENT, $fresh->role);
        $this->assertEquals('active', $fresh->status);

        User::unguard();
    }

    /**
     * Fix 5: SSRF Protection on Custom HTTP SMS Gateway
     */
    public function test_ssrf_blocked_in_custom_http_sms_gateway()
    {
        config([
            'sms.provider' => 'custom_http',
            'sms.simulation_enabled' => false,
            'sms.custom_http.url' => 'http://169.254.169.254/latest/meta-data',
        ]);

        $service = app(SmsGatewayService::class);
        $notification = $service->sendSms(
            '+15551234567',
            'Testing SSRF protection',
            SmsNotification::TYPE_GENERAL
        );

        $this->assertEquals(SmsNotification::STATUS_FAILED, $notification->status);
        $this->assertStringContainsString('SSRF Protection', $notification->error_message);
    }

    /**
     * Fix 6: Calendar DoS Prevention with Malformed Input
     */
    public function test_calendar_handles_malformed_month_input_safely()
    {
        $response = $this->actingAs($this->staff)->get(route('staff.calendar', ['month' => 'malicious-string-attempt']));
        
        // Must redirect safely back with session error and NOT throw 500 error
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * Fix 7: Free OTP API Client Input Validation
     */
    public function test_free_otp_service_rejects_traversal_parameters()
    {
        $service = app(FreeOtpApiService::class);

        // Path traversal payload
        $numbers = $service->getPhoneNumbers('../../../etc/passwd');
        $this->assertIsArray($numbers);
        $this->assertEmpty($numbers);

        $code = $service->getOtpCode('../../../', 'invalid_phone');
        $this->assertNull($code);
    }

    /**
     * Fix 8: Anti-Bot Honeypot Defense on Registration
     */
    public function test_registration_honeypot_drops_bot_submissions()
    {
        $response = $this->post(route('register'), [
            'name' => 'Bot Spammer',
            'email' => 'bot@spammer.com',
            'phone' => '+15559871111',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'hp_field' => 'bot-filled-trap-value',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('users', ['email' => 'bot@spammer.com']);
    }
}
