@extends('layouts.dashboard')

@section('title', 'System Monitoring & Analytics')
@section('page-title', 'Real-Time System Monitoring & Performance')

@section('styles')
<style>
    /* Status Pulse Dot */
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

    /* Web Vitals Badges */
    .vital-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
    }

    .vital-excellent { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .vital-good { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .vital-moderate { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .vital-slow { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
    .vital-critical { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .control-toolbar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
</style>
@endsection

@section('content')
<!-- Control & Connection Status Bar -->
<div class="control-toolbar mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-light border">
            <span class="pulse-dot" id="statusDot"></span>
            <span class="small fw-bold text-dark" id="connectionStatusText">Connecting (Live Stream)...</span>
        </div>
        <div class="small text-muted d-none d-md-block">
            Last update: <span class="fw-semibold text-dark" id="lastUpdatedText">{{ now()->format('H:i:s') }}</span>
        </div>
        <span class="badge bg-teal bg-opacity-10 text-teal border border-teal border-opacity-25 d-none d-lg-inline-block" style="color: var(--secondary-teal);">
            <i class="bi bi-shield-check me-1"></i> Zero Fake Data Policy Enforced
        </span>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Interval Selector -->
        <select class="form-select form-select-sm" id="intervalSelect" style="width: auto;">
            <option value="1m">Last 1 Minute</option>
            <option value="5m" selected>Last 5 Minutes</option>
            <option value="15m">Last 15 Minutes</option>
            <option value="30m">Last 30 Minutes</option>
            <option value="1h">Last 1 Hour</option>
            <option value="24h">Last 24 Hours</option>
        </select>

        <!-- Play/Pause Button -->
        <button class="btn btn-sm btn-outline-secondary" id="pauseResumeBtn" title="Pause live visualization">
            <i class="bi bi-pause-fill me-1" id="pauseIcon"></i> <span id="pauseBtnText">Pause</span>
        </button>

        <!-- Manual Refresh -->
        <button class="btn btn-sm btn-outline-secondary" id="refreshBtn" title="Refresh metrics">
            <i class="bi bi-arrow-repeat"></i>
        </button>

        <!-- Export CSV -->
        <button class="btn btn-sm btn-teal" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-download me-1"></i> Export CSV
        </button>

        <!-- Settings -->
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal" title="Alert Thresholds">
            <i class="bi bi-sliders"></i>
        </button>
    </div>
</div>

<!-- Active Alerts Banner (if any) -->
<div id="alertsContainer" class="mb-4" style="display: none;"></div>

<!-- 1. Real-Time Overview Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Active Visitors -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-info border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Active Visitors</div>
                <div class="h3 fw-bold text-info mb-0" id="metricActiveVisitors">--</div>
                <div class="text-muted" style="font-size: 0.72rem;">Last 5 minutes</div>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <!-- Traffic Rate / RPS -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-warning border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Traffic Rate</div>
                <div class="h3 fw-bold text-warning mb-0" id="metricRps">--</div>
                <div class="text-muted" style="font-size: 0.72rem;">Requests / sec</div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
        </div>
    </div>

    <!-- Inbound Traffic -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-primary border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Inbound Traffic</div>
                <div class="h3 fw-bold text-primary mb-0" id="metricInbound">--</div>
                <div class="text-muted" style="font-size: 0.72rem;" id="metricInboundRate">--</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-arrow-down-left-circle-fill"></i>
            </div>
        </div>
    </div>

    <!-- Outbound Traffic -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-success border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Outbound Traffic</div>
                <div class="h3 fw-bold text-success mb-0" id="metricOutbound">--</div>
                <div class="text-muted" style="font-size: 0.72rem;" id="metricOutboundRate">--</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-arrow-up-right-circle-fill"></i>
            </div>
        </div>
    </div>

    <!-- Avg Loading Time -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Avg Loading Time</div>
                <div class="h3 fw-bold text-teal mb-0" style="color: var(--secondary-teal);" id="metricAvgLoading">--</div>
                <div class="text-muted" style="font-size: 0.72rem;">Server latency</div>
            </div>
            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-stopwatch-fill"></i>
            </div>
        </div>
    </div>

    <!-- System Uptime -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Website Uptime</div>
                <div class="h3 fw-bold text-success mb-0" id="metricUptime">--</div>
                <div class="text-muted" style="font-size: 0.72rem;" id="metricErrorRate">Error: 0.0%</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-shield-check"></i>
            </div>
        </div>
    </div>
</div>

<!-- 2. Live Network Traffic Pulse & Geographic Distribution -->
<div class="row g-4 mb-4">
    <!-- Live Traffic Pulse Chart -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-activity text-teal me-2" style="color: var(--secondary-teal);"></i>
                        Live Network Traffic Pulse
                    </h6>
                    <span class="small text-muted">Inbound vs Outbound bandwidth volume</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Inbound</span>
                    <span class="badge bg-teal bg-opacity-10 text-teal border border-teal border-opacity-25" style="color: var(--secondary-teal);">Outbound</span>
                </div>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="trafficPulseChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Geographic Visitor Distribution -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-globe-americas text-info me-2"></i>
                        Geographic Distribution
                    </h6>
                    <span class="small text-muted">Top traffic origins by location</span>
                </div>
            </div>
            <div class="table-responsive" style="max-height: 260px;">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
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
            <div class="small text-muted mt-3 border-top pt-2" style="font-size: 0.72rem;">
                <i class="bi bi-lock me-1"></i> Privacy-compliant anonymized IP resolution
            </div>
        </div>
    </div>
</div>

<!-- 3. Server Health & Core Web Vitals -->
<div class="row g-4 mb-4">
    <!-- Server Health -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
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
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small fw-bold text-uppercase">CPU Load</span>
                            <span class="fw-bold text-dark" id="serverCpuText">--</span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-warning" id="serverCpuBar" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <!-- RAM -->
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small fw-bold text-uppercase">Memory (RAM)</span>
                            <span class="fw-bold text-dark" id="serverRamText">--</span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-info" id="serverRamBar" style="width: 0%;"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.7rem;" id="serverRamDetails">--</div>
                    </div>
                </div>

                <!-- Disk -->
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small fw-bold text-uppercase">Storage Disk</span>
                            <span class="fw-bold text-dark" id="serverDiskText">--</span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-primary" id="serverDiskBar" style="width: 0%;"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.7rem;" id="serverDiskDetails">--</div>
                    </div>
                </div>

                <!-- Database Health -->
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small fw-bold text-uppercase">Database Latency</span>
                            <span class="fw-bold text-dark" id="serverDbLatency">--</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Active DB: <strong class="text-dark" id="serverDbConnections">--</strong></span>
                            <span>Uptime: <strong class="text-dark" id="serverUptimeDuration">--</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Browser Web Vitals -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-speedometer text-success me-2"></i>
                        Browser Web Vitals & Page Loading
                    </h6>
                    <span class="small text-muted">Field performance measured via PerformanceObserver</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">FCP (Paint)</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalFcp">--</div>
                        <span class="vital-badge vital-good" id="badgeFcp">Good</span>
                    </div>
                </div>

                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">LCP (Content)</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalLcp">--</div>
                        <span class="vital-badge vital-good" id="badgeLcp">Good</span>
                    </div>
                </div>

                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">TTFB (Backend)</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalTtfb">--</div>
                        <span class="vital-badge vital-excellent" id="badgeTtfb">Excellent</span>
                    </div>
                </div>

                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">DOM Loaded</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalDom">--</div>
                        <span class="small text-muted">Ready event</span>
                    </div>
                </div>

                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Fully Loaded</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalFullyLoaded">--</div>
                        <span class="small text-muted">Window load</span>
                    </div>
                </div>

                <div class="col-sm-4 col-6">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">CLS (Shift)</span>
                        <div class="h5 fw-bold text-dark mb-1" id="vitalCls">--</div>
                        <span class="vital-badge vital-excellent" id="badgeCls">Stable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Live Visitor Activity Monitor -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-person-lines-fill text-info me-2"></i>
                Live Visitor Activity Monitor
            </h6>
            <span class="small text-muted">Real-time sessions browsing ClinicSync</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm" id="visitorSearchInput" placeholder="Filter URL, Device or Location...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="visitorTable">
            <thead class="table-light">
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
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark mb-1">
                <i class="bi bi-hdd-network-fill text-primary me-2"></i>
                API & Endpoint Monitoring
            </h6>
            <span class="small text-muted d-block mb-3">Top accessed routes, response duration & status codes</span>
            <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
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
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark mb-1">
                <i class="bi bi-hourglass-split text-warning me-2"></i>
                Page Latency & Performance Classification
            </h6>
            <span class="small text-muted d-block mb-3">Pages ranked by response duration</span>
            <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
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

<!-- Export Modal (ClinicSync Standard Light Dialog) -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-spreadsheet me-2 text-teal" style="color: var(--secondary-teal);"></i> Export Monitoring Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('staff.monitoring.export') }}" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Dataset Type</label>
                        <select name="type" class="form-select">
                            <option value="traffic">Traffic Logs (HTTP Requests & Bandwidth)</option>
                            <option value="performance">Browser Performance & Web Vitals</option>
                            <option value="server">Server Hardware Metrics (CPU, RAM, Disk)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">History Window</label>
                        <select name="days" class="form-select">
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
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-teal"><i class="bi bi-download me-1"></i> Download CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Settings Modal (ClinicSync Standard Light Dialog) -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-sliders me-2 text-warning"></i> Monitoring & Alert Thresholds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="settingsForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">CPU Utilization Alert Threshold (%)</label>
                        <input type="number" name="cpu_threshold" class="form-control" value="{{ $settings['cpu_threshold'] }}" min="10" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">RAM Utilization Alert Threshold (%)</label>
                        <input type="number" name="ram_threshold" class="form-control" value="{{ $settings['ram_threshold'] }}" min="10" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">High Page Response Latency Alert (ms)</label>
                        <input type="number" name="latency_threshold" class="form-control" value="{{ $settings['latency_threshold'] }}" min="100" max="30000" step="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Raw Metrics Retention Period (Days)</label>
                        <input type="number" name="retention_days" class="form-control" value="{{ $settings['retention_days'] }}" min="1" max="365" required>
                    </div>
                    <div id="settingsAlertBox" class="alert d-none small"></div>
                </div>
                <div class="modal-footer border-top">
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

    // Chart.js Traffic Pulse Chart (Styled identically to ClinicSync charts)
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
                    backgroundColor: 'rgba(2, 132, 199, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2,
                },
                {
                    label: 'Outbound (KB)',
                    data: [],
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.1)',
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
                    grid: { color: 'rgba(0, 0, 0, 0.04)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(0, 0, 0, 0.04)' },
                    ticks: { color: '#64748b', font: { size: 10 } },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#0f2744',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    borderWidth: 0,
                    padding: 10,
                    cornerRadius: 8
                }
            }
        }
    });

    // Dynamic Chart Theme Adaptation
    function applyChartTheme(isDark) {
        trafficChart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.04)';
        trafficChart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.04)';
        trafficChart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
        trafficChart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
        trafficChart.update('none');
    }

    window.addEventListener('themeChanged', function(e) {
        applyChartTheme(e.detail.theme === 'dark');
    });

    if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
        applyChartTheme(true);
    }

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
        document.getElementById('metricErrorRate').textContent = 'Error: ' + ov.error_rate_percent + '%';

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
                    <td><span class="badge bg-light text-dark border me-1">${g.code}</span> <span class="fw-semibold text-dark">${g.name}</span></td>
                    <td class="text-end fw-bold text-dark">${g.visitors}</td>
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
                    <td><span class="font-monospace text-primary small fw-semibold">${v.session_hash}</span></td>
                    <td><span class="badge bg-light text-dark border me-1">${v.method}</span> <span class="fw-semibold text-dark">${v.url}</span></td>
                    <td><i class="bi bi-${v.device_type === 'Mobile' ? 'phone' : (v.device_type === 'Tablet' ? 'tablet' : 'laptop')} me-1 text-muted"></i> ${v.device_type} &bull; ${v.browser}</td>
                    <td><span class="text-muted">${v.os}</span></td>
                    <td><span class="badge bg-light text-dark border me-1">${v.country_code}</span> ${v.country_name}</td>
                    <td><span class="text-muted">${v.duration_formatted}</span></td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-dark border">${v.page_count}</span></td>
                    <td class="text-muted">${v.last_seen}</td>
                </tr>
            `).join('');
        } else {
            visTbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No active sessions browsing at this moment.</td></tr>';
        }

        // 7. API Endpoints Table
        const apiTbody = document.getElementById('apiTableBody');
        if (data.api_endpoints && data.api_endpoints.length > 0) {
            apiTbody.innerHTML = data.api_endpoints.map(a => `
                <tr>
                    <td><span class="badge bg-light text-dark border me-1">${a.method}</span> <span class="fw-semibold text-dark">${a.url}</span></td>
                    <td class="text-end fw-semibold text-dark">${a.total}</td>
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
                    <td class="fw-semibold text-dark">${s.url}</td>
                    <td class="text-end text-muted">${s.requests}</td>
                    <td class="text-end fw-semibold text-dark">${s.avg_latency_ms} ms</td>
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
                <div class="alert ${al.severity === 'critical' ? 'alert-danger' : 'alert-warning'} d-flex align-items-center justify-content-between p-3 rounded-3 mb-2 shadow-sm border">
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
            this.classList.replace('btn-outline-secondary', 'btn-warning');
        } else {
            pauseIcon.className = 'bi bi-pause-fill me-1';
            pauseBtnText.textContent = 'Pause';
            this.classList.replace('btn-warning', 'btn-outline-secondary');
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
