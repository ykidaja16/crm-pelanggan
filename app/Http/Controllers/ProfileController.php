<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil user yang sedang login.
     */
    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update profil user (username, email, password).
     * Password lama wajib dikonfirmasi sebelum mengubah password baru.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|max:255|unique:users,email,' . $user->id,
        ];

        $messages = [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan oleh user lain.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan oleh user lain.',
        ];

        // Jika ingin ganti password, validasi password lama dan baru
        $wantsChangePassword = $request->filled('new_password');
        if ($wantsChangePassword) {
            $rules['current_password'] = 'required|string';
            $rules['new_password']     = 'required|string|min:6|confirmed';
            $messages['current_password.required'] = 'Password lama wajib diisi untuk mengubah password.';
            $messages['new_password.required']     = 'Password baru wajib diisi.';
            $messages['new_password.min']          = 'Password baru minimal 6 karakter.';
            $messages['new_password.confirmed']    = 'Konfirmasi password baru tidak cocok.';
        }

        $validated = $request->validate($rules, $messages);

        // Cek password lama jika ingin ganti password
        if ($wantsChangePassword) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withInput()
                    ->withErrors(['current_password' => 'Password lama yang Anda masukkan salah.']);
            }
        }

        // Update data profil
        $user->name     = $validated['name'];
        $user->username = $validated['username'];
        $user->email    = $validated['email'];

        if ($wantsChangePassword) {
            $user->password = bcrypt($validated['new_password']);
        }

        $user->save();

        // Catat aktivitas
        ActivityLog::record(
            'update',
            'User',
            'User memperbarui profil sendiri (username: ' . $user->username . ').',
            $user->id,
            $user->username,
            $user->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', 'Profil berhasil diperbarui.' . ($wantsChangePassword ? ' Password juga telah diubah.' : ''));
    }
}
