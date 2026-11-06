@extends('layouts.app')

@section('title', 'CLINICSYNC — Dental Appointment Scheduling & SMS Reminders')

@section('content')
<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #0f2744 0%, #1e3a8a 100%); color: #ffffff;">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 text-info small mb-3">
                    <i class="bi bi-broadcast"></i> Automated SMS Gateway Integration
                </div>
                <h1 class="display-4 fw-extrabold mb-3" style="letter-spacing: -1px; font-weight: 800;">
                    Smart Dental Appointment Scheduling with <span style="color: #2dd4bf;">Automated SMS Alerts</span>
                </h1>
                <p class="lead text-light-50 mb-4 pe-lg-4" style="color: #cbd5e1; font-size: 1.15rem;">
                    CLINICSYNC streamlines dental clinic operations by connecting patients, receptionists, and dentists through real-time schedule coordination and instant SMS confirmations and appointment reminders.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-teal btn-lg rounded-pill px-4 shadow">
                        <i class="bi bi-calendar-check me-2"></i> Book Appointment Now
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Patient & Staff Sign In
                    </a>
                </div>

                <div class="row g-4 mt-4 pt-3 border-top border-white border-opacity-10">
                    <div class="col-sm-4">
                        <div class="h3 fw-bold text-white mb-0">100%</div>
                        <div class="text-light-50 small" style="color: #94a3b8;">Automated SMS Confirmation</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="h3 fw-bold text-white mb-0">0%</div>
                        <div class="text-light-50 small" style="color: #94a3b8;">Double Booking Conflicts</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="h3 fw-bold text-white mb-0">24/7</div>
                        <div class="text-light-50 small" style="color: #94a3b8;">Self-Service Patient Access</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 text-dark" style="background: rgba(255, 255, 255, 0.98);">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-chat-heart text-teal fs-4" style="color: var(--secondary-teal);"></i>
                            <span class="fw-bold">SMS Live Simulation</span>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Delivered</span>
                    </div>

                    <div class="p-3 rounded-3 mb-3" style="background-color: #f1f5f9; border-left: 4px solid var(--secondary-teal);">
                        <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                            <span><i class="bi bi-phone me-1"></i> +1 (555) 987-6543</span>
                            <span>Just now</span>
                        </div>
                        <p class="small mb-0 text-dark">
                            "Hello <strong>John Doe</strong>, your dental appointment has been confirmed for <strong>Tomorrow at 10:00 AM</strong>. Dentist: <strong>Dr. Aris Thorne</strong>. Please contact the clinic if you need to reschedule. — CLINICSYNC"
                        </p>
                    </div>

                    <div class="p-3 rounded-3" style="background-color: #f8fafc; border-left: 4px solid #0284c7;">
                        <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                            <span><i class="bi bi-alarm me-1"></i> 24h Scheduled Reminder</span>
                            <span>Scheduled</span>
                        </div>
                        <p class="small mb-0 text-muted">
                            "Reminder: Hello John Doe, your dental appointment is scheduled for tomorrow at 10:00 AM. Please arrive 10 minutes early. — CLINICSYNC"
                        </p>
                    </div>

                    <div class="mt-4 pt-2 text-center">
                        <a href="{{ route('login') }}" class="btn btn-outline-teal w-100 rounded-pill">
                            Explore All Features & Demo Portals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-white">
    <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-1 rounded-pill mb-2 fw-semibold" style="color: var(--secondary-teal); background-color: var(--teal-light);">Streamlined Workflow</span>
            <h2 class="fw-bold text-dark">How CLINICSYNC Works</h2>
            <p class="text-muted">A clear, validated appointment lifecycle built for patients, administrative staff, and dentists.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card card-custom h-100 p-4 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-check fs-3" style="color: var(--secondary-teal);"></i>
                    </div>
                    <h5 class="fw-bold">1. Patient Requests Slot</h5>
                    <p class="text-muted small mb-0">The patient chooses a dentist, preferred service, date, and available time slot online 24/7.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-custom h-100 p-4 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-check fs-3 text-primary"></i>
                    </div>
                    <h5 class="fw-bold">2. Staff Verification</h5>
                    <p class="text-muted small mb-0">Clinic staff review the request, verify doctor availability, and confirm or modify the schedule.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-custom h-100 p-4 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-chat-quote fs-3 text-success"></i>
                    </div>
                    <h5 class="fw-bold">3. Automated SMS Sent</h5>
                    <p class="text-muted small mb-0">The patient instantly receives a branded text message confirmation with date, time, and assigned dentist.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-custom h-100 p-4 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-alarm fs-3 text-warning"></i>
                    </div>
                    <h5 class="fw-bold">4. 24h SMS Reminder</h5>
                    <p class="text-muted small mb-0">Automated queue workers dispatch reminder texts 24 hours prior to the appointment to reduce no-shows.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Clinic Dentists Section -->
<section class="py-5" style="background-color: #f8fafc;">
    <div class="container py-lg-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-1 rounded-pill mb-2 fw-semibold" style="color: var(--secondary-teal); background-color: var(--teal-light);">Medical Staff</span>
                <h2 class="fw-bold text-dark mb-1">Our Dental Specialists</h2>
                <p class="text-muted mb-0">Experienced dental practitioners available for scheduled appointments.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="{{ route('register') }}" class="btn btn-outline-teal rounded-pill">
                    Book an Appointment <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            @foreach($dentists as $dentist)
                <div class="col-md-4">
                    <div class="card card-custom h-100 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 56px; height: 56px; background-color: var(--primary-navy) !important;">
                                {{ substr($dentist->name, 4, 1) }}
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ $dentist->name }}</h5>
                                <div class="text-teal small fw-semibold" style="color: var(--secondary-teal);">{{ $dentist->specialization }}</div>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            {{ $dentist->bio ?? 'Dedicated to providing high quality preventive, restorative, and aesthetic oral healthcare.' }}
                        </p>

                        <div class="border-top pt-3 mt-auto">
                            <div class="small fw-semibold text-dark mb-1"><i class="bi bi-calendar3 me-1 text-primary"></i> Working Days:</div>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($dentist->availabilities as $avail)
                                    <span class="badge bg-light text-dark border">{{ $avail->day_name }}</span>
                                @empty
                                    <span class="badge bg-secondary">Schedule TBA</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5" style="background-color: var(--primary-navy); color: #ffffff;">
    <div class="container text-center py-4">
        <h2 class="fw-bold mb-3">Ready to Schedule Your Dental Visit?</h2>
        <p class="lead text-light-50 max-w-700 mx-auto mb-4" style="color: #cbd5e1;">
            Register in under two minutes, choose your preferred dentist, and receive real-time SMS updates directly on your mobile phone.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-teal btn-lg rounded-pill px-5">
                Create Patient Account
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                Staff Portal
            </a>
        </div>
    </div>
</section>
@endsection
