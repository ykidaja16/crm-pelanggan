<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Medical Lab CRM')</title>
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
        <div class="sidebar-header">
            <h4 class="mb-0">SIMA Lab</h4>
            <small>CRM System</small>
        </div>

        <ul class="list-unstyled components">
            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="{{ request()->routeIs('pelanggan.index') || request()->routeIs('pelanggan.show') || request()->routeIs('pelanggan.create') || request()->routeIs('pelanggan.edit') ? 'active' : '' }}">
                <a href="{{ route('pelanggan.index') }}">
                    <i class="fas fa-users"></i> Data Pelanggan
                </a>
            </li>
            @if(Auth::user()->role?->name === 'Super Admin')
            <li class="{{ request()->routeIs('users*') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}">
                    <i class="fas fa-user-cog"></i> Manajemen User
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
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-3 text-muted">Welcome, <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->role?->name ?? 'User' }})</span>
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
