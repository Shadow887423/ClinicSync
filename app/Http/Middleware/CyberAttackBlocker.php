<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY LAYER 5: Cyber Attack & Web Vulnerability Scanner Blocker
 * 
 * Intercepts reconnaissance bots, automated exploit scanners, bad user agents,
 * sensitive file access attempts (.env, .git, phpinfo, wp-admin, shell scripts),
 * and potential zero-day probing.
 */
class CyberAttackBlocker
{
    /**
     * Malicious or probe file path patterns.
     */
    protected array $blockedPathPatterns = [
        '/\.env/i',
        '/\.git/i',
        '/\.svn/i',
        '/\.htaccess/i',
        '/\.aws/i',
        '/phpinfo(\.php)?/i',
        '/info\.php/i',
        '/test\.php/i',
        '/eval-stdin/i',
        '/vendor\/phpunit/i',
        '/wp-(admin|login|content|includes)/i',
        '/xmlrpc\.php/i',
        '/phpmyadmin/i',
        '/pma/i',
        '/mysqladmin/i',
        '/adminer(\.php)?/i',
        '/actuator\/(gateway|health|env)/i',
        '/solr\//i',
        '/console\//i',
        '/\.well-known\/(?!acme-challenge)/i',
        '/(shell|cmd|c99|r57|wso)\.php/i',
    ];

    /**
     * Known malicious or exploit scanning user agents.
     */
    protected array $badUserAgents = [
        'sqlmap',
        'nikto',
        'acunetix',
        'nessus',
        'nmap',
        'w3af',
        'dirbuster',
        'gobuster',
        'hydra',
        'metasploit',
        'havij',
        'zgrab',
        'masscan',
    ];

    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        $userAgent = strtolower($request->userAgent() ?? '');
        $ip = $request->ip();

        // 1. Check Bad User Agents
        foreach ($this->badUserAgents as $badAgent) {
            if (str_contains($userAgent, $badAgent)) {
                Log::critical("[CYBER ATTACK BLOCKED] Scanner tool detected", [
                    'ip' => $ip,
                    'user_agent' => $request->userAgent(),
                    'path' => $path,
                ]);
                abort(403, 'Forbidden: Automated exploit scanner detected.');
            }
        }

        // 2. Check for Probing of Sensitive Files / Paths
        foreach ($this->blockedPathPatterns as $pattern) {
            if (preg_match($pattern, $path) || preg_match($pattern, $request->getRequestUri())) {
                Log::critical("[CYBER ATTACK BLOCKED] Sensitive path probe", [
                    'ip' => $ip,
                    'user_agent' => $request->userAgent(),
                    'path' => $request->getRequestUri(),
                ]);
                abort(403, 'Access denied.');
            }
        }

        // 3. Block HTTP Request Smuggling (Conflicting Content-Length and Transfer-Encoding)
        if ($request->hasHeader('Transfer-Encoding') && $request->hasHeader('Content-Length')) {
            Log::critical("[CYBER ATTACK BLOCKED] HTTP Smuggling attempt", [
                'ip' => $ip,
            ]);
            abort(400, 'Bad Request: Invalid headers.');
        }

        return $next($request);
    }
}
