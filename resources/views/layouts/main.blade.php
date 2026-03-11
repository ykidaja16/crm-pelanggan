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
    </style>
</head>
<body>

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
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="{{ request()->routeIs('pelanggan.index') || request()->routeIs('pelanggan.show') || request()->routeIs('pelanggan.create') || request()->routeIs('pelanggan.edit') ? 'active' : '' }}">
                @if(Auth::user()->role?->name !== 'IT')
                <a href="{{ route('pelanggan.index') }}">
                    <i class="fas fa-users"></i> Data Pelanggan
                </a>
                @endif
            </li>
            @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
            <li class="{{ request()->routeIs('pelanggan.khusus*') ? 'active' : '' }}">
                <a href="{{ route('pelanggan.khusus.index') }}">
                    <i class="fas fa-star"></i> Pelanggan Khusus
                </a>
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
            <li class="{{ request()->routeIs('special-day.*') ? 'active' : '' }}">
                <a href="{{ route('special-day.index') }}">
                    <i class="fas fa-birthday-cake"></i> Special Day Member
                </a>
            </li>
            @endif
            @if(Auth::user()->role?->name === 'Super Admin')
            @php
                $approvalActive = request()->routeIs('approval.pelanggan-khusus')
                               || request()->routeIs('approval.kunjungan')
                               || request()->routeIs('approval.pelanggan');
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
                </ul>
            </li>
            <li class="{{ request()->routeIs('cabang.*') ? 'active' : '' }}">
                <a href="{{ route('cabang.index') }}">
                    <i class="fas fa-building"></i> Manajemen Cabang
                </a>
            </li>
            @endif
            {{-- IT: hanya Manajemen User dan Log Aktivitas --}}
            @if(Auth::user()->role?->name === 'IT')
            <li class="{{ request()->routeIs('users*') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}">
                    <i class="fas fa-user-cog"></i> Manajemen User
                </a>
            </li>
            <li class="{{ request()->routeIs('activity-log*') ? 'active' : '' }}">
                <a href="{{ route('activity-log.index') }}">
                    <i class="fas fa-history"></i> Log Aktivitas
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
                <a class="navbar-brand ms-3 d-flex align-items-center" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logosima.png') }}" alt="SIMA Lab" height="35" class="me-2">
                    <!-- <span class="fw-bold text-primary">SIMA Lab</span> -->
                </a>
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


        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Optional: Toggle sidebar functionality if needed
    // document.getElementById('sidebarCollapse').addEventListener('click', function() {
    //     document.getElementById('sidebar').classList.toggle('active');
    // });
</script>
@yield('scripts')
</body>

</html>
