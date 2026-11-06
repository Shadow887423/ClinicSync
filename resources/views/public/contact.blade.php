@extends('layouts.app')

@section('title', 'Contact Information — CLINICSYNC')

@section('content')
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-3 text-center max-w-700 mx-auto">
        <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-1 rounded-pill mb-2 fw-semibold" style="color: var(--secondary-teal); background-color: var(--teal-light);">Get in Touch</span>
        <h1 class="fw-extrabold text-dark mb-3">Contact Dental Clinic</h1>
        <p class="lead text-muted">
            Have questions regarding our services or your appointment schedule? Reach out to our receptionist team.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="card card-custom p-4 p-lg-5 h-100" style="background-color: var(--primary-navy); color: #ffffff;">
                    <h4 class="fw-bold mb-4 text-white">Clinic Details</h4>
                    
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle bg-white bg-opacity-10 p-3 text-info">
                            <i class="bi bi-geo-alt-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-white">Clinic Location</h6>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">Suite 400, Healthcare Tower, Medical Plaza Way</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle bg-white bg-opacity-10 p-3 text-info">
                            <i class="bi bi-telephone-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-white">Phone & SMS Inquiries</h6>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">+1 (555) 123-CLINIC (4567)</p>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">SMS Dispatch Center: +1 (555) 019-SYNC</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle bg-white bg-opacity-10 p-3 text-info">
                            <i class="bi bi-envelope-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-white">Email Address</h6>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">appointments@clinicsync.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle bg-white bg-opacity-10 p-3 text-info">
                            <i class="bi bi-clock-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-white">Operating Hours</h6>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">Mon – Fri: 9:00 AM – 5:00 PM</p>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">Saturday: 9:00 AM – 1:00 PM</p>
                            <p class="small text-light-50 mb-0" style="color: #cbd5e1;">Sunday: Closed for maintenance</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-custom p-4 p-lg-5">
                    <h4 class="fw-bold mb-3 text-dark">Send an Inquiry</h4>
                    <p class="text-muted small mb-4">For immediate appointment booking, please <a href="{{ route('register') }}" class="text-teal fw-semibold">create an account online</a> or call the front desk.</p>

                    <form onsubmit="event.preventDefault(); alert('Inquiry received. For appointment bookings, please register or sign in to your patient account.');">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your Full Name</label>
                                <input type="text" class="form-control" placeholder="e.g. John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile Phone (for SMS reply)</label>
                                <input type="text" class="form-control" placeholder="+15551234567" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <input type="email" class="form-control" placeholder="you@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Message / Question</label>
                                <textarea class="form-control" rows="4" placeholder="How can our clinical team assist you?" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-teal rounded-pill px-4">
                                    <i class="bi bi-send me-1"></i> Send Inquiry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
