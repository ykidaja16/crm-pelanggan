<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PelangganImportExportController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\CabangController;
use App\Models\User;
use App\Models\Role;

// Seed Users Route - hanya untuk local development yang aman
if (
    app()->environment('local') &&
    config('app.debug') === true &&
    in_array(request()->getHost(), ['localhost', '127.0.0.1'], true)
) {
    Route::get('/seed-users', function () {
        try {
            // Create roles if not exist
            $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
            $adminRole = Role::firstOrCreate(['name' => 'Admin']);
            $userRole = Role::firstOrCreate(['name' => 'User']);

            // Create or update users
            $users = [
                [
                    'name' => 'Super Admin',
                    'username' => 'superadmin',
                    'email' => 'superadmin@crm.com',
                    'password' => bcrypt('password'),
                    'role_id' => $superAdminRole->id,
                    'is_active' => true,
                ],
                [
                    'name' => 'Admin',
                    'username' => 'admin',
                    'email' => 'admin@crm.com',
                    'password' => bcrypt('password'),
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                ],
                [
                    'name' => 'User Biasa',
                    'username' => 'user',
                    'email' => 'user@crm.com',
                    'password' => bcrypt('password'),
                    'role_id' => $userRole->id,
                    'is_active' => true,
                ],
            ];

            foreach ($users as $userData) {
                User::updateOrCreate(
                    ['username' => $userData['username']],
                    $userData
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Default users created successfully',
                'users' => [
                    ['username' => 'superadmin', 'password' => 'password', 'role' => 'Super Admin'],
                    ['username' => 'admin', 'password' => 'password', 'role' => 'Admin'],
                    ['username' => 'user', 'password' => 'password', 'role' => 'User'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    });
}

// Auth
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Forgot Password
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // ─── Import / Export (PelangganImportExportController) ───────────────────
    Route::post('/import', [PelangganImportExportController::class, 'import'])
        ->name('pelanggan.import')
        ->middleware('throttle:import');
    Route::get('/import/progress', [PelangganImportExportController::class, 'importProgress'])
        ->name('pelanggan.import.progress');
    Route::get('/export', [PelangganImportExportController::class, 'export'])
        ->name('pelanggan.export');
    Route::get('/download-template', [PelangganImportExportController::class, 'downloadTemplate'])
        ->name('pelanggan.download-template');

    // ─── Laporan ─────────────────────────────────────────────────────────────
    Route::get('/laporan', [\App\Http\Controllers\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/preview', [\App\Http\Controllers\LaporanController::class, 'preview'])->name('laporan.preview');
    Route::get('/laporan/export', [\App\Http\Controllers\LaporanController::class, 'export'])->name('laporan.export');

    // ─── Detail Pelanggan (semua role bisa lihat) ─────────────────────────────
    Route::get('/pelanggan/{pelanggan}/show', [PelangganController::class, 'show'])->name('pelanggan.show');

    // ─── Admin & Super Admin routes ───────────────────────────────────────────
    Route::middleware([\App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {

        // CRUD Pelanggan
        Route::get('/pelanggan/create', [PelangganController::class, 'create'])->name('pelanggan.create');
        Route::post('/pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('/pelanggan/{pelanggan}/edit', [PelangganController::class, 'edit'])->name('pelanggan.edit');
        Route::put('/pelanggan/{pelanggan}', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('/pelanggan/{pelanggan}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');

        // Bulk actions
        Route::post('/pelanggan/bulk-delete', [PelangganController::class, 'bulkDelete'])->name('pelanggan.bulk-delete');
        Route::post('/pelanggan/bulk-export', [PelangganImportExportController::class, 'bulkExport'])->name('pelanggan.bulk-export');

        // API pencarian pelanggan berdasarkan PID
        Route::get('/api/pelanggan/search', [PelangganController::class, 'searchByPid'])->name('api.pelanggan.search');

        // Kunjungan – form edit (pengajuan approval, bukan direct update)
        Route::get('/kunjungan/{kunjungan}/edit', [KunjunganController::class, 'edit'])->name('kunjungan.edit');

        // Pelanggan Khusus
        Route::get('/pelanggan-khusus', [PelangganController::class, 'khusus'])->name('pelanggan.khusus.index');
        Route::get('/download-template-khusus', [ApprovalRequestController::class, 'downloadTemplateKhusus'])->name('pelanggan.download-template-khusus');

        // Pengajuan approval
        Route::post('/approval/pelanggan-khusus', [ApprovalRequestController::class, 'storeSpecialCustomerRequest'])->name('approval.special.store');
        Route::post('/approval/pelanggan-khusus/import', [ApprovalRequestController::class, 'storeSpecialCustomerImportRequest'])->name('approval.special.import.store');
        Route::post('/approval/kunjungan/{kunjungan}/edit', [ApprovalRequestController::class, 'storeKunjunganEditRequest'])->name('approval.kunjungan.edit.store');
        Route::post('/approval/kunjungan/{kunjungan}/delete', [ApprovalRequestController::class, 'storeKunjunganDeleteRequest'])->name('approval.kunjungan.delete.store');
    });

    // ─── Super Admin only ─────────────────────────────────────────────────────
    Route::middleware([\App\Http\Middleware\EnsureUserIsSuperAdmin::class])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);

        // Password reset routes
        Route::get('/password-reset-requests', [\App\Http\Controllers\UserController::class, 'passwordResetRequests'])->name('users.password-reset-requests');
        Route::get('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'showResetForm'])->name('users.reset-password.form');
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/password-reset-requests/{resetRequest}/reject', [\App\Http\Controllers\UserController::class, 'rejectPasswordResetRequest'])->name('users.password-reset.reject');

        // Activity Log
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');

        // Approval requests
        Route::get('/approval-requests', [ApprovalRequestController::class, 'index'])->name('approval.index');
        Route::post('/approval-requests/{id}/approve', [ApprovalRequestController::class, 'approve'])->name('approval.approve');
        Route::post('/approval-requests/{id}/reject', [ApprovalRequestController::class, 'reject'])->name('approval.reject');
        Route::post('/approval-requests/{id}/process', [ApprovalRequestController::class, 'process'])->name('approval.process');

        // Cabang management
        Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
        Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
        Route::put('/cabang/{id}', [CabangController::class, 'update'])->name('cabang.update');
        Route::delete('/cabang/{id}', [CabangController::class, 'destroy'])->name('cabang.destroy');
    });
});
