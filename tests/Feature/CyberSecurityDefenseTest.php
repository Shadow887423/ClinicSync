<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CyberSecurityDefenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Layer 1: HTTP Security Headers are returned on responses
     */
    public function test_security_headers_are_present()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Test Layer 2: SQL Injection attempts are caught and blocked with 403
     */
    public function test_input_firewall_blocks_sql_injection()
    {
        $response = $this->get('/?search=' . urlencode("1' UNION SELECT 1,2,3--"));
        $response->assertStatus(403);
    }

    /**
     * Test Layer 3: Cross-Site Scripting (XSS) probe payloads are blocked
     */
    public function test_input_firewall_blocks_xss_payloads()
    {
        $response = $this->get('/?q=' . urlencode("<script>alert('XSS')</script>"));
        $response->assertStatus(403);
    }

    /**
     * Test Layer 4: Directory traversal probes are blocked
     */
    public function test_input_firewall_blocks_directory_traversal()
    {
        $response = $this->get('/?file=' . urlencode("../../../../etc/passwd"));
        $response->assertStatus(403);
    }

    /**
     * Test Layer 5: Automated reconnaissance scanner bots are blocked
     */
    public function test_cyber_attack_blocker_stops_exploit_scanners()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'sqlmap/1.6#stable (https://sqlmap.org)',
        ])->get('/');

        $response->assertStatus(403);
    }

    /**
     * Test Layer 6: Sensitive environment and backdoor file probing is blocked
     */
    public function test_cyber_attack_blocker_stops_sensitive_file_probes()
    {
        $response = $this->get('/.env');
        $response->assertStatus(403);

        $response2 = $this->get('/phpinfo.php');
        $response2->assertStatus(403);
    }
}
