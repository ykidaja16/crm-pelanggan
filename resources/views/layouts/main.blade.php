<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Medical Lab CRM')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logosima.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            overflow-x: hidden;
        }
        
        /* Prevent buttons from growing when clicked/disabled - Import & Download Template buttons */
        #importBtn,
        #importBtn:focus,
        #importBtn:active,
        #importBtn:hover,
        #importBtn:disabled,
        #importBtn[disabled],
        #downloadTemplateBtn,
        a#downloadTemplateBtn:focus,
        a#downloadTemplateBtn:active,
        a#downloadTemplateBtn:hover,
        a#downloadTemplateBtn.disabled,
        #downloadTemplateBtn:disabled,
        #downloadTemplateBtn[disabled] {
            min-width: unset !important;
            width: auto !important;
            padding: 0.25rem 0.75rem !important;
            transform: none !important;
            box-sizing: border-box !important;
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            vertical-align: middle !important;
        }
        
        /* Ensure consistent button size during loading animation */
        #importBtn .spinner-border {
            width: 12px;
            height: 12px;
            border-width: 2px;
        }
        
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #ffffff;
            color: #333;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            min-height: 100vh;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #0056b3 0%, #00a8cc 100%);
            color: white;
        }
        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        #sidebar ul p {
            color: #aaa;
            padding: 10px;
        }
        #sidebar ul li a {
            padding: 15px 25px;
            font-size: 1.1em;
            display: block;
            color: #555;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: 0.3s;
        }
        #sidebar ul li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        #sidebar ul li a:hover {
            color: #0056b3;
            background: #f8f9fa;
        }
        #sidebar ul li.active > a {
            color: #0056b3;
            background: #e9ecef;
            border-right: 4px solid #0056b3;
        }
        /* Submenu Pelanggan */
        #sidebar .submenu-pelanggan {
            background: #f8f9fa;
            border-left: 3px solid #0056b3;
            margin-left: 0;
        }
        #sidebar .submenu-pelanggan li a {
            padding: 10px 20px 10px 45px;
            font-size: 0.92em;
            color: #666;
        }
        #sidebar .submenu-pelanggan li a:hover {
            color: #0056b3;
            background: #e9ecef;
        }
        #sidebar .submenu-pelanggan li.active > a {
            color: #0056b3;
            background: #e9ecef;
            border-right: 4px solid #0056b3;
            font-weight: 600;
        }
        #sidebar .pelanggan-toggle {
            cursor: pointer;
        }
        #sidebar .pelanggan-toggle .fa-chevron-down {
            margin-left: auto;
            margin-right: 0;
            width: auto;
            font-size: 0.75em;
            transition: transform 0.3s;
        }
        #sidebar .pelanggan-toggle[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }
        /* Submenu Approval */
        #sidebar .submenu-approval {
            background: #f8f9fa;
            border-left: 3px solid #0056b3;
            margin-left: 0;
        }
        #sidebar .submenu-approval li a {
            padding: 10px 20px 10px 45px;
            font-size: 0.92em;
            color: #666;
        }
        #sidebar .submenu-approval li a:hover {
            color: #0056b3;
            background: #e9ecef;
        }
        #sidebar .submenu-approval li.active > a {
            color: #0056b3;
            background: #e9ecef;
            border-right: 4px solid #0056b3;
            font-weight: 600;
        }
        #sidebar .approval-toggle {
            cursor: pointer;
        }
        #sidebar .approval-toggle .fa-chevron-down {
            margin-left: auto;
            margin-right: 0;
            width: auto;
            font-size: 0.75em;
            transition: transform 0.3s;
        }
        #sidebar .approval-toggle[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }
        /* Submenu Special Day */
        #sidebar .submenu-special-day {
            background: #f8f9fa;
            border-left: 3px solid #0056b3;
            margin-left: 0;
        }
        #sidebar .submenu-special-day li a {
            padding: 10px 20px 10px 45px;
            font-size: 0.92em;
            color: #666;
        }
        #sidebar .submenu-special-day li a:hover {
            color: #0056b3;
            background: #e9ecef;
        }
        #sidebar .submenu-special-day li.active > a {
            color: #0056b3;
            background: #e9ecef;
            border-right: 4px solid #0056b3;
            font-weight: 600;
        }
        #sidebar .special-day-toggle {
            cursor: pointer;
        }
        #sidebar .special-day-toggle .fa-chevron-down {
            margin-left: auto;
            margin-right: 0;
            width: auto;
            font-size: 0.75em;
            transition: transform 0.3s;
        }
        #sidebar .special-day-toggle[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
        .btn-primary {
            background: #0056b3;
            border-color: #0056b3;
        }
        .btn-success {
            background: #00a8cc;
            border-color: #00a8cc;
        }
        .badge-primary { background: #0056b3; }
        .badge-info { background: #00a8cc; }
        
        /* ============================================
           RESPONSIVE STYLES - TAMBAHAN UNTUK MOBILE
           ============================================ */
        
        /* Overlay untuk mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Mobile Responsive - Tablet & Mobile */
        @media (max-width: 991.98px) {
            /* Sidebar behavior */
            #sidebar {
                position: fixed;
                left: -250px;
                z-index: 999;
                height: 100vh;
                overflow-y: auto;
            }
            
            #sidebar.active {
                left: 0;
            }
            
            /* Content adjustment */
            #content {
                padding: 15px;
                width: 100%;
            }
            
            /* Navbar adjustments */
            .navbar .container-fluid {
                padding: 0.5rem;
            }
            
            /* Compact cards */
            .card {
                margin-bottom: 15px;
            }
            
            .card-header {
                padding: 12px 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            /* Table responsive wrapper */
            .table-responsive-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Form adjustments */
            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
            }
            
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
            }
            
            /* Button adjustments */
            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.875rem;
            }
            
            /* Compact spacing */
            .mb-4 {
                margin-bottom: 1rem !important;
            }
            
            .mb-3 {
                margin-bottom: 0.75rem !important;
            }
            
            /* Alert compact */
            .alert {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        /* Small Mobile - < 576px */
        @media (max-width: 575.98px) {
            #content {
                padding: 10px;
            }
            
            /* Hide text in profile dropdown, show only icon */
            .dropdown .text-start.d-none.d-md-block {
                display: none !important;
            }
            
            /* Make dropdown button more compact */
            #profileDropdown {
                padding: 0.4rem !important;
            }
            
            /* Sidebar header smaller */
            #sidebar .sidebar-header {
                padding: 12px;
            }
            
            #sidebar .sidebar-header img {
                height: 35px !important;
                margin-bottom: 3px !important;
            }
            
            #sidebar .sidebar-header h4 {
                font-size: 0.85rem;
            }
            
            /* Menu items more compact */
            #sidebar ul li a {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            #sidebar .submenu-pelanggan li a,
            #sidebar .submenu-approval li a,
            #sidebar .submenu-special-day li a {
                padding: 8px 15px 8px 35px;
                font-size: 0.85rem;
            }
            
            /* Card more compact */
            .card-header {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            
            .card-body {
                padding: 12px;
            }
            
            /* Table font smaller */
            .table {
                font-size: 0.85rem;
            }
            
            .table td, .table th {
                padding: 0.5rem;
            }
            
            /* Badge smaller */
            .badge {
                font-size: 0.75rem;
                padding: 0.3em 0.5em;
            }
            
            /* Pagination compact */
            .pagination {
                font-size: 0.85rem;
            }
            
            .page-link {
                padding: 0.3rem 0.6rem;
            }
        }
        
        /* Extra Small Mobile - < 360px */
        @media (max-width: 359.98px) {
            #content {
                padding: 8px;
            }
            
            .card-header {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 0.35rem 0.6rem;
                font-size: 0.8rem;
            }
            
            /* Stack buttons vertically on very small screens */
            .btn-group-responsive {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group-responsive .btn {
                width: 100%;
            }
        }
        
        /* Landscape orientation optimization */
        @media (max-height: 500px) and (orientation: landscape) {
            #sidebar {
                overflow-y: auto;
            }
            
            #sidebar .sidebar-header {
                padding: 10px;
            }
            
            #sidebar .sidebar-header img {
                height: 40px !important;
                margin-bottom: 5px !important;
            }
        }
        
        /* Print styles - hide sidebar when printing */
        @media print {
            #sidebar {
                display: none !important;
            }
            
            #content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .navbar {
                display: none !important;
            }
            
            .btn, .dropdown {
                display: none !important;
            }
        }
        
        /* Utility classes for responsive tables */
        .table-responsive-mobile {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            display: block;
            width: 100%;
        }
        
        /* Ensure images are responsive */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Smooth transitions for sidebar */
        #sidebar {
            transition: all 0.3s ease-in-out;
        }
        
        /* Prevent horizontal scroll on body */
        body {
            overflow-x: hidden;
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header text-center">
            <img src="{{ asset('images/logosima.png') }}" alt="SIMA Lab Logo" style="height: 60px; margin-bottom: 10px; max-width: 100%;">
            <!-- <h4 class="mb-0">SIMA Lab</h4> -->
            <h4 class="mb-0">CRM System</h4> 
        </div>



        <ul class="list-unstyled components">
            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="fas fa-chart-line"></i> Dashboard Utama
                </a>
            </li>
            @if(Auth::user()->role?->name !== 'IT')
            @php
                $pelangganActive = request()->routeIs('pelanggan.index')
                                || request()->routeIs('pelanggan.show')
                                || request()->routeIs('pelanggan.input')
                                || request()->routeIs('pelanggan.create')
                                || request()->routeIs('pelanggan.edit')
                                || request()->routeIs('pelanggan.khusus*');
            @endphp
            <li class="{{ $pelangganActive ? 'active' : '' }}">
                <a href="#pelangganSubmenu"
                   data-bs-toggle="collapse"
                   class="pelanggan-toggle"
                   aria-expanded="{{ $pelangganActive ? 'true' : 'false' }}"
                   aria-controls="pelangganSubmenu">
                    <i class="fas fa-users"></i> Data Pelanggan
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="list-unstyled submenu-pelanggan collapse {{ $pelangganActive ? 'show' : '' }}" id="pelangganSubmenu">
                    <li class="{{ (request()->routeIs('pelanggan.index') || request()->routeIs('pelanggan.show') || request()->routeIs('pelanggan.edit')) ? 'active' : '' }}">
                        <a href="{{ route('pelanggan.index') }}">
                            <i class="fas fa-chart-bar me-2"></i>Dashboard Pelanggan
                        </a>
                    </li>
                    @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
                    <li class="{{ (request()->routeIs('pelanggan.input') || request()->routeIs('pelanggan.create')) ? 'active' : '' }}">
                        <a href="{{ route('pelanggan.input') }}">
                            <i class="fas fa-file-import me-2"></i>Input Data Pelanggan
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('pelanggan.khusus*') ? 'active' : '' }}">
                        <a href="{{ route('pelanggan.khusus.index') }}">
                            <i class="fas fa-star me-2"></i>Pelanggan Khusus
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif
            @if(Auth::user()->role?->name !== 'IT')
            <li class="{{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <a href="{{ route('laporan.index') }}">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
            </li>
            @endif
            @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
            @php
                $specialDayActive = request()->routeIs('special-day.*');
            @endphp
            <li class="{{ $specialDayActive ? 'active' : '' }}">
                <a href="#specialDaySubmenu"
                   data-bs-toggle="collapse"
                   class="special-day-toggle"
                   aria-expanded="{{ $specialDayActive ? 'true' : 'false' }}"
                   aria-controls="specialDaySubmenu">
                    <i class="fas fa-birthday-cake"></i> Special Day Member
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="list-unstyled submenu-special-day collapse {{ $specialDayActive ? 'show' : '' }}" id="specialDaySubmenu">
                    <li class="{{ request()->routeIs('special-day.birthday*') ? 'active' : '' }}">
                        <a href="{{ route('special-day.birthday') }}">
                            <i class="fas fa-birthday-cake me-2"></i>Birthday Reminder
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('special-day.kunjungan-terakhir*') ? 'active' : '' }}">
                        <a href="{{ route('special-day.kunjungan-terakhir') }}">
                            <i class="fas fa-calendar-times me-2"></i>Kunjungan Terakhir
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            @if(Auth::user()->role?->name === 'Super Admin')
            @php
                $approvalActive = request()->routeIs('approval.pelanggan-khusus')
                               || request()->routeIs('approval.kunjungan')
                               || request()->routeIs('approval.pelanggan')
                               || request()->routeIs('approval.naik-kelas');
            @endphp
            <li class="{{ $approvalActive ? 'active' : '' }}">
                <a href="#approvalSubmenu"
                   data-bs-toggle="collapse"
                   class="approval-toggle"
                   aria-expanded="{{ $approvalActive ? 'true' : 'false' }}"
                   aria-controls="approvalSubmenu">
                    <i class="fas fa-check-double"></i> Approval
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="list-unstyled submenu-approval collapse {{ $approvalActive ? 'show' : '' }}" id="approvalSubmenu">
                    <li class="{{ request()->routeIs('approval.pelanggan-khusus') ? 'active' : '' }}">
                        <a href="{{ route('approval.pelanggan-khusus') }}">
                            <i class="fas fa-star me-2"></i>Pelanggan Khusus
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('approval.kunjungan') ? 'active' : '' }}">
                        <a href="{{ route('approval.kunjungan') }}">
                            <i class="fas fa-calendar-check me-2"></i>Data Kunjungan
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('approval.pelanggan') ? 'active' : '' }}">
                        <a href="{{ route('approval.pelanggan') }}">
                            <i class="fas fa-users me-2"></i>Data Pelanggan
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('approval.naik-kelas') ? 'active' : '' }}">
                        <a href="{{ route('approval.naik-kelas') }}">
                            <i class="fas fa-arrow-up me-2"></i>Ubah Kelas
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            {{-- IT: hanya Manajemen User dan Log Aktivitas --}}
            @if(Auth::user()->role?->name === 'IT')
            <li class="{{ request()->routeIs('users*') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}">
                    <i class="fas fa-user-cog"></i> Manajemen User
                </a>
            </li>
            <li class="{{ request()->routeIs('cabang.*') ? 'active' : '' }}">
                <a href="{{ route('cabang.index') }}">
                    <i class="fas fa-building"></i> Manajemen Cabang
                </a>
            </li>
            <li class="{{ request()->routeIs('activity-log*') ? 'active' : '' }}">
                <a href="{{ route('activity-log.index') }}">
                    <i class="fas fa-history"></i> Log Aktivitas
                </a>
            </li>
            <li class="{{ request()->routeIs('import-batch*') ? 'active' : '' }}">
                <a href="{{ route('import-batch.index') }}">
                    <i class="fas fa-undo-alt"></i> Riwayat Import
                </a>
            </li>
            @endif
        </ul>


        <ul class="list-unstyled components border-top pt-2">
            <li>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item px-4 py-3 text-danger" style="background:none; border:none; width:100%; text-align:left; display:flex; align-items:center;">
                        <i class="fas fa-sign-out-alt me-3"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-light text-primary">
                    <i class="fas fa-bars"></i>
                </button>
                {{-- <a class="navbar-brand ms-3 d-flex align-items-center" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logosima.png') }}" alt="SIMA Lab" height="35" class="me-2">
                    <!-- <span class="fw-bold text-primary">SIMA Lab</span> -->
                </a> --}}
                <div class="ms-auto d-flex align-items-center gap-2">
                    {{-- Dropdown Profil User --}}
                    <div class="dropdown">
                        <button class="btn btn-light border d-flex align-items-center gap-2 px-3 py-2 dropdown-toggle"
                                type="button" id="profileDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                style="border-radius:8px;">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                 style="width:30px;height:30px;min-width:30px;">
                                <i class="fas fa-user text-white" style="font-size:0.75rem;"></i>
                            </div>
                            <div class="text-start d-none d-md-block" style="line-height:1.2;">
                                <div class="fw-semibold text-dark" style="font-size:0.85rem;">{{ Auth::user()->name }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ Auth::user()->role?->name ?? 'User' }}</div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-1" aria-labelledby="profileDropdown"
                            style="min-width:200px; border-radius:10px;">
                            <li>
                                <div class="px-3 py-2 border-bottom">
                                    <div class="fw-semibold text-dark" style="font-size:0.85rem;">{{ Auth::user()->name }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ Auth::user()->email }}</div>
                                    <span class="badge bg-primary mt-1" style="font-size:0.7rem;">{{ Auth::user()->role?->name ?? 'User' }}</span>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                   href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user-edit text-primary" style="width:16px;"></i>
                                    <span>Profil Saya</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                                        <i class="fas fa-sign-out-alt" style="width:16px;"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>


        {{-- Flash Messages --}}
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            @if(session('import_errors'))
                <ul class="mb-0 mt-2">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif


        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle sidebar functionality for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (sidebarCollapse && sidebar) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            });
        }
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay && sidebar) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Close sidebar when clicking on a menu item (mobile only)
        const sidebarLinks = sidebar.querySelectorAll('a:not([data-bs-toggle="collapse"])');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991.98) {
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        });
    });
</script>
        <script>
        // Global Approval Button Handler - untuk semua popup approval
        function updateApprovalBtn(radio) {
            const form = radio.closest('form');
            if (!form) return;
            const btn = form.querySelector('.approval-submit-btn');
            if (!btn) return;
            
            if (radio.value === 'approve') {
                btn.className = 'btn btn-success btn-sm px-4 approval-submit-btn';
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Approve';
            } else if (radio.value === 'reject') {
                btn.className = 'btn btn-danger btn-sm px-4 approval-submit-btn';
                btn.innerHTML = '<i class="fas fa-times me-1"></i>Reject';
            }
        }
        </script>
        @yield('scripts')
</body>

</html>

