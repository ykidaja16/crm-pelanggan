<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ForgotPasswordController;

// Auth
Route::get('/login', [LoginController::class , 'index'])->name('login');
Route::post('/login', [LoginController::class , 'authenticate']);
Route::post('/logout', [LoginController::class , 'logout'])->name('logout');

// Forgot Password
Route::get('password/reset', [ForgotPasswordController::class , 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class , 'sendResetLinkEmail'])->name('password.email');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [PelangganController::class , 'index'])->name('dashboard');
    Route::get('/pelanggan', [PelangganController::class , 'index'])->name('pelanggan.index');
    Route::get('/', function () {
            return redirect()->route('dashboard');
        }
        );

        Route::post('/import', [PelangganController::class , 'import'])->name('pelanggan.import');
        Route::get('/export', [PelangganController::class , 'export'])->name('pelanggan.export');

        Route::middleware([\App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
            Route::get('/pelanggan/create', [PelangganController::class , 'create'])->name('pelanggan.create');
            Route::post('/pelanggan', [PelangganController::class , 'store'])->name('pelanggan.store');
            Route::get('/pelanggan/{pelanggan}/show', [PelangganController::class , 'show'])->name('pelanggan.show');
            Route::get('/pelanggan/{pelanggan}/edit', [PelangganController::class , 'edit'])->name('pelanggan.edit');
            Route::put('/pelanggan/{pelanggan}', [PelangganController::class , 'update'])->name('pelanggan.update');
            Route::delete('/pelanggan/{pelanggan}', [PelangganController::class , 'destroy'])->name('pelanggan.destroy');
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
