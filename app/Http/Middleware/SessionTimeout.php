<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionTimeout
{
    /**
     * Session timeout in minutes (30 minutes)
     */
    protected $timeout = 30;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = Session::get('last_activity');

            if ($lastActivity && (time() - $lastActivity > ($this->timeout * 60))) {
                // Session expired, logout user
                $userId = Auth::id();
                $username = Auth::user()?->username;

                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                \Illuminate\Support\Facades\Log::info('Session timeout - user logged out', [
                    'user_id' => $userId,
                    'username' => $username,
                    'ip' => $request->ip(),
                ]);

                return redirect()->route('login')
                    ->with('warning', 'Sesi Anda telah berakhir karena tidak aktif selama ' . $this->timeout . ' menit. Silakan login kembali.');
            }

            // Update last activity time
            Session::put('last_activity', time());
        }

        return $next($request);
    }
}
