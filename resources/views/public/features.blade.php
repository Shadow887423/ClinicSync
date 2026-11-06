@extends('layouts.app')

@section('title', 'Features & Capabilities — CLINICSYNC')

@section('content')
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-3 text-center max-w-800 mx-auto">
        <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-1 rounded-pill mb-2 fw-semibold" style="color: var(--secondary-teal); background-color: var(--teal-light);">Capabilities</span>
        <h1 class="fw-extrabold text-dark mb-3">System Features & Modules</h1>
        <p class="lead text-muted">
            Explore how CLINICSYNC unites patients, receptionists, and dentists on an automated scheduling platform.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <!-- Feature 1: Patient Self-Service -->
        <div class="row align-items-center g-5 mb-5 pb-4 border-bottom">
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2 fw-bold">Patient Experience</span>
                <h3 class="fw-bold text-dark mb-3">Online Booking & Patient Portal</h3>
                <p class="text-muted">
                    Patients can register an account, manage their verified mobile number, and submit appointment requests with their preferred dentist, date, and time slot.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-2 text-dark small">
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> 24/7 web booking without phone hold times</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Live status tracking: Pending, Confirmed, Completed, Cancelled</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Reschedule and cancellation requests with audit logging</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> View full history of SMS notifications received</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-light shadow-sm">
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-calendar-event me-2 text-primary"></i> Live Slot Availability Engine</h6>
                    <p class="small text-muted mb-3">Dynamically queries the doctor's weekly shift and booked slots to present only available slots.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-success p-2"><i class="bi bi-check-lg me-1"></i> 09:00 AM (Available)</span>
                        <span class="badge bg-success p-2"><i class="bi bi-check-lg me-1"></i> 09:30 AM (Available)</span>
                        <span class="badge bg-secondary p-2"><i class="bi bi-slash-circle me-1"></i> 10:00 AM (Booked)</span>
                        <span class="badge bg-success p-2"><i class="bi bi-check-lg me-1"></i> 10:30 AM (Available)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 2: Centralized Management Dashboard -->
        <div class="row align-items-center g-5 mb-5 pb-4 border-bottom flex-lg-row-reverse">
            <div class="col-lg-6">
                <span class="badge bg-info bg-opacity-10 text-dark mb-2 fw-bold">Clinic Administration</span>
                <h3 class="fw-bold text-dark mb-3">Centralized Triage & Master Calendar</h3>
                <p class="text-muted">
                    Staff manage all patient requests from a single queue. Review pending bookings, assign dentists, modify dates, and verify patient contact info.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-2 text-dark small">
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> One-click confirmation that triggers immediate SMS</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Multi-doctor master calendar with daily/weekly views</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Patient directory with complete visit logs</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Real-time analytics charts powered by Chart.js</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold small text-dark"><i class="bi bi-pie-chart-fill me-1 text-teal" style="color: var(--secondary-teal);"></i> Database-Driven Metrics</span>
                        <span class="badge bg-teal" style="background-color: var(--secondary-teal);">Real-time</span>
                    </div>
                    <div class="row g-2 text-center small">
                        <div class="col-4 p-2 bg-light rounded">
                            <div class="fw-bold fs-5 text-warning">Pending</div>
                            <div class="text-muted">Requests Queue</div>
                        </div>
                        <div class="col-4 p-2 bg-light rounded">
                            <div class="fw-bold fs-5 text-success">Confirmed</div>
                            <div class="text-muted">Active Bookings</div>
                        </div>
                        <div class="col-4 p-2 bg-light rounded">
                            <div class="fw-bold fs-5 text-primary">Completed</div>
                            <div class="text-muted">Fulfilled Visits</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 3: Automated SMS Notification Gateway -->
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-success bg-opacity-10 text-success mb-2 fw-bold">SMS Engine</span>
                <h3 class="fw-bold text-dark mb-3">Automated SMS Gateway Module</h3>
                <p class="text-muted">
                    Equipped with gateway adapters supporting live production SMS providers (Twilio, Semaphore, custom webhook) as well as a development simulator for research presentations.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-2 text-dark small">
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Instant Confirmation SMS on booking acceptance</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Automated 24h reminders run via Laravel Scheduler</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Notification history logs with delivery status tracking</li>
                    <li><i class="bi bi-check2-circle text-teal me-2 fs-6" style="color: var(--secondary-teal);"></i> Credential safety: No exposed API tokens in logs</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-dark text-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-3 text-info small">
                        <span><i class="bi bi-terminal-fill me-1"></i> Artisan Scheduler</span>
                        <span class="badge bg-info text-dark">Active</span>
                    </div>
                    <code class="text-light small d-block mb-2">$ php artisan appointments:send-reminders</code>
                    <div class="p-3 rounded bg-black bg-opacity-50 small text-success font-monospace">
                        Scanning confirmed appointments within 24h window...<br>
                        Processing reminder for [APT-20261029-001] John Doe...<br>
                        -> Reminder SMS dispatched via Gateway (Ref: SIM-4E5DDD01)<br>
                        Completed. 0 errors.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
