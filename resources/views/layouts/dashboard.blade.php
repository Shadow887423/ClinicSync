<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CLINICSYNC</title>

    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --primary-navy: #0f2744;
            --primary-navy-dark: #0a1b30;
            --secondary-teal: #0d9488;
            --teal-hover: #0f766e;
            --teal-light: #ccfbf1;
            --accent-soft-blue: #0284c7;
            --accent-light: #e0f2fe;
            --bg-canvas: #f1f5f9;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-canvas);
            color: #1e293b;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0f2744 0%, #091729 100%);
            color: #e2e8f0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            transition: all 0.3s;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .sidebar-brand span {
            color: #2dd4bf;
        }

        .sidebar-menu {
            padding: 1rem 0.75rem;
            list-style: none;
            margin: 0;
        }

        .sidebar-header {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            color: #64748b;
            padding: 0.75rem 0.75rem 0.25rem 0.75rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.7rem 0.85rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
            margin-bottom: 0.2rem;
        }

        .sidebar-link i {
            font-size: 1.15rem;
            margin-right: 0.75rem;
            width: 22px;
            text-align: center;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }

        .sidebar-link.active {
            color: #ffffff;
            background: var(--secondary-teal);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
        }

        /* Top Header */
        #top-header {
            margin-left: var(--sidebar-width);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Main Content wrapper */
        #main-wrapper {
            margin-left: var(--sidebar-width);
            padding: 1.75rem;
            min-height: calc(100vh - 70px);
        }

        /* Components */
        .card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .stat-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .btn-teal {
            background-color: var(--secondary-teal);
            color: #ffffff;
            border: 1px solid var(--secondary-teal);
            font-weight: 600;
        }

        .btn-teal:hover {
            background-color: var(--teal-hover);
            color: #ffffff;
        }

        .btn-navy {
            background-color: var(--primary-navy);
            color: #ffffff;
            border: 1px solid var(--primary-navy);
            font-weight: 600;
        }

        .btn-navy:hover {
            background-color: var(--primary-navy-dark);
            color: #ffffff;
        }

        /* Responsive Mobile Behavior */
        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            #sidebar.show {
                margin-left: 0;
            }
            #top-header, #main-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar -->
    <aside id="sidebar">
        <a href="{{ route('home') }}" class="sidebar-brand">
            <div class="rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: var(--secondary-teal);">
                <i class="bi bi-shield-plus text-white"></i>
            </div>
            CLINIC<span>SYNC</span>
        </a>

        <div class="px-3 py-2 text-center border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-25">
            <div class="small text-uppercase text-light-50 fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                @if(Auth::user()->isAdmin())
                    <span class="badge bg-danger">Administrator</span>
                @elseif(Auth::user()->isStaff())
                    <span class="badge bg-info text-dark">Clinic Staff</span>
                @elseif(Auth::user()->isDentist())
                    <span class="badge bg-primary">Assigned Dentist</span>
                @else
                    <span class="badge bg-teal" style="background-color: var(--secondary-teal);">Patient Portal</span>
                @endif
            </div>
            <div class="text-truncate fw-semibold text-white small mt-1">{{ Auth::user()->name }}</div>
        </div>

        <ul class="sidebar-menu">
            {{-- PATIENT MENU --}}
            @if(Auth::user()->isPatient())
                <li class="sidebar-header">Patient Services</li>
                <li>
                    <a href="{{ route('patient.dashboard') }}" class="sidebar-link {{ request()->routeIs('patient.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('patient.appointments.request') }}" class="sidebar-link {{ request()->routeIs('patient.appointments.request') ? 'active' : '' }}">
                        <i class="bi bi-calendar-plus-fill"></i> Book Appointment
                    </a>
                </li>
                <li>
                    <a href="{{ route('patient.appointments.index') }}" class="sidebar-link {{ request()->routeIs('patient.appointments.*') && !request()->routeIs('patient.appointments.request') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check-fill"></i> My Appointments
                    </a>
                </li>
                <li>
                    <a href="{{ route('patient.notifications') }}" class="sidebar-link {{ request()->routeIs('patient.notifications') ? 'active' : '' }}">
                        <i class="bi bi-chat-left-dots-fill"></i> SMS Alerts
                    </a>
                </li>
                <li>
                    <a href="{{ route('patient.profile') }}" class="sidebar-link {{ request()->routeIs('patient.profile') ? 'active' : '' }}">
                        <i class="bi bi-person-lines-fill"></i> My Profile & Phone
                    </a>
                </li>
            @endif

            {{-- STAFF / ADMIN MENU --}}
            @if(Auth::user()->isStaffOrAdmin())
                <li class="sidebar-header">Clinic Management</li>
                <li>
                    <a href="{{ route('staff.dashboard') }}" class="sidebar-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.appointments.index') }}" class="sidebar-link {{ request()->routeIs('staff.appointments.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-range-fill"></i> Appointments Manager
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.calendar') }}" class="sidebar-link {{ request()->routeIs('staff.calendar') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i> Master Calendar
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.patients') }}" class="sidebar-link {{ request()->routeIs('staff.patients') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i> Patient Directory
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.dentists.index') }}" class="sidebar-link {{ request()->routeIs('staff.dentists.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i> Dentists & Schedules
                    </a>
                </li>

                <li class="sidebar-header">Automations & SMS</li>
                <li>
                    <a href="{{ route('staff.notifications.index') }}" class="sidebar-link {{ request()->routeIs('staff.notifications.*') ? 'active' : '' }}">
                        <i class="bi bi-chat-text-fill"></i> SMS Delivery Logs
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.settings') }}" class="sidebar-link {{ request()->routeIs('staff.settings') ? 'active' : '' }}">
                        <i class="bi bi-broadcast"></i> SMS Gateway Settings
                    </a>
                </li>
            @endif

            {{-- DENTIST MENU --}}
            @if(Auth::user()->isDentist())
                <li class="sidebar-header">Clinical Portal</li>
                <li>
                    <a href="{{ route('dentist.dashboard') }}" class="sidebar-link {{ request()->routeIs('dentist.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> Today's Schedule
                    </a>
                </li>
                <li>
                    <a href="{{ route('dentist.schedule') }}" class="sidebar-link {{ request()->routeIs('dentist.schedule') || request()->routeIs('dentist.appointments.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week-fill"></i> Assigned Appointments
                    </a>
                </li>
            @endif

            <li class="sidebar-header">Account</li>
            <li>
                <a href="{{ route('settings') }}" class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i> Security Settings
                </a>
            </li>
            <li>
                <a href="{{ route('home') }}" class="sidebar-link" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> View Public Site
                </a>
            </li>
            <li>
                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                    @csrf
                    <button type="submit" class="sidebar-link border-0 bg-transparent text-danger w-100 text-start">
                        <i class="bi bi-box-arrow-left text-danger"></i> Sign Out
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Top Header -->
    <header id="top-header">
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-outline-secondary d-lg-none me-2" id="sidebarToggle" type="button">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h5 class="mb-0 fw-bold text-dark">@yield('page-title', 'Overview')</h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-flex align-items-center text-muted small">
                <i class="bi bi-clock me-1 text-primary"></i>
                <span id="liveClock">{{ now()->format('l, F d, Y') }}</span>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; background-color: var(--primary-navy) !important;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <span class="d-none d-sm-inline fw-semibold">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li class="px-3 py-1">
                        <div class="small fw-bold">{{ Auth::user()->name }}</div>
                        <div class="text-muted small">{{ Auth::user()->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('settings') }}"><i class="bi bi-key me-2"></i> Change Password</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="main-wrapper">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div>{{ session('info') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i> Please correct the errors below:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
    @yield('scripts')
</body>
</html>
