@extends('layouts.dashboard')

@section('title', 'My SMS Notification History')
@section('page-title', 'SMS Notification History')

@section('content')
<div class="card card-custom p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-chat-text-fill text-teal me-2" style="color: var(--secondary-teal);"></i> Automated SMS Alerts</h5>
            <p class="text-muted small mb-0">Complete historical record of all text messages dispatched to your registered mobile number ({{ Auth::user()->phone }}).</p>
        </div>
        <a href="{{ route('patient.profile') }}" class="btn btn-sm btn-outline-teal rounded-pill">
            <i class="bi bi-phone me-1"></i> Update Mobile Number
        </a>
    </div>

    @if($notifications->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Date & Time</th>
                        <th>Alert Type</th>
                        <th>SMS Message Body</th>
                        <th>Delivery Status</th>
                        <th>Gateway Ref</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $sms)
                        <tr>
                            <td class="text-nowrap small">
                                <div class="fw-bold text-dark">{{ $sms->created_at->format('M d, Y') }}</div>
                                <div class="text-muted">{{ $sms->created_at->format('g:i A') }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $sms->status_badge_class }}">
                                    {{ ucfirst($sms->notification_type) }}
                                </span>
                            </td>
                            <td>
                                <div class="p-2 bg-light rounded text-dark small border" style="max-width: 500px;">
                                    {{ $sms->message }}
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $sms->status_badge_class }}">
                                    <i class="bi bi-check2-circle me-1"></i> {{ ucfirst($sms->status) }}
                                </span>
                            </td>
                            <td>
                                <code class="small text-muted">{{ $sms->gateway_reference ?? 'N/A' }}</code>
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
            <i class="bi bi-chat-square-dots fs-1 opacity-50 d-block mb-3"></i>
            <h5 class="fw-bold">No SMS Notifications Dispatched Yet</h5>
            <p class="small mb-0">Whenever an appointment is confirmed, modified, or scheduled for a reminder, you will see the recorded message here.</p>
        </div>
    @endif
</div>
@endsection
