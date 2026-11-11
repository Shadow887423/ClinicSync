<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MonitoringAlert;
use App\Models\MonitoringSetting;
use App\Models\PerformanceLog;
use App\Models\ServerMetric;
use App\Models\TrafficLog;
use App\Models\UptimeLog;
use App\Services\ServerMonitorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    protected ServerMonitorService $serverMonitor;

    public function __construct(ServerMonitorService $serverMonitor)
    {
        $this->serverMonitor = $serverMonitor;
    }

    /**
     * Render the Master NOC Monitoring Dashboard.
     */
    public function index()
    {
        $serverMetrics = $this->serverMonitor->captureMetrics();
        $activeAlertsCount = MonitoringAlert::where('status', 'active')->count();

        $settings = [
            'cpu_threshold' => (float) MonitoringSetting::get('alert_cpu_threshold', 85),
            'ram_threshold' => (float) MonitoringSetting::get('alert_ram_threshold', 90),
            'latency_threshold' => (float) MonitoringSetting::get('alert_latency_threshold_ms', 3000),
            'retention_days' => (int) MonitoringSetting::get('data_retention_days', 30),
        ];

        return view('staff.monitoring.index', compact('serverMetrics', 'activeAlertsCount', 'settings'));
    }

    /**
     * Polling API endpoint returning structured genuine monitoring data.
     */
    public function data(Request $request): JsonResponse
    {
        $interval = $request->query('interval', '5m');
        $data = $this->compileMonitoringDataset($interval);

        return response()->json($data);
    }

    /**
     * Server-Sent Events (SSE) live stream feed for real-time dashboard updates.
     */
    public function stream(Request $request): StreamedResponse
    {
        $interval = $request->query('interval', '5m');

        return response()->stream(function () use ($interval) {
            // Send initial connected ping
            echo "event: connected\n";
            echo 'data: ' . json_encode(['status' => 'connected', 'timestamp' => now()->toIso8601String()]) . "\n\n";
            ob_flush();
            flush();

            // Run a brief burst of iterations (or single snapshot if running in testing/serverless)
            $iterations = 0;
            $maxIterations = app()->environment('testing') ? 1 : 10;

            while ($iterations < $maxIterations) {
                if (connection_aborted()) {
                    break;
                }

                $data = $this->compileMonitoringDataset($interval);
                echo "event: metrics\n";
                echo 'data: ' . json_encode($data) . "\n\n";

                ob_flush();
                flush();

                $iterations++;
                if ($iterations < $maxIterations) {
                    sleep(3);
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Acknowledge an active alert.
     */
    public function acknowledgeAlert(MonitoringAlert $alert): JsonResponse
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        return response()->json(['success' => true, 'alert' => $alert]);
    }

    /**
     * Resolve an alert.
     */
    public function resolveAlert(MonitoringAlert $alert): JsonResponse
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true, 'alert' => $alert]);
    }

    /**
     * Update alert thresholds and retention policies.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cpu_threshold' => 'required|numeric|min:10|max:100',
            'ram_threshold' => 'required|numeric|min:10|max:100',
            'latency_threshold' => 'required|numeric|min:100|max:30000',
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        MonitoringSetting::set('alert_cpu_threshold', $validated['cpu_threshold'], 'CPU % threshold for alerts');
        MonitoringSetting::set('alert_ram_threshold', $validated['ram_threshold'], 'RAM % threshold for alerts');
        MonitoringSetting::set('alert_latency_threshold_ms', $validated['latency_threshold'], 'Page latency threshold in ms');
        MonitoringSetting::set('data_retention_days', $validated['retention_days'], 'Days to retain raw metrics');

        return response()->json(['success' => true, 'message' => 'Monitoring settings updated successfully.']);
    }

    /**
     * Export genuine monitoring history as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $type = $request->query('type', 'traffic'); // traffic, performance, server
        $days = (int) $request->query('days', 7);
        $since = now()->subDays(max(1, min(90, $days)));

        $filename = "clinicsync_{$type}_monitoring_" . now()->format('Y-m-d_His') . ".csv";

        return response()->streamDownload(function () use ($type, $since) {
            $handle = fopen('php://output', 'w');

            if ($type === 'performance') {
                fputcsv($handle, ['ID', 'URL', 'FCP (ms)', 'LCP (ms)', 'INP (ms)', 'CLS', 'TTFB (ms)', 'DOM Loaded (ms)', 'Fully Loaded (ms)', 'Classification', 'Timestamp']);
                PerformanceLog::where('created_at', '>=', $since)->orderBy('created_at', 'desc')->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id, $row->url, $row->fcp, $row->lcp, $row->inp, $row->cls,
                            $row->ttfb, $row->dom_loaded, $row->fully_loaded, $row->classification,
                            $row->created_at ? $row->created_at->toIso8601String() : '',
                        ]);
                    }
                });
            } elseif ($type === 'server') {
                fputcsv($handle, ['ID', 'CPU (%)', 'RAM (%)', 'RAM Used (MB)', 'RAM Total (MB)', 'Disk (%)', 'Disk Used (GB)', 'Disk Total (GB)', 'DB Latency (ms)', 'DB Connections', 'Status', 'Timestamp']);
                ServerMetric::where('created_at', '>=', $since)->orderBy('created_at', 'desc')->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id, $row->cpu_usage, $row->ram_usage,
                            $row->ram_used_bytes ? round($row->ram_used_bytes / 1048576, 2) : '',
                            $row->ram_total_bytes ? round($row->ram_total_bytes / 1048576, 2) : '',
                            $row->disk_usage,
                            $row->disk_used_bytes ? round($row->disk_used_bytes / 1073741824, 2) : '',
                            $row->disk_total_bytes ? round($row->disk_total_bytes / 1073741824, 2) : '',
                            $row->db_response_time_ms, $row->db_connections, $row->status,
                            $row->created_at ? $row->created_at->toIso8601String() : '',
                        ]);
                    }
                });
            } else {
                fputcsv($handle, ['ID', 'Session Hash', 'URL', 'Method', 'Status Code', 'Response Time (ms)', 'Inbound (Bytes)', 'Outbound (Bytes)', 'Device', 'Browser', 'OS', 'Country Code', 'Country Name', 'Is Error', 'Timestamp']);
                TrafficLog::where('created_at', '>=', $since)->orderBy('created_at', 'desc')->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id, $row->session_hash, $row->url, $row->method, $row->status_code,
                            $row->response_time_ms, $row->inbound_bytes, $row->outbound_bytes,
                            $row->device_type, $row->browser, $row->os, $row->country_code, $row->country_name,
                            $row->is_error ? '1' : '0',
                            $row->created_at ? $row->created_at->toIso8601String() : '',
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Compile genuine real-time and interval-based metrics dataset.
     */
    protected function compileMonitoringDataset(string $interval): array
    {
        $since = $this->parseInterval($interval);
        $seconds = max(1, now()->diffInSeconds($since));

        // 1. Overview Metrics
        $trafficQuery = TrafficLog::where('created_at', '>=', $since);
        $totalRequests = (clone $trafficQuery)->count();
        $totalVisitors = (clone $trafficQuery)->distinct('session_hash')->count('session_hash');
        $activeVisitors = TrafficLog::where('created_at', '>=', now()->subMinutes(5))->distinct('session_hash')->count('session_hash');
        $inboundBytes = (int) (clone $trafficQuery)->sum('inbound_bytes');
        $outboundBytes = (int) (clone $trafficQuery)->sum('outbound_bytes');
        $avgResponseTime = (float) ((clone $trafficQuery)->avg('response_time_ms') ?? 0);
        $errorCount = (clone $trafficQuery)->where('is_error', true)->count();
        $errorRate = $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 2) : 0.0;
        $rps = round($totalRequests / $seconds, 2);

        // Calculate Uptime
        $uptimeLogTotal = UptimeLog::where('checked_at', '>=', $since)->count();
        $outageCount = UptimeLog::where('checked_at', '>=', $since)->where('status', 'outage')->count();
        $uptimePercent = $uptimeLogTotal > 0 ? round((($uptimeLogTotal - $outageCount) / $uptimeLogTotal) * 100, 2) : 100.0;

        // 2. Real Server Health
        $serverHealth = $this->serverMonitor->captureMetrics();

        // 3. Network Traffic Pulse (Bucketed Time Series)
        $pulseBuckets = $this->buildTrafficPulseBuckets($since, $interval);

        // 4. Geographic Distribution
        $geoDistribution = TrafficLog::where('created_at', '>=', $since)
            ->whereNotNull('country_name')
            ->select('country_code', 'country_name', DB::raw('count(*) as count'), DB::raw('count(distinct session_hash) as visitors'))
            ->groupBy('country_code', 'country_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($row) use ($totalRequests) {
                return [
                    'code' => $row->country_code,
                    'name' => $row->country_name,
                    'hits' => (int) $row->count,
                    'visitors' => (int) $row->visitors,
                    'percentage' => $totalRequests > 0 ? round(($row->count / $totalRequests) * 100, 1) : 0,
                ];
            });

        // 5. Live Visitor Activity (Recent active sessions)
        $liveVisitors = TrafficLog::where('created_at', '>=', now()->subMinutes(30))
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('session_hash')
            ->take(15)
            ->map(function ($log) {
                $sessionLogs = TrafficLog::where('session_hash', $log->session_hash);
                $firstSeen = (clone $sessionLogs)->min('created_at');
                $durationSec = $firstSeen ? now()->diffInSeconds(Carbon::parse($firstSeen)) : 0;
                $pageCount = (clone $sessionLogs)->count();

                return [
                    'session_hash' => $log->session_hash,
                    'url' => $log->url,
                    'method' => $log->method,
                    'status_code' => $log->status_code,
                    'device_type' => $log->device_type,
                    'browser' => $log->browser,
                    'os' => $log->os,
                    'country_name' => $log->country_name ?: 'Global Access',
                    'country_code' => $log->country_code ?: 'UN',
                    'duration_formatted' => $this->formatDuration($durationSec),
                    'last_seen' => $log->created_at ? $log->created_at->diffForHumans() : 'Just now',
                    'page_count' => $pageCount,
                ];
            })->values();

        // 6. Website Loading Performance (Web Vitals)
        $vitalsQuery = PerformanceLog::where('created_at', '>=', $since);
        $vitals = [
            'fcp' => round((clone $vitalsQuery)->avg('fcp') ?? 0, 1),
            'lcp' => round((clone $vitalsQuery)->avg('lcp') ?? 0, 1),
            'inp' => round((clone $vitalsQuery)->avg('inp') ?? 0, 1),
            'cls' => round((clone $vitalsQuery)->avg('cls') ?? 0, 3),
            'ttfb' => round((clone $vitalsQuery)->avg('ttfb') ?? 0, 1),
            'dom_loaded' => round((clone $vitalsQuery)->avg('dom_loaded') ?? 0, 1),
            'fully_loaded' => round((clone $vitalsQuery)->avg('fully_loaded') ?? 0, 1),
            'sample_count' => (clone $vitalsQuery)->count(),
        ];

        // Slow Pages Breakdown
        $slowPages = TrafficLog::where('created_at', '>=', $since)
            ->select('url', DB::raw('count(*) as count'), DB::raw('avg(response_time_ms) as avg_latency'), DB::raw('sum(case when is_error = 1 then 1 else 0 end) as error_count'))
            ->groupBy('url')
            ->orderBy('avg_latency', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $latency = round($row->avg_latency, 1);
                $classification = $latency < 1000 ? 'excellent' : ($latency <= 2000 ? 'good' : ($latency <= 3000 ? 'moderate' : ($latency <= 5000 ? 'slow' : 'critical')));
                return [
                    'url' => $row->url,
                    'requests' => (int) $row->count,
                    'avg_latency_ms' => $latency,
                    'error_count' => (int) $row->error_count,
                    'classification' => $classification,
                ];
            });

        // 7. API / Endpoint Monitoring
        $apiEndpoints = TrafficLog::where('created_at', '>=', $since)
            ->select('url', 'method', DB::raw('count(*) as total_requests'), DB::raw('avg(response_time_ms) as avg_duration'), DB::raw('sum(case when status_code < 400 then 1 else 0 end) as success_count'), DB::raw('sum(case when status_code >= 400 then 1 else 0 end) as fail_count'))
            ->groupBy('url', 'method')
            ->orderBy('total_requests', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'url' => $row->url,
                    'method' => $row->method,
                    'total' => (int) $row->total_requests,
                    'avg_duration_ms' => round($row->avg_duration, 1),
                    'success_count' => (int) $row->success_count,
                    'fail_count' => (int) $row->fail_count,
                    'status' => $row->fail_count > 0 ? ($row->fail_count >= $row->success_count ? 'Critical' : 'Degraded') : 'Operational',
                ];
            });

        // 8. Traffic Spike Analysis & Peak Records
        $peakRps = $this->calculatePeakRps($since);
        $hourExpr = DB::getDriverName() === 'sqlite' ? "strftime('%H', created_at) as hour" : "HOUR(created_at) as hour";
        $peakTrafficHour = TrafficLog::where('created_at', '>=', $since)
            ->select(DB::raw($hourExpr), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        // 9. Active Alerts
        $alerts = MonitoringAlert::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'interval' => $interval,
            'timestamp' => now()->toIso8601String(),
            'overview' => [
                'active_visitors' => $activeVisitors,
                'total_visitors' => $totalVisitors,
                'page_views' => $totalRequests,
                'requests_per_second' => $rps,
                'inbound_bytes' => $inboundBytes,
                'outbound_bytes' => $outboundBytes,
                'inbound_formatted' => $this->formatBytes($inboundBytes),
                'outbound_formatted' => $this->formatBytes($outboundBytes),
                'inbound_rate' => $this->formatBitrate($inboundBytes, $seconds),
                'outbound_rate' => $this->formatBitrate($outboundBytes, $seconds),
                'avg_loading_time_ms' => round($avgResponseTime, 1),
                'server_response_time_ms' => $serverHealth['database']['latency_ms'] ?? round($avgResponseTime, 1),
                'error_rate_percent' => $errorRate,
                'uptime_percent' => $uptimePercent,
            ],
            'network_pulse' => $pulseBuckets,
            'geographic_distribution' => $geoDistribution,
            'live_visitors' => $liveVisitors,
            'performance' => [
                'vitals' => $vitals,
                'slow_pages' => $slowPages,
            ],
            'server_health' => $serverHealth,
            'api_endpoints' => $apiEndpoints,
            'traffic_analysis' => [
                'peak_rps' => $peakRps,
                'busiest_hour' => $peakTrafficHour ? sprintf('%02d:00 - %02d:00', $peakTrafficHour->hour, ($peakTrafficHour->hour + 1) % 24) : 'N/A',
                'spike_detected' => $rps > 50,
            ],
            'alerts' => $alerts,
        ];
    }

    protected function parseInterval(string $interval): Carbon
    {
        return match ($interval) {
            '1m' => now()->subMinute(),
            '5m' => now()->subMinutes(5),
            '15m' => now()->subMinutes(15),
            '30m' => now()->subMinutes(30),
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            default => now()->subMinutes(5),
        };
    }

    protected function buildTrafficPulseBuckets(Carbon $since, string $interval): array
    {
        $bucketCount = 15;
        $totalSeconds = max(15, now()->diffInSeconds($since));
        $bucketSeconds = max(1, (int) floor($totalSeconds / $bucketCount));

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $bucketStart = (clone $since)->addSeconds($i * $bucketSeconds);
            $bucketEnd = (clone $bucketStart)->addSeconds($bucketSeconds);

            $inbound = (int) TrafficLog::whereBetween('created_at', [$bucketStart, $bucketEnd])->sum('inbound_bytes');
            $outbound = (int) TrafficLog::whereBetween('created_at', [$bucketStart, $bucketEnd])->sum('outbound_bytes');
            $reqCount = TrafficLog::whereBetween('created_at', [$bucketStart, $bucketEnd])->count();

            $buckets[] = [
                'label' => $bucketStart->format('H:i:s'),
                'inbound_kb' => round($inbound / 1024, 2),
                'outbound_kb' => round($outbound / 1024, 2),
                'requests' => $reqCount,
                'rps' => round($reqCount / $bucketSeconds, 2),
            ];
        }

        return $buckets;
    }

    protected function calculatePeakRps(Carbon $since): float
    {
        // Calculate max requests recorded in any single second
        $peak = TrafficLog::where('created_at', '>=', $since)
            ->select(DB::raw('count(*) as count'))
            ->groupBy('created_at')
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? (float) $peak->count : 0.0;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    protected function formatBitrate(int $bytes, int $seconds): string
    {
        $bitsPerSec = ($bytes * 8) / max(1, $seconds);
        if ($bitsPerSec >= 1000000) return round($bitsPerSec / 1000000, 2) . ' Mbps';
        if ($bitsPerSec >= 1000) return round($bitsPerSec / 1000, 2) . ' Kbps';
        return round($bitsPerSec, 2) . ' bps';
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds}s";
        $mins = floor($seconds / 60);
        $remSecs = $seconds % 60;
        return "{$mins}m {$remSecs}s";
    }
}
