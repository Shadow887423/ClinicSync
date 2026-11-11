<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CLINICSYNC') — Dental Appointment & SMS Notification System</title>
    
    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-navy: #0f2744;
            --primary-navy-dark: #0a1b30;
            --secondary-teal: #0d9488;
            --teal-hover: #0f766e;
            --teal-light: #ccfbf1;
            --accent-soft-blue: #0284c7;
            --accent-light: #e0f2fe;
            --bg-canvas: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-brand-clinicsync {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.5px;
            color: var(--primary-navy) !important;
        }

        .navbar-brand-clinicsync span {
            color: var(--secondary-teal);
        }

        .btn-primary-navy {
            background-color: var(--primary-navy);
            color: #ffffff;
            border: 1px solid var(--primary-navy);
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary-navy:hover {
            background-color: var(--primary-navy-dark);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 39, 68, 0.2);
        }

        .btn-teal {
            background-color: var(--secondary-teal);
            color: #ffffff;
            border: 1px solid var(--secondary-teal);
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-teal:hover {
            background-color: var(--teal-hover);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
        }

        .btn-outline-teal {
            color: var(--secondary-teal);
            border: 1.5px solid var(--secondary-teal);
            font-weight: 600;
        }

        .btn-outline-teal:hover {
            background-color: var(--secondary-teal);
            color: #ffffff;
        }

        .navbar-public {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .nav-link {
            font-weight: 500;
            color: #475569;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--secondary-teal);
        }

        .card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-custom:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }

        .footer-public {
            background-color: var(--primary-navy-dark);
            color: #cbd5e1;
            margin-top: auto;
        }

        .footer-public a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-public a:hover {
            color: #ffffff;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Public Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-public sticky-top py-3">
        <div class="container">
            <a class="navbar-brand navbar-brand-clinicsync d-flex align-items-center" href="{{ route('home') }}">
                <div class="bg-primary text-white rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background-color: var(--primary-navy) !important;">
                    <i class="bi bi-shield-plus fs-5 text-info"></i>
                </div>
                CLINIC<span>SYNC</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About System</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('features') ? 'active' : '' }}" href="{{ route('features') }}">Features & Workflow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact Clinic</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    @auth
                        @if(Auth::user()->isPatient())
                            <a href="{{ route('patient.dashboard') }}" class="btn btn-teal rounded-pill px-4">
                                <i class="bi bi-grid-fill me-1"></i> Patient Portal
                            </a>
                        @elseif(Auth::user()->isDentist())
                            <a href="{{ route('dentist.dashboard') }}" class="btn btn-primary-navy rounded-pill px-4">
                                <i class="bi bi-stethoscope me-1"></i> Dentist Portal
                            </a>
                        @else
                            <a href="{{ route('staff.dashboard') }}" class="btn btn-primary-navy rounded-pill px-4">
                                <i class="bi bi-speedometer2 me-1"></i> Staff Portal
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-teal rounded-pill px-3 py-1">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-teal rounded-pill px-4 py-1">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @if(session('success'))
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="container mt-3">
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer-public py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: var(--secondary-teal) !important;">
                            <i class="bi bi-shield-plus text-white"></i>
                        </div>
                        <h5 class="text-white mb-0 fw-bold">CLINICSYNC</h5>
                    </div>
                    <p class="text-light-50 small pe-lg-4">
                        Cloud-based dental appointment scheduling platform with automated SMS notifications. Designed for academic demonstration, clinical efficiency, and real-time patient coordination.
                    </p>
                    <div class="d-flex gap-3 text-secondary">
                        <span class="badge bg-secondary"><i class="bi bi-check2-circle me-1"></i> SMS Gateway Integrated</span>
                        <span class="badge bg-secondary"><i class="bi bi-shield-lock me-1"></i> Role-Based Access</span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Quick Navigation</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('home') }}">Home Page</a></li>
                        <li><a href="{{ route('about') }}">About CLINICSYNC</a></li>
                        <li><a href="{{ route('features') }}">Workflow Features</a></li>
                        <li><a href="{{ route('contact') }}">Contact Clinic</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Portals</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('login') }}">Patient Login</a></li>
                        <li><a href="{{ route('register') }}">Patient Registration</a></li>
                        <li><a href="{{ route('login') }}">Staff / Admin Portal</a></li>
                        <li><a href="{{ route('login') }}">Dentist Schedule</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Clinic Schedule</h6>
                    <p class="small text-light-50 mb-1"><i class="bi bi-calendar3 me-2 text-info"></i> Monday – Friday: 9:00 AM – 5:00 PM</p>
                    <p class="small text-light-50 mb-1"><i class="bi bi-calendar3 me-2 text-info"></i> Saturday: 9:00 AM – 1:00 PM</p>
                    <p class="small text-light-50 mb-0"><i class="bi bi-telephone me-2 text-info"></i> +1 (555) 123-CLINIC</p>
                </div>
            </div>

            <hr class="my-4 border-secondary opacity-25">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-light-50">
                <div>&copy; {{ date('Y') }} CLINICSYNC Dental System. Academic Capstone & Research Project.</div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-dark border border-secondary text-info">
                        <i class="bi bi-broadcast me-1"></i> SMS Engine: Active
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.performance-observer')
    @yield('scripts')
</body>
</html>
