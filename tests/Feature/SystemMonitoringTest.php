<?php

namespace Tests\Feature;

use App\Models\MonitoringAlert;
use App\Models\MonitoringSetting;
use App\Models\PerformanceLog;
use App\Models\TrafficLog;
use App\Models\User;
use App\Services\ServerMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::unguard();
    }

    protected function tearDown(): void
    {
        User::reguard();
        parent::tearDown();
    }

    public function test_unauthenticated_users_cannot_access_monitoring(): void
    {
        $response = $this->get(route('staff.monitoring.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_monitoring(): void
    {
        $patient = User::create([
            'name' => 'Test Patient',
            'email' => 'patient.mon@example.com',
            'phone' => '+1555111222',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_PATIENT,
            'status' => 'active',
        ]);

        $staff = User::create([
            'name' => 'Test Staff',
            'email' => 'staff.mon@example.com',
            'phone' => '+1555111223',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => 'active',
        ]);

        $this->actingAs($patient)->get(route('staff.monitoring.index'))->assertStatus(403);
        $this->actingAs($staff)->get(route('staff.monitoring.index'))->assertStatus(403);
    }

    public function test_admin_user_can_access_monitoring_dashboard(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.mon@example.com',
            'phone' => '+1555111224',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('staff.monitoring.index'));
        $response->assertStatus(200);
        $response->assertSee('Real-Time System Monitoring');
        $response->assertSee('Zero Fake Data Policy Enforced');
    }

    public function test_traffic_middleware_records_real_requests(): void
    {
        $initialCount = TrafficLog::count();

        // Make an authentic request to a public route
        $response = $this->get(route('about'));
        $response->assertStatus(200);

        $this->assertGreaterThan($initialCount, TrafficLog::count());

        $latestLog = TrafficLog::latest('id')->first();
        $this->assertNotNull($latestLog);
        $this->assertEquals('/about', $latestLog->url);
        $this->assertEquals(200, $latestLog->status_code);
        $this->assertEquals('GET', $latestLog->method);
        $this->assertGreaterThan(0, $latestLog->response_time_ms);
        $this->assertGreaterThan(0, $latestLog->inbound_bytes);
        $this->assertGreaterThan(0, $latestLog->outbound_bytes);
    }

    public function test_web_vitals_api_ingests_performance_metrics(): void
    {
        $payload = [
            'url' => '/patient/dashboard',
            'fcp' => 350.5,
            'lcp' => 850.2,
            'inp' => 45.0,
            'cls' => 0.012,
            'ttfb' => 120.0,
            'dom_loaded' => 600.0,
            'fully_loaded' => 950.0,
            'response_time_ms' => 120.0,
        ];

        $response = $this->postJson(route('api.monitoring.vitals'), $payload);
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'classification' => 'excellent',
        ]);

        $this->assertDatabaseHas('performance_logs', [
            'url' => '/patient/dashboard',
            'classification' => 'excellent',
        ]);
    }

    public function test_monitoring_data_endpoint_returns_accurate_metrics(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.mon2@example.com',
            'phone' => '+1555111225',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson(route('staff.monitoring.data', ['interval' => '5m']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'interval',
            'timestamp',
            'overview' => [
                'active_visitors',
                'total_visitors',
                'page_views',
                'requests_per_second',
                'inbound_bytes',
                'outbound_bytes',
                'avg_loading_time_ms',
                'uptime_percent',
            ],
            'network_pulse',
            'geographic_distribution',
            'live_visitors',
            'server_health' => [
                'disk',
                'database',
                'uptime',
                'status',
            ],
            'performance',
            'api_endpoints',
            'traffic_analysis',
            'alerts',
        ]);
    }

    public function test_alert_lifecycle_acknowledge_and_resolve(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.mon3@example.com',
            'phone' => '+1555111226',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $alert = MonitoringAlert::create([
            'type' => 'high_response_time',
            'severity' => 'warning',
            'title' => 'Slow Database Ping',
            'message' => 'Database latency exceeded normal operational limit.',
            'status' => 'active',
            'created_at' => now(),
        ]);

        // Acknowledge
        $ackRes = $this->actingAs($admin)->postJson(route('staff.monitoring.alerts.acknowledge', $alert));
        $ackRes->assertStatus(200);
        $this->assertEquals('acknowledged', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->acknowledged_at);

        // Resolve
        $resRes = $this->actingAs($admin)->postJson(route('staff.monitoring.alerts.resolve', $alert));
        $resRes->assertStatus(200);
        $this->assertEquals('resolved', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_update_monitoring_settings(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.mon4@example.com',
            'phone' => '+1555111227',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->postJson(route('staff.monitoring.settings.update'), [
            'cpu_threshold' => 92,
            'ram_threshold' => 88,
            'latency_threshold' => 4500,
            'retention_days' => 45,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('92', MonitoringSetting::get('alert_cpu_threshold'));
        $this->assertEquals('88', MonitoringSetting::get('alert_ram_threshold'));
        $this->assertEquals('4500', MonitoringSetting::get('alert_latency_threshold_ms'));
        $this->assertEquals('45', MonitoringSetting::get('data_retention_days'));
    }

    public function test_export_monitoring_csv(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.mon5@example.com',
            'phone' => '+1555111228',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('staff.monitoring.export', ['type' => 'traffic', 'days' => 7]));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_server_monitor_service_returns_genuine_metrics(): void
    {
        $service = new ServerMonitorService();
        $metrics = $service->captureMetrics();

        $this->assertArrayHasKey('disk', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('status', $metrics);
        $this->assertNotNull($metrics['disk']['percent']);
        $this->assertTrue($metrics['database']['operational']);
    }
}
