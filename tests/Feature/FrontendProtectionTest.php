<?php

namespace Tests\Feature;

use App\Models\MonitoringSetting;
use App\Models\User;
use App\Services\SecurityProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@clinicsync.test',
            'status' => 'active',
        ]);

        $this->patient = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'email' => 'patient@clinicsync.test',
            'status' => 'active',
        ]);
    }

    public function test_deterrence_script_is_rendered_when_enabled()
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('window.__CLINICSYNC_SECURITY__', false);
        $response->assertSee('inspection-deterrence.min.js');
    }

    public function test_deterrence_script_is_omitted_when_disabled()
    {
        MonitoringSetting::set('security.inspection_deterrence_enabled', '0');

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('inspection-deterrence.min.js');
    }

    public function test_admin_can_update_deterrence_settings()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('staff.settings.security.update'), [
            'enabled' => '1',
            'disable_context_menu' => '1',
            'block_shortcuts' => '1',
            'detect_devtools' => '0',
            'show_warning' => '1',
            'allow_admin_bypass' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $service = app(SecurityProtectionService::class);
        $settings = $service->getSettings();

        $this->assertTrue($settings['enabled']);
        $this->assertTrue($settings['disable_context_menu']);
        $this->assertTrue($settings['block_shortcuts']);
        $this->assertFalse($settings['detect_devtools']);
        $this->assertTrue($settings['show_warning']);
        $this->assertTrue($settings['allow_admin_bypass']);
    }

    public function test_admin_can_toggle_inspection_mode_session()
    {
        $this->actingAs($this->admin);

        // Turn ON inspection mode
        $response = $this->post(route('staff.settings.toggle-inspection-mode'));
        $response->assertRedirect();
        $this->assertTrue(session('admin_inspection_mode'));

        // Turn OFF inspection mode
        $response = $this->post(route('staff.settings.toggle-inspection-mode'));
        $response->assertRedirect();
        $this->assertFalse(session('admin_inspection_mode'));
    }

    public function test_non_admin_cannot_update_security_settings()
    {
        $this->actingAs($this->patient);

        $response = $this->post(route('staff.settings.security.update'), [
            'enabled' => '0',
        ]);

        $response->assertStatus(403);
    }

    public function test_backend_security_remains_strictly_enforced_regardless_of_frontend()
    {
        // 1. Direct unauthenticated request to protected admin endpoint fails
        $response = $this->get(route('staff.settings'));
        $response->assertRedirect(route('login'));

        // 2. Patient cannot access admin settings
        $this->actingAs($this->patient);
        $response = $this->get(route('staff.settings'));
        $response->assertStatus(403);

        // 3. Server-side firewall blocks SQL injection even if sent via raw HTTP
        $response = $this->get('/?search=' . urlencode("1' UNION SELECT 1,2,3--"));
        $response->assertStatus(403);

        // 4. Server-side firewall blocks XSS payload
        $response = $this->get('/?q=' . urlencode("<script>alert('xss')</script>"));
        $response->assertStatus(403);
    }

    public function test_inspection_deterrence_assets_exist_and_contain_core_protections()
    {
        $fullPath = public_path('js/inspection-deterrence.js');
        $minPath = public_path('js/inspection-deterrence.min.js');

        $this->assertFileExists($fullPath);
        $this->assertFileExists($minPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('contextmenu', $content);
        $this->assertStringContainsString('keydown', $content);
        $this->assertStringContainsString('F12', $content);
        $this->assertStringContainsString('isInputElement', $content);
    }
}
