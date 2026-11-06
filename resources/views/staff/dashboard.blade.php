@extends('layouts.dashboard')

@section('title', 'Staff Dashboard')
@section('page-title', 'Clinic Administration & Appointment Dashboard')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Total Bookings</div>
                <div class="h3 fw-bold text-dark mb-0">{{ $metrics['total'] }}</div>
            </div>
            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-folder2-open"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-warning border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Pending Requests</div>
                <div class="h3 fw-bold text-warning mb-0">{{ $metrics['pending'] }}</div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-hourglass-top"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-success border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Confirmed</div>
                <div class="h3 fw-bold text-success mb-0">{{ $metrics['confirmed'] }}</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-calendar-check"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="stat-card border-primary border-opacity-50">
            <div>
                <div class="text-muted small fw-semibold">Today's Visits</div>
                <div class="h3 fw-bold text-primary mb-0">{{ $metrics['today'] }}</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar-day"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Completed</div>
                <div class="h3 fw-bold text-teal mb-0" style="color: var(--secondary-teal);">{{ $metrics['completed'] }}</div>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-check2-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Cancelled</div>
                <div class="h3 fw-bold text-danger mb-0">{{ $metrics['cancelled'] }}</div>
            </div>
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-x-octagon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Database-Driven Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Upcoming 7-Day Appointment Schedule Volume</h6>
                <span class="badge bg-light text-muted border">Dynamic Database Query</span>
            </div>
            <div style="height: 250px;">
                <canvas id="volumeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart-fill text-teal me-2" style="color: var(--secondary-teal);"></i> Status Breakdown</h6>
                <span class="badge bg-light text-muted border">All-Time</span>
            </div>
            <div style="height: 250px;" class="d-flex align-items-center justify-content-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Pending Requests Review Queue -->
    <div class="col-lg-7">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-exclamation-circle-fill text-warning me-2"></i> Pending Requests Triage Queue
                </h5>
                <a href="{{ route('staff.appointments.index', ['status' => 'pending']) }}" class="small text-teal fw-semibold">View All</a>
            </div>
            <p class="small text-muted mb-3">Online requests awaiting staff confirmation or schedule modification:</p>

            @if($pendingQueue->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Service</th>
                                <th>Requested Slot</th>
                                <th>Dentist</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingQueue as $apt)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $apt->patient ? $apt->patient->name : 'Patient' }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-phone"></i> {{ $apt->patient ? $apt->patient->phone : 'No Phone' }}</div>
                                    </td>
                                    <td class="fw-semibold">{{ $apt->service_type }}</td>
                                    <td>
                                        <div class="fw-bold">{{ \Carbon\Carbon::parse($apt->requested_date)->format('M d, Y') }}</div>
                                        <div class="text-muted">{{ \Carbon\Carbon::parse($apt->requested_time)->format('g:i A') }}</div>
                                    </td>
                                    <td>{{ $apt->dentist ? $apt->dentist->name : 'Unassigned' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('staff.appointments.show', $apt) }}" class="btn btn-sm btn-teal rounded-pill px-3">
                                            Review & Confirm
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                    All appointment requests have been processed. The pending queue is clear!
                </div>
            @endif
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="col-lg-5">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-calendar2-day-fill text-primary me-2"></i> Today's Schedule
                </h5>
                <span class="badge bg-primary">{{ \Carbon\Carbon::today()->format('M d, Y') }}</span>
            </div>

            @if($todayAppointments->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($todayAppointments as $apt)
                        <div class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center small">
                            <div>
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-dark me-1">{{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}</span>
                                    {{ $apt->patient ? $apt->patient->name : 'Patient' }}
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    {{ $apt->service_type }} &bull; {{ $apt->dentist ? $apt->dentist->name : 'Unassigned' }}
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('staff.appointments.show', $apt) }}" class="btn btn-sm btn-light border rounded-pill">
                                    Manage
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-calendar-event fs-3 opacity-50 d-block mb-2"></i>
                    No appointments scheduled for today.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Recent SMS Activity Feed -->
<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="bi bi-broadcast text-teal me-2" style="color: var(--secondary-teal);"></i> Recent Automated SMS Gateway Activity
        </h5>
        <a href="{{ route('staff.notifications.index') }}" class="small text-teal fw-semibold">View All SMS Logs</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Recipient</th>
                    <th>Message Text</th>
                    <th>Status</th>
                    <th>Gateway Ref</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSms as $sms)
                    <tr>
                        <td class="text-nowrap">{{ $sms->created_at->format('M d, g:i A') }}</td>
                        <td><span class="badge {{ $sms->status_badge_class }}">{{ ucfirst($sms->notification_type) }}</span></td>
                        <td class="fw-bold">{{ $sms->recipient_phone }}</td>
                        <td>
                            <div class="text-truncate" style="max-width: 380px;">{{ $sms->message }}</div>
                        </td>
                        <td><span class="badge {{ $sms->status_badge_class }}">{{ ucfirst($sms->status) }}</span></td>
                        <td><code>{{ $sms->gateway_reference ?? 'N/A' }}</code></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No SMS transactions recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartStatusLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartStatusValues) !!},
                    backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#64748b', '#ef4444'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });

        // 7-Day Volume Bar Chart
        const volumeCtx = document.getElementById('volumeChart').getContext('2d');
        new Chart(volumeCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($next7DaysLabels) !!},
                datasets: [{
                    label: 'Scheduled Appointments',
                    data: {!! json_encode($next7DaysValues) !!},
                    backgroundColor: '#0d9488',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>
@endsection
