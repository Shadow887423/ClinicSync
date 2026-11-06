@extends('layouts.app')

@section('title', 'About CLINICSYNC — System Overview & Architecture')

@section('content')
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-1 rounded-pill mb-2 fw-semibold" style="color: var(--secondary-teal); background-color: var(--teal-light);">Academic Research Project</span>
                <h1 class="fw-extrabold text-dark mb-3">About CLINICSYNC</h1>
                <p class="lead text-muted">
                    CLINICSYNC is a cloud-based dental appointment scheduling and automated SMS notification system designed to eliminate scheduling friction, optimize chair-time utilization, and minimize patient no-shows.
                </p>
            </div>
            <div class="col-lg-5 text-center">
                <div class="p-4 rounded-4 shadow-sm border bg-light">
                    <i class="bi bi-diagram-3-fill display-3 text-primary mb-3"></i>
                    <h5 class="fw-bold">Centralized Architecture</h5>
                    <p class="small text-muted mb-0">Unified relational database linking Patients, Dentists, Administrative Staff, and SMS Gateways with ACID-compliant transactions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100 p-4">
                    <div class="text-teal mb-3 fs-3" style="color: var(--secondary-teal);"><i class="bi bi-bullseye"></i></div>
                    <h5 class="fw-bold">Problem Statement</h5>
                    <p class="text-muted small">
                        Traditional dental clinics rely on manual phone calls, paper appointment books, and fragmented messaging. This leads to double-bookings, miscommunications, and missed appointments that waste costly clinical resources.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 p-4">
                    <div class="text-primary mb-3 fs-3"><i class="bi bi-lightbulb-fill"></i></div>
                    <h5 class="fw-bold">Proposed Solution</h5>
                    <p class="text-muted small">
                        A web application incorporating patient online booking, staff schedule triage, concurrency-controlled slot allocation, and automated SMS confirmations and reminders delivered right to the patient's phone.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 p-4">
                    <div class="text-success mb-3 fs-3"><i class="bi bi-code-slash"></i></div>
                    <h5 class="fw-bold">Technical Stack</h5>
                    <p class="text-muted small">
                        Engineered with PHP (Laravel 9 MVC), MariaDB/MySQL database, Bootstrap 5 UI, and an adaptable SMS Gateway adapter pattern (supporting Twilio, Semaphore, and simulated testbed drivers).
                    </p>
                </div>
            </div>
        </div>

        <div class="card card-custom mt-5 p-4 p-lg-5">
            <h4 class="fw-bold mb-3"><i class="bi bi-check2-all text-success me-2"></i> Core Research Principles & Scope</h4>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-teal fs-5" style="color: var(--secondary-teal);"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Concurrency & Conflict Prevention</h6>
                            <p class="small text-muted">Database-level row locks and validation prevent overlapping confirmed bookings for the same dentist and slot.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-teal fs-5" style="color: var(--secondary-teal);"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Decoupled SMS Notification Engine</h6>
                            <p class="small text-muted">Automated triggers on confirmation and scheduled queue runners for 24h reminders, with delivery status logging and safe credential handling.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-teal fs-5" style="color: var(--secondary-teal);"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Strict Role-Based Access Control (RBAC)</h6>
                            <p class="small text-muted">Three distinct personas (Patient, Clinic Staff/Admin, Dentist) with isolated dashboards and backend authorization middleware.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-teal fs-5" style="color: var(--secondary-teal);"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Clinical Scope Boundary</h6>
                            <p class="small text-muted">Focused strictly on appointment management and patient notifications; non-clinical boundaries exclude diagnostic tools or prescription writing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
