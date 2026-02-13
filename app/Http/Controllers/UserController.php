<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(10);
        $pendingPasswordResets = \App\Models\PasswordResetRequest::where('status', 'pending')->count();
        return view('users.index', compact('users', 'pendingPasswordResets'));
    }

    public function create()
    {
        $roles = \App\Models\Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        $roles = \App\Models\Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|min:6', // Optional password update
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }
        else {
            unset($validated['password']);
        }

        // Handle is_active status if sent
        if ($request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');
        }

        $user->update($validated);

        // Explicitly save the boolean if not in fillable or handled by update
        if ($request->has('is_active')) {
            $user->save();
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }

    /**
     * Show pending password reset requests
     */
    public function passwordResetRequests()
    {
        $requests = \App\Models\PasswordResetRequest::with(['user', 'processedBy'])
            ->orderBy('requested_at', 'desc')
            ->paginate(10);
            
        return view('users.password-reset-requests', compact('requests'));
    }

    /**
     * Show form to reset user password
     */
    public function showResetForm(User $user)
    {
        return view('users.reset-password', compact('user'));
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        // Update the password reset request status
        $resetRequest = \App\Models\PasswordResetRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($resetRequest) {
            $resetRequest->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => Auth::id(),
            ]);
        }

        return redirect()->route('users.password-reset-requests')->with('success', 'Password user berhasil direset.');
    }

    /**
     * Reject password reset request
     */
    public function rejectPasswordResetRequest(\App\Models\PasswordResetRequest $resetRequest)
    {
        $resetRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Permintaan reset password telah ditolak.');
    }
}
