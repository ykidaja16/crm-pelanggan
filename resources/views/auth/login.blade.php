<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Medical Lab CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logosima.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a4fa0 0%, #1e90ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            padding: 40px 38px 36px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.22), 0 4px 16px rgba(0,0,0,0.1);
        }

        /* Brand row */
        .login-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .login-logo {
            max-height: 46px;
            max-width: 120px;
            width: auto;
            height: auto;
            flex-shrink: 0;
        }
        .login-brand-name {
            display: block;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a3c78;
            letter-spacing: 0.2px;
            line-height: 1.25;
        }
        .login-brand-name span { font-weight: 400; color: #4a6fa5; }
        .login-brand-sub {
            display: block;
            font-size: 0.62rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #7a90b0;
            font-weight: 500;
            margin-top: 5px;
        }

        /* Divider */
        .login-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #dde6f5, transparent);
            margin-bottom: 28px;
        }

        /* Welcome */
        .login-welcome {
            margin-bottom: 26px;
        }
        .login-welcome h5 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a3c78;
            margin-bottom: 4px;
        }
        .login-welcome p {
            font-size: 0.83rem;
            color: #8a9bb5;
            margin: 0;
        }

        /* Form */
        .form-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #4a5a78;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .form-control {
            border-radius: 10px;
            padding: 11px 15px;
            border: 1.5px solid #e4ecf7;
            background: #f6f9fd;
            font-size: 0.9rem;
            color: #2d3a50;
            transition: all 0.2s;
        }
        .form-control::placeholder { color: #b0bccf; }
        .form-control:focus {
            border-color: #1a4fa0;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26,79,160,0.1);
            outline: none;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #b0bccf;
            font-size: 0.85rem;
            pointer-events: none;
        }
        .input-icon-wrap .form-control {
            padding-left: 38px;
        }

        /* Button */
        .btn-login {
            background: linear-gradient(135deg, #1a4fa0 0%, #1e90ff 100%);
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 0.92rem;
            font-weight: 600;
            width: 100%;
            color: #fff;
            letter-spacing: 0.4px;
            transition: all 0.25s;
            box-shadow: 0 4px 16px rgba(26,79,160,0.3);
            margin-top: 4px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26,79,160,0.4);
            color: #fff;
        }
        .btn-login:active { transform: translateY(0); }

        /* Forgot */
        .login-forgot {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 0.82rem;
            color: #8a9bb5;
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-forgot:hover { color: #1a4fa0; }

        /* Alert */
        .alert-danger {
            border-radius: 10px;
            font-size: 0.85rem;
            border: none;
            background: #fff2f2;
            color: #c0392b;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        /* Footer */
        .login-footer {
            color: rgba(255,255,255,0.45);
            font-size: 0.72rem;
            margin-top: 22px;
            text-align: center;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>

    <div class="login-card">

        <div class="login-brand">
            <img src="{{ asset('images/logosima.png') }}" alt="SIMA Lab" class="login-logo">
            <div>
                <span class="login-brand-name">CRM <span>System</span></span>
                <span class="login-brand-sub">Medical Lab</span>
            </div>
        </div>

        <div class="login-divider"></div>

        <div class="login-welcome">
            <h5>Selamat Datang</h5>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form action="/login" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="{{ old('username') }}" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Masuk
            </button>
            <a href="{{ route('password.request') }}" class="login-forgot">Lupa Password?</a>
        </form>

    </div>

    <p class="login-footer">&copy; {{ date('Y') }} SIMA Lab &mdash; Medical Lab CRM System</p>

</body>
</html>
