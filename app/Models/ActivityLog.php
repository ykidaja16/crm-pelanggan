<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // Hanya pakai created_at, tidak updated_at

    protected $fillable = [
        'user_id',
        'username',
        'role',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper: catat aktivitas secara otomatis
     */
    public static function record(
        string $action,
        string $module,
        string $description,
        ?int $userId = null,
        string $username = 'guest',
        string $role = '-',
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            static::create([
                'user_id'    => $userId,
                'username'   => $username,
                'role'       => $role,
                'action'     => $action,
                'module'     => $module,
                'description'=> $description,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Jangan sampai error log mengganggu request utama
            \Illuminate\Support\Facades\Log::error('ActivityLog::record failed: ' . $e->getMessage());
        }
    }

    /**
     * Label warna badge per action
     */
    public static function actionBadgeClass(string $action): string
    {
        return match($action) {
            'login'   => 'bg-success',
            'logout'  => 'bg-secondary',
            'create'  => 'bg-primary',
            'update'  => 'bg-warning text-dark',
            'delete'  => 'bg-danger',
            'import'  => 'bg-info',
            'restore' => 'bg-teal text-white',
            default   => 'bg-secondary',
        };
    }

    /**
     * Label teks per action
     */
    public static function actionLabel(string $action): string
    {
        return match($action) {
            'login'   => 'Login',
            'logout'  => 'Logout',
            'create'  => 'Tambah',
            'update'  => 'Ubah',
            'delete'  => 'Hapus',
            'import'  => 'Import',
            'restore' => 'Pulihkan',
            default   => ucfirst($action),
        };
    }
}
