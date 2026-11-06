<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistAvailability;
use App\Models\SmsNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $patient;
    protected User $staff;
    protected User $dentistUser;
    protected Dentist $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::create([
            'name' => 'Demo Patient',
            'email' => 'patient@test.com',
            'phone' => '+15551234567',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $this->staff = User::create([
            'name' => 'Demo Staff',
            'email' => 'staff@test.com',
            'phone' => '+15552345678',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('password123'),
        ]);

        $this->dentistUser = User::create([
            'name' => 'Dr. Test Dentist',
            'email' => 'dentist@test.com',
            'phone' => '+15553456789',
            'role' => User::ROLE_DENTIST,
            'password' => Hash::make('password123'),
        ]);

        $this->dentist = Dentist::create([
            'user_id' => $this->dentistUser->id,
            'name' => 'Dr. Test Dentist',
            'email' => 'dentist@test.com',
            'phone' => '+15553456789',
            'specialization' => 'General Dentistry',
            'slot_duration_minutes' => 30,
            'status' => 'active',
        ]);

        // Mon - Fri availability
        for ($i = 1; $i <= 5; $i++) {
            DentistAvailability::create([
                'dentist_id' => $this->dentist->id,
                'day_of_week' => $i,
                'day_name' => Carbon::now()->startOfWeek()->addDays($i - 1)->format('l'),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration_minutes' => 30,
                'status' => 'active',
            ]);
        }
    }

    public function test_patient_can_create_appointment_request()
    {
        $futureDate = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->actingAs($this->patient)->post(route('patient.appointments.store'), [
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Dental Cleaning',
            'requested_date' => $futureDate,
            'requested_time' => '10:00',
            'patient_notes' => 'First visit for routine cleaning.',
        ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Dental Cleaning',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $appointment = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($appointment->appointment_number);
        $response->assertRedirect(route('patient.appointments.show', $appointment));
    }

    public function test_patient_cannot_book_past_date()
    {
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        $response = $this->actingAs($this->patient)->post(route('patient.appointments.store'), [
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Dental Cleaning',
            'requested_date' => $pastDate,
            'requested_time' => '10:00',
        ]);

        $response->assertSessionHasErrors(['requested_date']);
    }

    public function test_staff_can_confirm_appointment_and_trigger_sms()
    {
        $futureDate = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $appointment = Appointment::create([
            'appointment_number' => 'APT-CONFIRM-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Checkup',
            'requested_date' => $futureDate,
            'requested_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->staff)->post(route('staff.appointments.confirm', $appointment), [
            'dentist_id' => $this->dentist->id,
            'confirmed_date' => $futureDate,
            'confirmed_time' => '11:00',
            'staff_notes' => 'Confirmed with Dr. Test Dentist',
        ]);

        $appointment->refresh();
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->status);
        $this->assertEquals($futureDate, $appointment->confirmed_date->format('Y-m-d'));

        // Verify SMS confirmation was triggered and recorded in sms_notifications table
        $this->assertDatabaseHas('sms_notifications', [
            'appointment_id' => $appointment->id,
            'notification_type' => SmsNotification::TYPE_CONFIRMATION,
            'recipient_phone' => '+15551234567',
        ]);
    }

    public function test_prevention_of_conflicting_confirmed_appointments()
    {
        $date = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');
        $time = '14:00:00';

        // 1st Appointment already confirmed
        Appointment::create([
            'appointment_number' => 'APT-EXISTING',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Extraction',
            'requested_date' => $date,
            'requested_time' => $time,
            'confirmed_date' => $date,
            'confirmed_time' => $time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        // 2nd Appointment requesting the exact same slot
        $secondAppointment = Appointment::create([
            'appointment_number' => 'APT-CONFLICT',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Filling',
            'requested_date' => $date,
            'requested_time' => $time,
            'status' => Appointment::STATUS_PENDING,
        ]);

        // Attempting to confirm 2nd appointment for the same dentist & time must fail
        $response = $this->actingAs($this->staff)->post(route('staff.appointments.confirm', $secondAppointment), [
            'dentist_id' => $this->dentist->id,
            'confirmed_date' => $date,
            'confirmed_time' => $time,
        ]);

        $response->assertSessionHasErrors(['confirmed_time']);
        $secondAppointment->refresh();
        $this->assertEquals(Appointment::STATUS_PENDING, $secondAppointment->status);
    }

    public function test_staff_can_reject_appointment_with_reason()
    {
        $futureDate = Carbon::now()->next(Carbon::THURSDAY)->format('Y-m-d');

        $appointment = Appointment::create([
            'appointment_number' => 'APT-REJECT-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Root Canal',
            'requested_date' => $futureDate,
            'requested_time' => '15:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->staff)->post(route('staff.appointments.reject', $appointment), [
            'rejection_reason' => 'Doctor has emergency surgery scheduled.',
        ]);

        $appointment->refresh();
        $this->assertEquals(Appointment::STATUS_REJECTED, $appointment->status);
        $this->assertEquals('Doctor has emergency surgery scheduled.', $appointment->rejection_reason);

        // Verify rejection SMS was sent
        $this->assertDatabaseHas('sms_notifications', [
            'appointment_id' => $appointment->id,
            'notification_type' => SmsNotification::TYPE_REJECTION,
        ]);
    }

    public function test_patient_can_request_rescheduling()
    {
        $futureDate = Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d');

        $appointment = Appointment::create([
            'appointment_number' => 'APT-RESCHED-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Cleaning',
            'requested_date' => $futureDate,
            'requested_time' => '09:30:00',
            'confirmed_date' => $futureDate,
            'confirmed_time' => '09:30:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($this->patient)->post(route('patient.appointments.reschedule.store', $appointment), [
            'reason' => 'Family event on Friday morning',
            'preferred_date' => Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d'),
            'preferred_time' => '10:00',
        ]);

        $appointment->refresh();
        $this->assertEquals(Appointment::STATUS_RESCHEDULE_REQUESTED, $appointment->status);
        $this->assertEquals('Family event on Friday morning', $appointment->reschedule_reason);
    }

    public function test_patient_can_cancel_appointment()
    {
        $futureDate = Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d');

        $appointment = Appointment::create([
            'appointment_number' => 'APT-CANCEL-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Cleaning',
            'requested_date' => $futureDate,
            'requested_time' => '14:00:00',
            'confirmed_date' => $futureDate,
            'confirmed_time' => '14:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($this->patient)->post(route('patient.appointments.cancel', $appointment), [
            'cancellation_reason' => 'Moving out of city',
        ]);

        $appointment->refresh();
        $this->assertEquals(Appointment::STATUS_CANCELLED, $appointment->status);
        $this->assertEquals('Moving out of city', $appointment->cancellation_reason);

        // Verify Cancellation SMS was sent
        $this->assertDatabaseHas('sms_notifications', [
            'appointment_id' => $appointment->id,
            'notification_type' => SmsNotification::TYPE_CANCELLATION,
        ]);
    }

    public function test_dentist_can_mark_assigned_appointment_completed()
    {
        $appointment = Appointment::create([
            'appointment_number' => 'APT-COMPLETE-01',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Restoration',
            'requested_date' => Carbon::today()->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'confirmed_date' => Carbon::today()->format('Y-m-d'),
            'confirmed_time' => '10:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($this->dentistUser)->post(route('dentist.appointments.complete', $appointment));

        $appointment->refresh();
        $this->assertEquals(Appointment::STATUS_COMPLETED, $appointment->status);
    }
}
