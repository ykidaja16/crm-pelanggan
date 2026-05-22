<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResetRequest;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['username' => 'required|string']);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => 'Username tidak ditemukan.']);
        }

        // Cek jika sudah ada pending request
        $existing = PasswordResetRequest::where('user_id', $user->id)
            ->where('status', 'pending')->first();

        if (!$existing) {
            PasswordResetRequest::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        }

        return back()->with('status', 'Permintaan reset password telah dikirim. Silakan hubungi tim IT untuk proses selanjutnya.');
    }
}
