<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $patient1;
    protected User $patient2;
    protected User $staff;
    protected Dentist $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient1 = User::create([
            'name' => 'Patient One',
            'email' => 'p1@example.com',
            'phone' => '+15551111111',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $this->patient2 = User::create([
            'name' => 'Patient Two',
            'email' => 'p2@example.com',
            'phone' => '+15552222222',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $this->staff = User::create([
            'name' => 'Clinic Staff',
            'email' => 'staff@example.com',
            'phone' => '+15553333333',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('password123'),
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Dentist',
            'email' => 'dentist@example.com',
            'phone' => '+15554444444',
            'role' => User::ROLE_DENTIST,
            'password' => Hash::make('password123'),
        ]);

        $this->dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'name' => 'Dr. Dentist',
            'specialization' => 'General',
            'email' => 'dentist@example.com',
            'phone' => '+15554444444',
        ]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $response = $this->get(route('patient.dashboard'));
        $response->assertRedirect(route('login'));

        $staffResponse = $this->get(route('staff.dashboard'));
        $staffResponse->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_staff_dashboard()
    {
        $response = $this->actingAs($this->patient1)->get(route('staff.dashboard'));
        $response->assertStatus(403);
    }

    public function test_staff_cannot_access_patient_dashboard()
    {
        $response = $this->actingAs($this->staff)->get(route('patient.dashboard'));
        $response->assertStatus(403);
    }

    public function test_patient_cannot_view_another_patients_appointment()
    {
        $appointment = Appointment::create([
            'appointment_number' => 'APT-TEST-001',
            'patient_id' => $this->patient2->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Cleaning',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        // Patient 1 tries to access Patient 2's appointment
        $response = $this->actingAs($this->patient1)->get(route('patient.appointments.show', $appointment));
        $response->assertStatus(403);
    }

    public function test_patient_cannot_cancel_another_patients_appointment()
    {
        $appointment = Appointment::create([
            'appointment_number' => 'APT-TEST-002',
            'patient_id' => $this->patient2->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Extraction',
            'requested_date' => now()->addDays(3)->format('Y-m-d'),
            'requested_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $this->actingAs($this->patient1);

        $this->expectException(\Exception::class);
        app(\App\Services\AppointmentService::class)->cancelAppointment(
            $appointment,
            'Unauthorized attempt',
            $this->patient1
        );
    }
}
