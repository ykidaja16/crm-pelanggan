<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Medical Lab CRM</title>
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
        .login-body {
            padding: 40px 30px;
        }
        .btn-login {
            background: #0056b3;
            border: none;
            width: 100%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h3>Reset Password</h3>
        </div>
        <div class="login-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-login text-white">
                    Send Password Reset Link
                </button>
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-decoration-none small">Kembali ke Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
