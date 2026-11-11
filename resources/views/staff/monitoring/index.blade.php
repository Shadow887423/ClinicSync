@extends('layouts.dashboard')

@section('title', 'NOC System Monitoring & Real-Time Analytics')
@section('page-title', 'Real-Time System Monitoring')

@section('styles')
<style>
    /* Dark NOC Terminal Styling */
    .noc-container {
        background-color: #0b1320;
        color: #e2e8f0;
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }

    .noc-card {
        background: #111c2e;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 1.25rem;
        transition: all 0.2s ease-in-out;
    }

    .noc-card:hover {
        border-color: rgba(45, 212, 191, 0.4);
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .noc-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.75px;
        color: #94a3b8;
        margin-bottom: 0.35rem;
    }

    .noc-value {
        font-size: 1.65rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }

    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulseAnimation 2s infinite;
    }

    @keyframes pulseAnimation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .pulse-dot.disconnected {
        background-color: #ef4444;
        animation: none;
    }

    .pulse-dot.reconnecting {
        background-color: #f59e0b;
        animation: pulseWarning 1.5s infinite;
    }

    @keyframes pulseWarning {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    .noc-table {
        color: #cbd5e1;
        font-size: 0.85rem;
    }

    .noc-table th {
        background-color: #0d1726;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding: 0.75rem;
    }

    .noc-table td {
        padding: 0.75rem;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        vertical-align: middle;
    }

    .noc-table tr:hover td {
        background-color: rgba(255,255,255,0.02);
    }

    .progress-bar-custom {
        height: 8px;
        background-color: #1e293b;
        border-radius: 999px;
        overflow: hidden;
    }

    .vital-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
    }

    .vital-excellent { background-color: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .vital-good { background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    .vital-moderate { background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .vital-slow { background-color: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3); }
    .vital-critical { background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
</style>
@endsection

@section('content')
<div class="noc-container">

    <!-- Top Status Control Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-dark border border-secondary border-opacity-25">
                <span class="pulse-dot" id="statusDot"></span>
                <span class="small fw-bold text-white" id="connectionStatusText">Connecting (SSE)...</span>
            </div>
            <div class="small text-muted d-none d-md-block">
                Last update: <span class="text-white" id="lastUpdatedText">{{ now()->format('H:i:s') }}</span>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 d-none d-lg-inline-block">
                <i class="bi bi-shield-check me-1"></i> Zero Fake Data Policy Enforced
            </span>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Interval Selector -->
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50" id="intervalSelect" style="width: auto;">
                <option value="1m">Last 1 Minute</option>
                <option value="5m" selected>Last 5 Minutes</option>
                <option value="15m">Last 15 Minutes</option>
                <option value="30m">Last 30 Minutes</option>
                <option value="1h">Last 1 Hour</option>
                <option value="24h">Last 24 Hours</option>
            </select>

            <!-- Play/Pause Button -->
            <button class="btn btn-sm btn-outline-light" id="pauseResumeBtn" title="Pause live visualization">
                <i class="bi bi-pause-fill me-1" id="pauseIcon"></i> <span id="pauseBtnText">Pause</span>
            </button>

            <!-- Manual Refresh -->
            <button class="btn btn-sm btn-outline-secondary text-white" id="refreshBtn" title="Refresh metrics">
                <i class="bi bi-arrow-repeat"></i>
            </button>

            <!-- Export CSV -->
            <button class="btn btn-sm btn-teal" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download me-1"></i> Export Report
            </button>

            <!-- Settings -->
            <button class="btn btn-sm btn-outline-secondary text-white" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-sliders"></i>
            </button>
        </div>
    </div>

    <!-- Active Alerts Banner (if any) -->
    <div id="alertsContainer" class="mb-4" style="display: none;"></div>

    <!-- 1. Real-Time Overview Cards -->
    <div class="row g-3 mb-4">
        <!-- Active Visitors -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Active Visitors</span>
                    <i class="bi bi-people-fill text-info"></i>
                </div>
                <div class="noc-value text-info" id="metricActiveVisitors">--</div>
                <div class="small text-muted mt-1">Live in last 5 min</div>
            </div>
        </div>

        <!-- Requests Per Second -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Traffic Rate</span>
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                </div>
                <div class="noc-value" id="metricRps">--</div>
                <div class="small text-muted mt-1">Requests / sec (RPS)</div>
            </div>
        </div>

        <!-- Inbound Bandwidth -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Inbound Traffic</span>
                    <i class="bi bi-arrow-down-left-circle-fill text-primary"></i>
                </div>
                <div class="noc-value text-primary" id="metricInbound">--</div>
                <div class="small text-muted mt-1" id="metricInboundRate">--</div>
            </div>
        </div>

        <!-- Outbound Bandwidth -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Outbound Traffic</span>
                    <i class="bi bi-arrow-up-right-circle-fill text-success"></i>
                </div>
                <div class="noc-value text-success" id="metricOutbound">--</div>
                <div class="small text-muted mt-1" id="metricOutboundRate">--</div>
            </div>
        </div>

        <!-- Avg Page Loading Time -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Avg Loading Time</span>
                    <i class="bi bi-stopwatch-fill text-teal" style="color: var(--secondary-teal);"></i>
                </div>
                <div class="noc-value" id="metricAvgLoading">--</div>
                <div class="small text-muted mt-1">Server + response time</div>
            </div>
        </div>

        <!-- System Uptime -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="noc-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="noc-label">Website Uptime</span>
                    <i class="bi bi-shield-check text-success"></i>
                </div>
                <div class="noc-value text-success" id="metricUptime">--</div>
                <div class="small text-muted mt-1" id="metricErrorRate">Error Rate: 0.0%</div>
            </div>
        </div>
    </div>

    <!-- 2. Live Network Traffic Pulse & Geographic Distribution -->
    <div class="row g-4 mb-4">
        <!-- NOC Network Traffic Pulse Chart -->
        <div class="col-lg-8">
            <div class="noc-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold text-white mb-0">
                            <i class="bi bi-activity text-teal me-2" style="color: var(--secondary-teal);"></i>
                            Live Network Traffic Pulse
                        </h6>
                        <span class="small text-muted">Inbound vs Outbound bandwidth volume</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25">Inbound</span>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25">Outbound</span>
                    </div>
                </div>
                <div style="height: 280px; position: relative;">
                    <canvas id="trafficPulseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Geographic Visitor Distribution -->
        <div class="col-lg-4">
            <div class="noc-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold text-white mb-0">
                            <i class="bi bi-globe-americas text-info me-2"></i>
                            Geographic Distribution
                        </h6>
                        <span class="small text-muted">Top traffic origins by location</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 280px;">
                    <table class="table table-borderless noc-table mb-0">
                        <thead>
                            <tr>
                                <th>Country / Region</th>
                                <th class="text-end">Visitors</th>
                                <th class="text-end">Share</th>
                            </tr>
                        </thead>
                        <tbody id="geoTableBody">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Waiting for traffic data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted mt-2 border-top border-secondary border-opacity-25 pt-2" style="font-size: 0.72rem;">
                    <i class="bi bi-lock me-1"></i> Privacy-compliant anonymized IP resolution
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Server Health & Core Web Vitals -->
    <div class="row g-4 mb-4">
        <!-- Real Server Health -->
        <div class="col-lg-6">
            <div class="noc-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold text-white mb-0">
                            <i class="bi bi-cpu-fill text-warning me-2"></i>
                            Genuine Server Health
                        </h6>
                        <span class="small text-muted">Hardware utilization & database latency</span>
                    </div>
                    <span class="badge bg-success" id="serverStatusBadge">Operational</span>
                </div>

                <div class="row g-3">
                    <!-- CPU -->
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3" style="background-color: #0d1726;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="noc-label">CPU Load</span>
                                <span class="fw-bold text-white" id="serverCpuText">--</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="bg-warning h-100" id="serverCpuBar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- RAM -->
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3" style="background-color: #0d1726;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="noc-label">Memory (RAM)</span>
                                <span class="fw-bold text-white" id="serverRamText">--</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="bg-info h-100" id="serverRamBar" style="width: 0%;"></div>
                            </div>
                            <div class="small text-muted mt-1" style="font-size: 0.7rem;" id="serverRamDetails">--</div>
                        </div>
                    </div>

                    <!-- Disk -->
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3" style="background-color: #0d1726;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="noc-label">Storage Disk</span>
                                <span class="fw-bold text-white" id="serverDiskText">--</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="bg-primary h-100" id="serverDiskBar" style="width: 0%;"></div>
                            </div>
                            <div class="small text-muted mt-1" style="font-size: 0.7rem;" id="serverDiskDetails">--</div>
                        </div>
                    </div>

                    <!-- Database Health -->
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3" style="background-color: #0d1726;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="noc-label">Database Latency</span>
                                <span class="fw-bold text-white" id="serverDbLatency">--</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <span>Active Connections: <strong class="text-white" id="serverDbConnections">--</strong></span>
                                <span>Uptime: <strong class="text-white" id="serverUptimeDuration">--</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Browser Web Vitals -->
        <div class="col-lg-6">
            <div class="noc-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold text-white mb-0">
                            <i class="bi bi-speedometer text-success me-2"></i>
                            Browser Web Vitals & Page Loading
                        </h6>
                        <span class="small text-muted">Field performance measured via browser PerformanceObserver</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">FCP (Paint)</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalFcp">--</div>
                            <span class="vital-badge vital-good" id="badgeFcp">Good</span>
                        </div>
                    </div>

                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">LCP (Content)</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalLcp">--</div>
                            <span class="vital-badge vital-good" id="badgeLcp">Good</span>
                        </div>
                    </div>

                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">TTFB (Backend)</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalTtfb">--</div>
                            <span class="vital-badge vital-excellent" id="badgeTtfb">Excellent</span>
                        </div>
                    </div>

                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">DOM Loaded</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalDom">--</div>
                            <span class="small text-muted">Ready event</span>
                        </div>
                    </div>

                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">Fully Loaded</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalFullyLoaded">--</div>
                            <span class="small text-muted">Window load</span>
                        </div>
                    </div>

                    <div class="col-sm-4 col-6">
                        <div class="p-2 rounded-3 text-center" style="background-color: #0d1726;">
                            <span class="noc-label">CLS (Shift)</span>
                            <div class="h5 fw-bold text-white mb-1" id="vitalCls">--</div>
                            <span class="vital-badge vital-excellent" id="badgeCls">Stable</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Live Visitor Activity Monitor -->
    <div class="noc-card mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h6 class="fw-bold text-white mb-0">
                    <i class="bi bi-person-lines-fill text-info me-2"></i>
                    Live Visitor Activity Monitor
                </h6>
                <span class="small text-muted">Real-time sessions browsing ClinicSync</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary border-opacity-50" id="visitorSearchInput" placeholder="Filter URL, Device or Location...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless noc-table mb-0" id="visitorTable">
                <thead>
                    <tr>
                        <th>Anonymous Session</th>
                        <th>Target Webpage</th>
                        <th>Device & Browser</th>
                        <th>OS</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Pages</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody id="visitorTableBody">
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Waiting for incoming visitor traffic...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. API & Endpoint Monitoring & Slow Pages -->
    <div class="row g-4">
        <!-- API Endpoint Performance -->
        <div class="col-lg-6">
            <div class="noc-card h-100">
                <h6 class="fw-bold text-white mb-2">
                    <i class="bi bi-hdd-network-fill text-primary me-2"></i>
                    API & Endpoint Monitoring
                </h6>
                <span class="small text-muted d-block mb-3">Top accessed routes, response duration & status codes</span>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-borderless noc-table mb-0">
                        <thead>
                            <tr>
                                <th>Method / Route</th>
                                <th class="text-end">Requests</th>
                                <th class="text-end">Avg Latency</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody id="apiTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No endpoint traffic recorded in this interval.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Slow Pages & Bottlenecks -->
        <div class="col-lg-6">
            <div class="noc-card h-100">
                <h6 class="fw-bold text-white mb-2">
                    <i class="bi bi-hourglass-split text-warning me-2"></i>
                    Page Latency & Performance Classification
                </h6>
                <span class="small text-muted d-block mb-3">Pages ranked by response duration</span>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-borderless noc-table mb-0">
                        <thead>
                            <tr>
                                <th>Route / Page</th>
                                <th class="text-end">Hits</th>
                                <th class="text-end">Latency</th>
                                <th class="text-end">Rating</th>
                            </tr>
                        </thead>
                        <tbody id="slowPagesTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No slow page bottlenecks identified.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2 text-teal"></i> Export Monitoring Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('staff.monitoring.export') }}" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dataset Type</label>
                        <select name="type" class="form-select bg-black text-white border-secondary">
                            <option value="traffic">Traffic Logs (HTTP Requests & Bandwidth)</option>
                            <option value="performance">Browser Performance & Web Vitals</option>
                            <option value="server">Server Hardware Metrics (CPU, RAM, Disk)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">History Window</label>
                        <select name="days" class="form-select bg-black text-white border-secondary">
                            <option value="1">Last 24 Hours</option>
                            <option value="7" selected>Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <div class="small text-muted">
                        Downloads authentic raw records in standard CSV format without simulated entries.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-teal"><i class="bi bi-download me-1"></i> Download CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-sliders me-2 text-warning"></i> Monitoring & Alert Thresholds</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="settingsForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CPU Utilization Alert Threshold (%)</label>
                        <input type="number" name="cpu_threshold" class="form-control bg-black text-white border-secondary" value="{{ $settings['cpu_threshold'] }}" min="10" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">RAM Utilization Alert Threshold (%)</label>
                        <input type="number" name="ram_threshold" class="form-control bg-black text-white border-secondary" value="{{ $settings['ram_threshold'] }}" min="10" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">High Page Response Latency Alert (ms)</label>
                        <input type="number" name="latency_threshold" class="form-control bg-black text-white border-secondary" value="{{ $settings['latency_threshold'] }}" min="100" max="30000" step="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Raw Metrics Retention Period (Days)</label>
                        <input type="number" name="retention_days" class="form-control bg-black text-white border-secondary" value="{{ $settings['retention_days'] }}" min="1" max="365" required>
                    </div>
                    <div id="settingsAlertBox" class="alert d-none small"></div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-teal" id="saveSettingsBtn">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let isPaused = false;
    let eventSource = null;
    let reconnectTimeout = null;
    let reconnectAttempts = 0;
    let pollingInterval = null;
    let currentInterval = '5m';

    // Chart.js Traffic Pulse Chart
    const ctx = document.getElementById('trafficPulseChart').getContext('2d');
    const trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Inbound (KB)',
                    data: [],
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.15)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2,
                },
                {
                    label: 'Outbound (KB)',
                    data: [],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', font: { size: 10 } },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#0f172a',
                    titleColor: '#e2e8f0',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            }
        }
    });

    // Connection Status Elements
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('connectionStatusText');
    const lastUpdatedText = document.getElementById('lastUpdatedText');
    const intervalSelect = document.getElementById('intervalSelect');
    const pauseResumeBtn = document.getElementById('pauseResumeBtn');
    const pauseIcon = document.getElementById('pauseIcon');
    const pauseBtnText = document.getElementById('pauseBtnText');
    const refreshBtn = document.getElementById('refreshBtn');

    function updateConnectionState(state, message) {
        statusDot.className = 'pulse-dot ' + state;
        statusText.textContent = message;
    }

    // Connect to SSE Stream with Fallback Polling & Auto-reconnect
    function startRealtimeConnection() {
        if (eventSource) {
            eventSource.close();
        }

        updateConnectionState('reconnecting', 'Connecting (SSE)...');

        try {
            const streamUrl = "{{ route('staff.monitoring.stream') }}?interval=" + currentInterval;
            eventSource = new EventSource(streamUrl);

            eventSource.addEventListener('connected', function(e) {
                reconnectAttempts = 0;
                updateConnectionState('', 'Connected (Live Stream)');
            });

            eventSource.addEventListener('metrics', function(e) {
                if (isPaused) return;
                try {
                    const data = JSON.parse(e.data);
                    renderDashboardData(data);
                } catch (err) {}
            });

            eventSource.onerror = function(err) {
                eventSource.close();
                eventSource = null;
                reconnectAttempts++;

                if (reconnectAttempts <= 3) {
                    const delay = Math.min(30, Math.pow(2, reconnectAttempts)) * 1000;
                    updateConnectionState('reconnecting', `Reconnecting (${reconnectAttempts})...`);
                    reconnectTimeout = setTimeout(startRealtimeConnection, delay);
                } else {
                    // Switch to Fallback Polling
                    updateConnectionState('', 'Connected (Polling Fallback)');
                    startPollingFallback();
                }
            };
        } catch (e) {
            startPollingFallback();
        }
    }

    function startPollingFallback() {
        if (pollingInterval) clearInterval(pollingInterval);
        fetchData();
        pollingInterval = setInterval(fetchData, 4000);
    }

    function fetchData() {
        if (isPaused) return;
        fetch("{{ route('staff.monitoring.data') }}?interval=" + currentInterval)
            .then(res => res.json())
            .then(data => {
                renderDashboardData(data);
                updateConnectionState('', 'Connected (Polling Active)');
            })
            .catch(() => {
                updateConnectionState('disconnected', 'Connection Interrupted');
            });
    }

    // Render Data into DOM
    function renderDashboardData(data) {
        lastUpdatedText.textContent = new Date().toLocaleTimeString();

        // 1. Overview Metrics
        const ov = data.overview;
        document.getElementById('metricActiveVisitors').textContent = ov.active_visitors;
        document.getElementById('metricRps').textContent = ov.requests_per_second;
        document.getElementById('metricInbound').textContent = ov.inbound_formatted;
        document.getElementById('metricInboundRate').textContent = ov.inbound_rate;
        document.getElementById('metricOutbound').textContent = ov.outbound_formatted;
        document.getElementById('metricOutboundRate').textContent = ov.outbound_rate;
        document.getElementById('metricAvgLoading').textContent = ov.avg_loading_time_ms > 0 ? ov.avg_loading_time_ms + ' ms' : 'Waiting for Data';
        document.getElementById('metricUptime').textContent = ov.uptime_percent + '%';
        document.getElementById('metricErrorRate').textContent = 'Error Rate: ' + ov.error_rate_percent + '%';

        // 2. Network Pulse Chart
        if (data.network_pulse && data.network_pulse.length > 0) {
            trafficChart.data.labels = data.network_pulse.map(p => p.label);
            trafficChart.data.datasets[0].data = data.network_pulse.map(p => p.inbound_kb);
            trafficChart.data.datasets[1].data = data.network_pulse.map(p => p.outbound_kb);
            trafficChart.update();
        }

        // 3. Geographic Distribution
        const geoTbody = document.getElementById('geoTableBody');
        if (data.geographic_distribution && data.geographic_distribution.length > 0) {
            geoTbody.innerHTML = data.geographic_distribution.map(g => `
                <tr>
                    <td><span class="badge bg-dark border border-secondary text-info me-1">${g.code}</span> ${g.name}</td>
                    <td class="text-end fw-semibold text-white">${g.visitors}</td>
                    <td class="text-end text-muted">${g.percentage}%</td>
                </tr>
            `).join('');
        } else {
            geoTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No geographic traffic in selected window.</td></tr>';
        }

        // 4. Server Health
        const sh = data.server_health;
        const cpuVal = sh.cpu_usage !== null ? sh.cpu_usage + '%' : 'Unavailable';
        document.getElementById('serverCpuText').textContent = cpuVal;
        document.getElementById('serverCpuBar').style.width = (sh.cpu_usage || 0) + '%';

        const ramVal = sh.ram.percent !== null ? sh.ram.percent + '%' : 'Unavailable';
        document.getElementById('serverRamText').textContent = ramVal;
        document.getElementById('serverRamBar').style.width = (sh.ram.percent || 0) + '%';
        document.getElementById('serverRamDetails').textContent = sh.ram.used_gb ? `${sh.ram.used_gb} GB used of ${sh.ram.total_gb} GB` : 'System Memory';

        const diskVal = sh.disk.percent !== null ? sh.disk.percent + '%' : 'Unavailable';
        document.getElementById('serverDiskText').textContent = diskVal;
        document.getElementById('serverDiskBar').style.width = (sh.disk.percent || 0) + '%';
        document.getElementById('serverDiskDetails').textContent = sh.disk.used_gb ? `${sh.disk.used_gb} GB used of ${sh.disk.total_gb} GB` : 'Host Disk';

        document.getElementById('serverDbLatency').textContent = sh.database.latency_ms !== null ? sh.database.latency_ms + ' ms' : 'Unreachable';
        document.getElementById('serverDbConnections').textContent = sh.database.connections !== null ? sh.database.connections : '1';
        document.getElementById('serverUptimeDuration').textContent = sh.uptime.formatted;

        const badge = document.getElementById('serverStatusBadge');
        badge.className = 'badge ' + (sh.status === 'operational' ? 'bg-success' : (sh.status === 'warning' ? 'bg-warning text-dark' : 'bg-danger'));
        badge.textContent = sh.status.toUpperCase();

        // 5. Browser Web Vitals
        const vitals = data.performance.vitals;
        document.getElementById('vitalFcp').textContent = vitals.fcp > 0 ? vitals.fcp + ' ms' : 'Waiting';
        document.getElementById('vitalLcp').textContent = vitals.lcp > 0 ? vitals.lcp + ' ms' : 'Waiting';
        document.getElementById('vitalTtfb').textContent = vitals.ttfb > 0 ? vitals.ttfb + ' ms' : 'Waiting';
        document.getElementById('vitalDom').textContent = vitals.dom_loaded > 0 ? vitals.dom_loaded + ' ms' : 'Waiting';
        document.getElementById('vitalFullyLoaded').textContent = vitals.fully_loaded > 0 ? vitals.fully_loaded + ' ms' : 'Waiting';
        document.getElementById('vitalCls').textContent = vitals.cls !== null ? vitals.cls : '0.00';

        // 6. Live Visitors Activity Table
        const visTbody = document.getElementById('visitorTableBody');
        if (data.live_visitors && data.live_visitors.length > 0) {
            visTbody.innerHTML = data.live_visitors.map(v => `
                <tr class="visitor-row">
                    <td><span class="font-monospace text-info small">${v.session_hash}</span></td>
                    <td><span class="badge bg-dark border border-secondary text-white">${v.method}</span> <span class="text-white">${v.url}</span></td>
                    <td><i class="bi bi-${v.device_type === 'Mobile' ? 'phone' : (v.device_type === 'Tablet' ? 'tablet' : 'laptop')} me-1 text-muted"></i> ${v.device_type} &bull; ${v.browser}</td>
                    <td>${v.os}</td>
                    <td><span class="badge bg-secondary me-1">${v.country_code}</span> ${v.country_name}</td>
                    <td>${v.duration_formatted}</td>
                    <td><span class="badge bg-dark text-teal">${v.page_count}</span></td>
                    <td class="text-muted">${v.last_seen}</td>
                </tr>
            `).join('');
        } else {
            visTbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No active sessions browsing at this moment.</td></tr>';
        }

        // 7. API Endpoints Table
        const apiTbody = document.getElementById('apiTableBody');
        if (data.api_endpoints && data.api_endpoints.length > 0) {
            apiTbody.innerHTML = data.api_endpoints.map(a => `
                <tr>
                    <td><span class="badge bg-dark border border-secondary text-info me-1">${a.method}</span> <span class="text-white">${a.url}</span></td>
                    <td class="text-end fw-semibold">${a.total}</td>
                    <td class="text-end text-muted">${a.avg_duration_ms} ms</td>
                    <td class="text-end"><span class="badge ${a.status === 'Operational' ? 'bg-success' : 'bg-warning text-dark'}">${a.status}</span></td>
                </tr>
            `).join('');
        } else {
            apiTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No API requests recorded in this interval.</td></tr>';
        }

        // 8. Slow Pages Table
        const slowTbody = document.getElementById('slowPagesTableBody');
        if (data.performance.slow_pages && data.performance.slow_pages.length > 0) {
            slowTbody.innerHTML = data.performance.slow_pages.map(s => `
                <tr>
                    <td class="text-white">${s.url}</td>
                    <td class="text-end text-muted">${s.requests}</td>
                    <td class="text-end fw-semibold text-white">${s.avg_latency_ms} ms</td>
                    <td class="text-end"><span class="vital-badge vital-${s.classification}">${s.classification.toUpperCase()}</span></td>
                </tr>
            `).join('');
        } else {
            slowTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">All pages responding within optimal thresholds.</td></tr>';
        }

        // 9. Active Alerts
        const alertsDiv = document.getElementById('alertsContainer');
        if (data.alerts && data.alerts.length > 0) {
            alertsDiv.style.display = 'block';
            alertsDiv.innerHTML = data.alerts.map(al => `
                <div class="alert ${al.severity === 'critical' ? 'alert-danger' : 'alert-warning'} d-flex align-items-center justify-content-between p-3 rounded-3 mb-2 shadow-sm">
                    <div>
                        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> ${al.title}</strong>: ${al.message}
                        <div class="small text-muted mt-1">Affected: <strong>${al.affected_service || 'System'}</strong> &bull; Value: <strong>${al.actual_value}</strong></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-dark" onclick="acknowledgeAlert(${al.id})">Acknowledge</button>
                        <button class="btn btn-sm btn-success" onclick="resolveAlert(${al.id})">Resolve</button>
                    </div>
                </div>
            `).join('');
        } else {
            alertsDiv.style.display = 'none';
        }
    }

    // Interval Change Listener
    intervalSelect.addEventListener('change', function() {
        currentInterval = this.value;
        startRealtimeConnection();
    });

    // Pause / Resume Toggle
    pauseResumeBtn.addEventListener('click', function() {
        isPaused = !isPaused;
        if (isPaused) {
            pauseIcon.className = 'bi bi-play-fill me-1';
            pauseBtnText.textContent = 'Resume';
            this.classList.replace('btn-outline-light', 'btn-warning');
        } else {
            pauseIcon.className = 'bi bi-pause-fill me-1';
            pauseBtnText.textContent = 'Pause';
            this.classList.replace('btn-warning', 'btn-outline-light');
            fetchData();
        }
    });

    // Manual Refresh
    refreshBtn.addEventListener('click', function() {
        fetchData();
    });

    // Filter Visitor Activity Search
    document.getElementById('visitorSearchInput')?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.visitor-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });

    // Settings Form Submission (AJAX)
    document.getElementById('settingsForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveSettingsBtn');
        const alertBox = document.getElementById('settingsAlertBox');
        btn.disabled = true;

        const formData = new FormData(this);
        fetch("{{ route('staff.monitoring.settings.update') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            alertBox.className = 'alert alert-success small mt-3';
            alertBox.textContent = data.message || 'Settings saved successfully.';
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
                if (modal) modal.hide();
                alertBox.classList.add('d-none');
            }, 1200);
        })
        .catch(() => {
            btn.disabled = false;
            alertBox.className = 'alert alert-danger small mt-3';
            alertBox.textContent = 'Error saving settings. Please verify inputs.';
            alertBox.classList.remove('d-none');
        });
    });

    // Alert Handlers
    window.acknowledgeAlert = function(id) {
        fetch(`/staff/monitoring/alerts/${id}/acknowledge`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(() => fetchData());
    };

    window.resolveAlert = function(id) {
        fetch(`/staff/monitoring/alerts/${id}/resolve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(() => fetchData());
    };

    // Initial connection
    startRealtimeConnection();
});
</script>
@endsection
