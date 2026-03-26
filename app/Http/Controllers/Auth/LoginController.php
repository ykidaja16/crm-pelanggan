<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class LoginController extends Controller
{
    /**
     * Maximum failed login attempts before lockout
     */
    protected $maxAttempts = 5;

    /**
     * Lockout duration in minutes
     */
    protected $lockoutDuration = 15;

    public function index()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        // Check if user is locked out
        $lockoutKey = 'login_attempts_' . $request->ip();
        $attempts = Cache::get($lockoutKey, 0);

        if ($attempts >= $this->maxAttempts) {
            $remainingTime = Cache::get($lockoutKey . '_expires') - now()->timestamp;
            $minutes = ceil($remainingTime / 60);
            
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$minutes} menit.",
            ])->onlyInput('username');
        }

        $credentials = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Cek apakah akun user aktif
            if (!Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning('Login attempt by inactive user', [
                    'username' => $request->username,
                    'ip'       => $request->ip(),
                ]);

                return back()->withErrors([
                    'username' => 'Akun Anda tidak aktif. Silakan hubungi IT.',
                ])->onlyInput('username');
            }

            // Clear failed attempts on successful login
            Cache::forget($lockoutKey);
            Cache::forget($lockoutKey . '_expires');

            $request->session()->regenerate();

            // Catat login ke database activity log
            ActivityLog::record(
                action:      'login',
                module:      'auth',
                description: 'User berhasil login',
                userId:      Auth::id(),
                username:    Auth::user()->username,
                role:        Auth::user()->role?->name ?? '-',
                ipAddress:   $request->ip(),
                userAgent:   $request->userAgent(),
            );

            // Log ke file sebagai backup
            Log::info('User logged in successfully', [
                'user_id'  => Auth::id(),
                'username' => Auth::user()->username,
                'ip'       => $request->ip(),
            ]);

            return redirect()->intended('/dashboard');
        }

        // Increment failed attempts
        $attempts++;
        Cache::put($lockoutKey, $attempts, now()->addMinutes($this->lockoutDuration));
        Cache::put($lockoutKey . '_expires', now()->addMinutes($this->lockoutDuration)->timestamp, $this->lockoutDuration * 60);

        // Log failed attempt
        Log::warning('Failed login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'attempt' => $attempts,
            'user_agent' => $request->userAgent(),
        ]);

        $remainingAttempts = $this->maxAttempts - $attempts;

        $errorMessage = 'Username atau password salah.';
        if ($remainingAttempts > 0 && $remainingAttempts < $this->maxAttempts) {
            $errorMessage .= " Anda memiliki {$remainingAttempts} percobaan lagi.";
        } elseif ($remainingAttempts <= 0) {
            $errorMessage .= " Akun Anda dikunci selama {$this->lockoutDuration} menit.";
        }

        return back()->withErrors([
            'username' => $errorMessage,
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $userId   = Auth::id();
        $username = Auth::user()?->username ?? 'guest';
        $role     = Auth::user()?->role?->name ?? '-';

        // Catat logout ke database SEBELUM Auth::logout()
        ActivityLog::record(
            action:      'logout',
            module:      'auth',
            description: 'User logout',
            userId:      $userId,
            username:    $username,
            role:        $role,
            ipAddress:   $request->ip(),
            userAgent:   $request->userAgent(),
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log ke file sebagai backup
        Log::info('User logged out', [
            'user_id'  => $userId,
            'username' => $username,
            'ip'       => $request->ip(),
        ]);

        return redirect('/login');
    }
}
