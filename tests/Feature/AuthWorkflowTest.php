<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_successfully()
    {
        $response = $this->post('/register', [
            'name' => 'Alice Patient',
            'email' => 'alice@example.com',
            'phone' => '+15551234567',
            'birth_date' => '1995-06-15',
            'gender' => 'Female',
            'address' => '123 Test Street',
            'password' => 'secretPassword123',
            'password_confirmation' => 'secretPassword123',
        ]);

        $response->assertRedirect(route('patient.dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'role' => User::ROLE_PATIENT,
            'phone' => '+15551234567',
        ]);
        $this->assertAuthenticated();
    }

    public function test_registration_validates_phone_and_unique_email()
    {
        User::create([
            'name' => 'Existing',
            'email' => 'duplicate@example.com',
            'phone' => '+15551112233',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/register', [
            'name' => 'Bob',
            'email' => 'duplicate@example.com',
            'phone' => 'invalid-phone-string',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_patient_login_redirects_to_patient_dashboard()
    {
        $patient = User::create([
            'name' => 'Charlie Patient',
            'email' => 'charlie@example.com',
            'phone' => '+15553334455',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'charlie@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('patient.dashboard'));
        $this->assertAuthenticatedAs($patient);
    }

    public function test_staff_login_redirects_to_staff_dashboard()
    {
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff_test@example.com',
            'phone' => '+15554445566',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'staff_test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_deactivated_account_cannot_login()
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'phone' => '+15559998877',
            'role' => User::ROLE_PATIENT,
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
