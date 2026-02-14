<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log specific actions
        if ($this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged
     */
    protected function shouldLog(Request $request): bool
    {
        $loggableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $loggableRoutes = [
            'pelanggan.store',
            'pelanggan.update',
            'pelanggan.destroy',
            'pelanggan.import',
            'users.store',
            'users.update',
            'users.destroy',
            'users.reset-password',
            'users.password-reset.reject',
        ];

        return in_array($request->method(), $loggableMethods) &&
               (in_array($request->route()?->getName(), $loggableRoutes) ||
                $request->is('*/edit') ||
                $request->is('*/create'));
    }

    /**
     * Log the activity
     */
    protected function logActivity(Request $request, Response $response): void
    {
        $user = Auth::user();
        $userId = $user?->id ?? 'guest';
        $username = $user?->username ?? 'guest';
        $role = $user?->role?->name ?? 'none';

        $logData = [
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'action' => $request->method(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'input_keys' => array_keys($request->except(['password', 'password_confirmation', '_token'])),
        ];

        // Log successful operations
        if ($response->isSuccessful() || $response->isRedirection()) {
            Log::channel('audit')->info('User action performed', $logData);
        } else {
            Log::channel('audit')->warning('User action failed or returned error', $logData);
        }
    }
}
