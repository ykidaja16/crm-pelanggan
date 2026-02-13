<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Medical Lab CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            border: none;
        }
        .login-header {
            background: linear-gradient(135deg, #0056b3 0%, #00a8cc 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e1e5eb;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #00a8cc;
            box-shadow: 0 0 0 3px rgba(0, 168, 204, 0.1);
            background-color: white;
        }
        .btn-login {
            background: #0056b3;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #004494;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 86, 179, 0.3);
        }
        .alert-danger {
            border-radius: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h3>Medical Lab</h3>
            <p>Customer Relationship Management</p>
        </div>
        <div class="login-body">
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="/login" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" value="{{ old('username') }}" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login">Sign In</button>
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-decoration-none small">Lupa Password?</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
