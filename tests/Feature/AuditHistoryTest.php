<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Dentist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected User $patient;
    protected Dentist $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@clinicsync.test',
            'status' => 'active',
        ]);

        $this->staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'email' => 'staff@clinicsync.test',
            'status' => 'active',
        ]);

        $this->patient = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'email' => 'patient@clinicsync.test',
            'status' => 'active',
        ]);

        $this->dentist = Dentist::create([
            'name' => 'Dr. Eleanor Vance',
            'specialization' => 'Orthodontics',
            'license_number' => 'DENT-9921',
            'phone' => '+15550001111',
            'email' => 'vance@clinicsync.test',
            'slot_duration_minutes' => 30,
            'status' => 'active',
        ]);
    }

    public function test_record_creation_automatically_creates_audit_log()
    {
        $this->actingAs($this->patient);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-20261114-TEST',
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service_type' => 'Root Canal Therapy',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
            'action' => 'created',
            'user_id' => $this->patient->id,
        ]);

        $log = AuditLog::where('auditable_type', Appointment::class)
            ->where('auditable_id', $appointment->id)
            ->where('action', 'created')
            ->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('service_type', $log->new_values);
        $this->assertEquals('Root Canal Therapy', $log->new_values['service_type']);
    }

    public function test_record_update_captures_diff_with_old_and_new_values()
    {
        $this->actingAs($this->patient);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-20261114-DIFF',
            'patient_id' => $this->patient->id,
            'service_type' => 'Cleaning',
            'requested_date' => now()->addDays(3)->format('Y-m-d'),
            'requested_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $this->actingAs($this->staff);

        $appointment->update([
            'status' => Appointment::STATUS_CONFIRMED,
            'confirmed_date' => now()->addDays(3)->format('Y-m-d'),
            'confirmed_time' => '11:00:00',
            'staff_notes' => 'Confirmed by telephone triage.',
        ]);

        $updateLog = AuditLog::where('auditable_id', $appointment->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals($this->staff->id, $updateLog->user_id);
        $this->assertEquals('pending', $updateLog->old_values['status']);
        $this->assertEquals('confirmed', $updateLog->new_values['status']);

        // Test diff accessor
        $diff = $updateLog->diff;
        $this->assertNotEmpty($diff);
        $statusDiff = collect($diff)->firstWhere('field', 'status');
        $this->assertNotNull($statusDiff);
        $this->assertEquals('pending', $statusDiff['old']);
        $this->assertEquals('confirmed', $statusDiff['new']);
        $this->assertTrue($statusDiff['changed']);
    }

    public function test_sensitive_attributes_are_redacted_in_audit_snapshots()
    {
        $this->actingAs($this->admin);

        $newUser = User::create([
            'name' => 'Dr. Secret Person',
            'email' => 'secret@clinicsync.test',
            'phone' => '+15559990000',
            'password' => bcrypt('super_secret_password'),
        ]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $newUser->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->new_values['password']);
        $this->assertStringNotContainsString('super_secret_password', json_encode($log->new_values));
    }

    public function test_soft_delete_preserves_record_and_logs_deletion_event_with_reason()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-20261114-DEL',
            'patient_id' => $this->patient->id,
            'service_type' => 'Consultation',
            'requested_date' => now()->addDays(4)->format('Y-m-d'),
            'requested_time' => '14:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->delete(route('staff.audit.delete', [
            'type' => 'appointment',
            'id' => $appointment->id,
        ]), [
            'deletion_reason' => 'Patient requested immediate purge due to relocation.',
        ]);

        $response->assertRedirect();

        // Verify record is soft deleted and not physically removed
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);

        $delLog = AuditLog::where('auditable_type', Appointment::class)
            ->where('auditable_id', $appointment->id)
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($delLog);
        $this->assertEquals('Patient requested immediate purge due to relocation.', $delLog->reason);
        $this->assertEquals($this->admin->id, $delLog->user_id);
    }

    public function test_record_restoration_recovers_record_and_appends_audit_log()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-20261114-REST',
            'patient_id' => $this->patient->id,
            'service_type' => 'Consultation',
            'requested_date' => now()->addDays(5)->format('Y-m-d'),
            'requested_time' => '15:00:00',
            'status' => Appointment::STATUS_PENDING,
            'deletion_reason' => 'Duplicate booking.',
        ]);

        $appointment->delete();
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);

        // Restore record
        $response = $this->post(route('staff.audit.restore', [
            'type' => 'appointment',
            'id' => $appointment->id,
        ]), [
            'restoration_reason' => 'Recovered after patient confirmed it was not a duplicate.',
        ]);

        $response->assertRedirect();

        // Check restored in database
        $this->assertNotSoftDeleted('appointments', ['id' => $appointment->id]);

        $restoreLog = AuditLog::where('auditable_type', Appointment::class)
            ->where('auditable_id', $appointment->id)
            ->where('action', 'restored')
            ->first();

        $this->assertNotNull($restoreLog);
        $this->assertEquals('Recovered after patient confirmed it was not a duplicate.', $restoreLog->reason);
    }

    public function test_admin_can_access_audit_hub_and_record_history()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-20261114-VIEW',
            'patient_id' => $this->patient->id,
            'service_type' => 'Checkup',
            'requested_date' => now()->addDays(1)->format('Y-m-d'),
            'requested_time' => '09:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        // Audit Hub
        $response = $this->get(route('staff.audit.index'));
        $response->assertStatus(200);
        $response->assertSee('Audit Activity Trail');

        // Record History
        $response = $this->get(route('staff.audit.record', ['type' => 'appointment', 'id' => $appointment->id]));
        $response->assertStatus(200);
        $response->assertSee('APT-20261114-VIEW');
        $response->assertSee('Chronological Lifecycle Timeline');

        // Deleted Records
        $response = $this->get(route('staff.audit.deleted'));
        $response->assertStatus(200);
        $response->assertSee('Recoverable Deleted Records (Trash)');
    }

    public function test_non_admin_cannot_access_audit_hub()
    {
        // Staff cannot access
        $this->actingAs($this->staff);
        $response = $this->get(route('staff.audit.index'));
        $response->assertStatus(403);

        // Patient cannot access
        $this->actingAs($this->patient);
        $response = $this->get(route('staff.audit.index'));
        $response->assertStatus(403);
    }

    public function test_audit_search_and_filters_function_correctly()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-SEARCH-FILTER',
            'patient_id' => $this->patient->id,
            'service_type' => 'Wisdom Tooth Extraction',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '13:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->get(route('staff.audit.index', [
            'record_type' => 'appointment',
            'record_id' => $appointment->id,
            'action' => 'created',
        ]));

        $response->assertStatus(200);
        $response->assertSee("#{$appointment->id}");
    }

    public function test_compare_endpoint_returns_json_diff()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'appointment_number' => 'APT-DIFF-JSON',
            'patient_id' => $this->patient->id,
            'service_type' => 'Crown Placement',
            'requested_date' => now()->addDays(2)->format('Y-m-d'),
            'requested_time' => '14:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $appointment->update([
            'service_type' => 'Implant Consultation',
        ]);

        $log = AuditLog::where('auditable_id', $appointment->id)->latest('id')->first();

        $response = $this->get(route('staff.audit.compare', $log->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'action',
            'action_label',
            'description',
            'user',
            'diff',
        ]);
    }

    public function test_export_csv_downloads_audit_data()
    {
        $this->actingAs($this->admin);

        Appointment::create([
            'appointment_number' => 'APT-CSV-TEST',
            'patient_id' => $this->patient->id,
            'service_type' => 'Teeth Whitening',
            'requested_date' => now()->addDays(3)->format('Y-m-d'),
            'requested_time' => '16:00:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->get(route('staff.audit.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
