<?php

namespace Tests\Feature;

use App\Models\Dentist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('CLINICSYNC');
    }

    public function test_public_pages_load_successfully()
    {
        $this->get('/about')->assertStatus(200)->assertSee('About CLINICSYNC');
        $this->get('/features')->assertStatus(200)->assertSee('System Features');
        $this->get('/contact')->assertStatus(200)->assertSee('Contact Dental Clinic');
        $this->get('/login')->assertStatus(200)->assertSee('Sign In');
        $this->get('/register')->assertStatus(200)->assertSee('Create Patient Account');
    }
}
