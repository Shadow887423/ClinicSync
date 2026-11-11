<?php

namespace App\Http\Middleware;

use App\Models\MonitoringAlert;
use App\Models\MonitoringSetting;
use App\Models\TrafficLog;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordTrafficMetrics
{
    protected float $startTime;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $this->startTime = microtime(true);

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * This avoids any latency impact on the user.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $path = $request->path();

            // Skip self-monitoring polling/stream requests and static asset extensions
            if ($this->shouldSkip($request, $path)) {
                return;
            }

            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - ($this->startTime ?? $endTime)) * 1000, 2);

            // Compute payload sizes
            $inboundBytes = strlen($request->getContent());
            if ($inboundBytes === 0 && $request->header('Content-Length')) {
                $inboundBytes = (int) $request->header('Content-Length');
            }
            // Add approximate HTTP request headers size
            $inboundBytes += strlen($request->getMethod() . ' ' . $request->getRequestUri() . ' HTTP/1.1\r\n');

            $outboundContent = $response->getContent();
            $outboundBytes = $outboundContent !== false ? strlen($outboundContent) : 0;
            // Add approximate HTTP response headers size
            $outboundBytes += 300;

            // Generate privacy-safe anonymous session hash
            $sessionIdentifier = $request->hasSession() ? $request->session()->getId() : $request->ip();
            $sessionHash = 'anon_' . substr(hash('sha256', ($sessionIdentifier ?: 'unknown') . config('app.key', 'clinicsync')), 0, 16);

            $userAgent = $request->userAgent() ?: 'Unknown';
            $deviceType = $this->parseDeviceType($userAgent);
            $browser = $this->parseBrowser($userAgent);
            $os = $this->parseOs($userAgent);
            $geo = $this->parseGeoLocation($request);

            $statusCode = $response->getStatusCode();
            $isError = $statusCode >= 400;

            // Store genuine traffic log
            TrafficLog::create([
                'session_hash' => $sessionHash,
                'url' => '/' . ltrim($path, '/'),
                'route_name' => $request->route() ? $request->route()->getName() : null,
                'method' => strtoupper($request->getMethod()),
                'status_code' => $statusCode,
                'response_time_ms' => max(0.01, $responseTimeMs),
                'inbound_bytes' => max(1, $inboundBytes),
                'outbound_bytes' => max(1, $outboundBytes),
                'device_type' => $deviceType,
                'browser' => $browser,
                'os' => $os,
                'country_code' => $geo['code'],
                'country_name' => $geo['name'],
                'referer' => $request->header('referer'),
                'is_error' => $isError,
                'created_at' => now(),
            ]);

            // Real-time anomaly / threshold checks
            $this->evaluateAnomalies($request, $statusCode, $responseTimeMs);

        } catch (Exception $e) {
            // Fail silently to never break existing application requests
        }
    }

    protected function shouldSkip(Request $request, string $path): bool
    {
        // Don't monitor the monitoring stream itself to avoid recursive loops
        if (str_starts_with($path, 'staff/monitoring/stream') || str_starts_with($path, 'staff/monitoring/data')) {
            return true;
        }

        // Skip static asset files if requested directly through Laravel
        $extensions = ['js', 'css', 'ico', 'png', 'jpg', 'jpeg', 'svg', 'woff', 'woff2', 'map'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension && in_array(strtolower($extension), $extensions, true)) {
            return true;
        }

        return false;
    }

    protected function parseDeviceType(string $ua): string
    {
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/(mobile|iphone|ipod|blackberry|iemobile|opera mini|webos)/i', $ua)) {
            return 'Mobile';
        }
        if (preg_match('/(bot|crawler|spider|curl|wget|slurp)/i', $ua)) {
            return 'Bot';
        }
        return 'Desktop';
    }

    protected function parseBrowser(string $ua): string
    {
        if (preg_match('/Edg\/([0-9.]+)/i', $ua)) return 'Edge';
        if (preg_match('/Chrome\/([0-9.]+)/i', $ua)) return 'Chrome';
        if (preg_match('/Firefox\/([0-9.]+)/i', $ua)) return 'Firefox';
        if (preg_match('/Safari\/([0-9.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) return 'Safari';
        if (preg_match('/Opera|OPR\/([0-9.]+)/i', $ua)) return 'Opera';
        return 'Other';
    }

    protected function parseOs(string $ua): string
    {
        if (preg_match('/Windows NT 10/i', $ua)) return 'Windows 10/11';
        if (preg_match('/Windows/i', $ua)) return 'Windows';
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) return 'macOS';
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) return 'iOS';
        if (preg_match('/Android/i', $ua)) return 'Android';
        if (preg_match('/Linux/i', $ua)) return 'Linux';
        return 'Unknown';
    }

    protected function parseGeoLocation(Request $request): array
    {
        // 1. Cloudflare header support (accurate in Cloudflare deployment)
        $cfCountry = $request->header('CF-IPCountry');
        if ($cfCountry && strlen($cfCountry) === 2 && strtoupper($cfCountry) !== 'XX') {
            $code = strtoupper($cfCountry);
            $names = [
                'PH' => 'Philippines', 'US' => 'United States', 'GB' => 'United Kingdom',
                'CA' => 'Canada', 'AU' => 'Australia', 'DE' => 'Germany', 'SG' => 'Singapore',
                'JP' => 'Japan', 'IN' => 'India', 'FR' => 'France',
            ];
            return ['code' => $code, 'name' => $names[$code] ?? $code];
        }

        // 2. Local / Private IPs
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return ['code' => 'LOC', 'name' => 'Localhost / Private Network'];
        }

        return ['code' => 'UN', 'name' => 'Global Access'];
    }

    protected function evaluateAnomalies(Request $request, int $statusCode, float $responseTimeMs): void
    {
        // Server Error (500 or 503)
        if ($statusCode === 500 || $statusCode === 503) {
            MonitoringAlert::firstOrCreate(
                [
                    'type' => 'http_error_spike',
                    'title' => "HTTP {$statusCode} Error Encountered",
                    'status' => 'active',
                ],
                [
                    'severity' => 'critical',
                    'message' => "Application returned HTTP {$statusCode} on endpoint: " . $request->path(),
                    'affected_service' => 'Web Application',
                    'threshold_value' => '< 500 Status',
                    'actual_value' => "HTTP {$statusCode}",
                    'created_at' => now(),
                ]
            );
        }

        // Severe Response Latency (> 3000ms)
        $latencyThreshold = (float) MonitoringSetting::get('alert_latency_threshold_ms', 3000);
        if ($responseTimeMs > $latencyThreshold) {
            MonitoringAlert::firstOrCreate(
                [
                    'type' => 'high_response_time',
                    'title' => 'High Page Response Latency',
                    'status' => 'active',
                ],
                [
                    'severity' => 'warning',
                    'message' => "Page {$request->path()} took {$responseTimeMs}ms to respond (threshold: {$latencyThreshold}ms).",
                    'affected_service' => $request->path(),
                    'threshold_value' => "{$latencyThreshold}ms",
                    'actual_value' => "{$responseTimeMs}ms",
                    'created_at' => now(),
                ]
            );
        }
    }
}
