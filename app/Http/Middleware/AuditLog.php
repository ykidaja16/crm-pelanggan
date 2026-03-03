<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class AuditLog
{
    /**
     * Mapping route name → [action, module, description]
     */
    protected array $routeMap = [
        'pelanggan.store'              => ['create', 'pelanggan', 'Menambah data pelanggan/kunjungan baru'],
        'pelanggan.update'             => ['update', 'pelanggan', 'Mengubah data pelanggan'],
        'pelanggan.destroy'            => ['delete', 'pelanggan', 'Menghapus data pelanggan'],
        'pelanggan.import'             => ['import', 'pelanggan', 'Import data pelanggan dari file'],
        'users.store'                  => ['create', 'user',      'Menambah user baru'],
        'users.update'                 => ['update', 'user',      'Mengubah data user'],
        'users.destroy'                => ['delete', 'user',      'Menghapus user'],
        'users.reset-password'         => ['update', 'user',      'Reset password user'],
        'users.password-reset.reject'  => ['update', 'user',      'Menolak permintaan reset password'],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Tentukan apakah request perlu dicatat
     */
    protected function shouldLog(Request $request): bool
    {
        $loggableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $routeName = $request->route()?->getName();

        return in_array($request->method(), $loggableMethods) &&
               array_key_exists($routeName, $this->routeMap);
    }

    /**
     * Catat aktivitas ke database dan file log
     */
    protected function logActivity(Request $request, Response $response): void
    {
        $user     = Auth::user();
        $userId   = $user?->id;
        $username = $user?->username ?? 'guest';
        $role     = $user?->role?->name ?? '-';

        $routeName = $request->route()?->getName();
        [$action, $module, $description] = $this->routeMap[$routeName] ?? ['action', 'system', 'Aktivitas sistem'];

        // Hanya catat jika request berhasil (sukses atau redirect)
        if ($response->isSuccessful() || $response->isRedirection()) {
            // Simpan ke database
            ActivityLog::record(
                action:      $action,
                module:      $module,
                description: $description,
                userId:      $userId,
                username:    $username,
                role:        $role,
                ipAddress:   $request->ip(),
                userAgent:   $request->userAgent(),
            );

            // Tetap simpan ke file log sebagai backup
            Log::channel('audit')->info('User action performed', [
                'user_id'  => $userId,
                'username' => $username,
                'role'     => $role,
                'action'   => $action,
                'module'   => $module,
                'route'    => $routeName,
                'ip'       => $request->ip(),
            ]);
        } else {
            Log::channel('audit')->warning('User action failed', [
                'user_id'     => $userId,
                'username'    => $username,
                'route'       => $routeName,
                'status_code' => $response->getStatusCode(),
                'ip'          => $request->ip(),
            ]);
        }
    }
}
