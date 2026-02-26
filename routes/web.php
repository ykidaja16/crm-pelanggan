<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ForgotPasswordController;
use App\Models\User;
use App\Models\Role;

// Seed Users Route - Create default users
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
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});

// Auth
Route::get('/login', [LoginController::class , 'index'])->name('login');
Route::post('/login', [LoginController::class , 'authenticate'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class , 'logout'])->name('logout');

// Forgot Password
Route::get('password/reset', [ForgotPasswordController::class , 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class , 'sendResetLinkEmail'])->name('password.email');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');
    Route::get('/pelanggan', [PelangganController::class , 'index'])->name('pelanggan.index');
    Route::get('/', function () {
            return redirect()->route('dashboard');
        }
        );

        Route::post('/import', [PelangganController::class , 'import'])->name('pelanggan.import')->middleware('throttle:import');
        Route::get('/export', [PelangganController::class , 'export'])->name('pelanggan.export');

        Route::get('/pelanggan/{pelanggan}/show', [PelangganController::class , 'show'])->name('pelanggan.show');
        
        Route::middleware([\App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
            Route::get('/pelanggan/create', [PelangganController::class , 'create'])->name('pelanggan.create');
            Route::post('/pelanggan', [PelangganController::class , 'store'])->name('pelanggan.store');
            Route::get('/pelanggan/{pelanggan}/edit', [PelangganController::class , 'edit'])->name('pelanggan.edit');
            Route::put('/pelanggan/{pelanggan}', [PelangganController::class , 'update'])->name('pelanggan.update');
            Route::delete('/pelanggan/{pelanggan}', [PelangganController::class , 'destroy'])->name('pelanggan.destroy');
            
            // API untuk mencari pelanggan berdasarkan PID
            Route::get('/api/pelanggan/search', [PelangganController::class, 'searchByPid'])->name('api.pelanggan.search');
        }
        );

        Route::middleware([\App\Http\Middleware\EnsureUserIsSuperAdmin::class])->group(function () {
            Route::resource('users', \App\Http\Controllers\UserController::class);
            
            // Password reset routes
            Route::get('/password-reset-requests', [\App\Http\Controllers\UserController::class, 'passwordResetRequests'])->name('users.password-reset-requests');
            Route::get('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'showResetForm'])->name('users.reset-password.form');
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/password-reset-requests/{resetRequest}/reject', [\App\Http\Controllers\UserController::class, 'rejectPasswordResetRequest'])->name('users.password-reset.reject');
        }
        );
    });
