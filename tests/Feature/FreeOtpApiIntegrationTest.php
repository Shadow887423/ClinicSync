<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FreeOtpApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FreeOtpApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'name' => 'Receptionist Emily',
            'email' => 'emily@clinicsync.com',
            'phone' => '+15551239876',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    public function test_can_fetch_countries_from_free_otp_api()
    {
        Http::fake([
            'https://otp-api.shelex.dev/api/countries' => Http::response(['usa', 'canada', 'uk', 'france'], 200),
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.otp.countries'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'countries' => ['usa', 'canada', 'uk', 'france'],
        ]);
    }

    public function test_can_fetch_phone_numbers_per_country()
    {
        Http::fake([
            'https://otp-api.shelex.dev/api/list/usa' => Http::response([
                ['phone' => '+15550001111', 'country' => 'usa'],
                ['phone' => '+15550002222', 'country' => 'usa'],
            ], 200),
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.otp.numbers', 'usa'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'country' => 'usa',
            'numbers' => [
                ['phone' => '+15550001111', 'country' => 'usa'],
                ['phone' => '+15550002222', 'country' => 'usa'],
            ],
        ]);
    }

    public function test_can_fetch_otp_code()
    {
        Http::fake([
            'https://otp-api.shelex.dev/api/usa/*?*' => Http::response([
                'code' => '948214',
                'text' => 'Your CLINICSYNC verification code is 948214',
            ], 200),
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.otp.verify', [
            'country' => 'usa',
            'phone' => '+15550001111',
            'match' => 'CLINICSYNC',
            'ago' => 120,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'code' => '948214',
                'text' => 'Your CLINICSYNC verification code is 948214',
            ],
        ]);
    }
}
