@extends('layouts.dashboard')

@section('title', 'SMS Delivery Logs')
@section('page-title', 'SMS Notification Audit & Delivery Logs')

@section('content')
<div class="card card-custom p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-broadcast text-teal me-2" style="color: var(--secondary-teal);"></i> SMS Notification Audit Trail
            </h5>
            <p class="text-muted small mb-0">Live delivery status, gateway references, and dispatch logs for all automated messages.</p>
        </div>

        <a href="{{ route('staff.settings') }}" class="btn btn-outline-teal rounded-pill btn-sm">
            <i class="bi bi-gear-fill me-1"></i> SMS Gateway Configuration
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('staff.notifications.index') }}" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search phone, message, ref..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Notification Types</option>
                    <option value="confirmation" {{ request('type') === 'confirmation' ? 'selected' : '' }}>Confirmation</option>
                    <option value="reminder" {{ request('type') === 'reminder' ? 'selected' : '' }}>Reminder (24h)</option>
                    <option value="rescheduled" {{ request('type') === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                    <option value="cancellation" {{ request('type') === 'cancellation' ? 'selected' : '' }}>Cancellation</option>
                    <option value="rejection" {{ request('type') === 'rejection' ? 'selected' : '' }}>Rejection</option>
                    <option value="general" {{ request('type') === 'general' ? 'selected' : '' }}>General / Test</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="simulated" {{ request('status') === 'simulated' ? 'selected' : '' }}>Simulated (Dev)</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-navy flex-grow-1">Filter</button>
                <a href="{{ route('staff.notifications.index') }}" class="btn btn-sm btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </form>

    <!-- Logs Table -->
    @if($notifications->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Recipient Mobile</th>
                        <th>Related Apt</th>
                        <th>Message Body</th>
                        <th>Status</th>
                        <th>Gateway & Ref</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $sms)
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-bold">{{ $sms->created_at->format('M d, Y') }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $sms->created_at->format('g:i A') }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $sms->status_badge_class }}">
                                    {{ ucfirst($sms->notification_type) }}
                                </span>
                            </td>
                            <td class="fw-bold text-dark">
                                <i class="bi bi-phone me-1 text-primary"></i> {{ $sms->recipient_phone }}
                            </td>
                            <td>
                                @if($sms->appointment)
                                    <a href="{{ route('staff.appointments.show', $sms->appointment) }}" class="text-decoration-none">
                                        <code>{{ $sms->appointment->appointment_number }}</code>
                                    </a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                <div class="p-2 bg-light rounded text-dark" style="max-width: 320px; font-size: 0.75rem;">
                                    {{ $sms->message }}
                                </div>
                                @if($sms->error_message)
                                    <div class="text-danger small mt-1">
                                        <strong>Error:</strong> {{ $sms->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $sms->status_badge_class }}">
                                    {{ ucfirst($sms->status) }}
                                </span>
                            </td>
                            <td>
                                <div><span class="badge bg-light text-dark border">{{ ucfirst($sms->gateway) }}</span></div>
                                <code class="small text-muted">{{ $sms->gateway_reference ?? 'None' }}</code>
                            </td>
                            <td class="text-end">
                                @if($sms->status === 'failed')
                                    <form action="{{ route('staff.notifications.retry', $sms) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Retry Sending">
                                            <i class="bi bi-arrow-repeat"></i> Retry
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check-lg text-success"></i> Dispatched</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat-left-dots fs-1 opacity-50 d-block mb-3"></i>
            <h5>No SMS records match your filter criteria.</h5>
        </div>
    @endif
</div>
@endsection
