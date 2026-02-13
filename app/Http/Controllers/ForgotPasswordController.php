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
        $request->validate(['email' => 'required|email']);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        // Create password reset request record (tidak mengirim email)
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Tampilkan pesan bahwa request telah dikirim ke superadmin
        return back()->with(['status' => 'Permintaan reset password telah dikirim. Superadmin akan mereset password Anda.']);
    }
}
