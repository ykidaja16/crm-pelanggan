<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'cabangs'])->paginate(10);
        $pendingPasswordResets = \App\Models\PasswordResetRequest::where('status', 'pending')->count();
        return view('users.index', compact('users', 'pendingPasswordResets'));
    }

    public function create()
    {
        $roles   = \App\Models\Role::all();
        $cabangs = Cabang::orderBy('nama')->get();
        return view('users.create', compact('roles', 'cabangs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required',
            'username' => 'required|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id'  => 'required|exists:roles,id',
            'cabangs'  => 'required|array',
            'cabangs.*'=> 'exists:cabangs,id',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_active'] = true;

        $user = User::create(\Illuminate\Support\Arr::except($validated, ['cabangs']));

        // Sync cabang access
        $cabangIds = $request->input('cabangs', []);
        $user->cabangs()->sync($cabangIds);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        $roles   = \App\Models\Role::all();
        $cabangs = Cabang::orderBy('nama')->get();
        $userCabangIds = $user->cabangs()->pluck('cabangs.id')->toArray();
        return view('users.edit', compact('user', 'roles', 'cabangs', 'userCabangIds'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required',
            'username' => 'required|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role_id'  => 'required|exists:roles,id',
            'password' => 'nullable|min:6',
            'cabangs'  => 'required|array',
            'cabangs.*'=> 'exists:cabangs,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Selalu update is_active — checkbox HTML tidak dikirim saat unchecked,
        // sehingga $request->boolean() mengembalikan false jika tidak ada (nonaktif)
        // dan true jika ada (aktif).
        $validated['is_active'] = $request->boolean('is_active');

        $user->update(\Illuminate\Support\Arr::except($validated, ['cabangs']));

        // Sync cabang access
        $cabangIds = $request->input('cabangs', []);
        $user->cabangs()->sync($cabangIds);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        // Hitung related records untuk logging/cleanup check
        $requesterCount = $user->approvalRequestsAsRequester()->count();
        $reviewerCount = $user->approvalRequestsAsReviewer()->count();
        $historyCount = $user->pelangganClassHistories()->count();

        // Cek jika Super Admin dan punya task approval pending
        if ($user->role && $user->role->name === 'Super Admin') {
            $pendingApprovals = $user->assignedApprovalRequests()->where('status', 'pending')->count();
            if ($pendingApprovals > 0) {
                return back()->with('error', "Superadmin '{$user->name}' tidak bisa dihapus. Ada {$pendingApprovals} task approval pending yang harus diselesaikan terlebih dahulu.");
            }
        }

        // Hapus related approval requests (sebagai requester, reviewer)
        if ($requesterCount > 0) {
            $user->approvalRequestsAsRequester()->delete();
        }
        if ($reviewerCount > 0) {
            $user->approvalRequestsAsReviewer()->delete();
        }

        // Hapus related class histories
        if ($historyCount > 0) {
            $user->pelangganClassHistories()->delete();
        }

        // Detach semua relasi cabang sebelum force delete
        $user->cabangs()->detach();

        // Final delete
        $user->forceDelete();

        $message = "User '{$user->name}' berhasil dihapus. Dihapus: {$requesterCount} approval (requester), {$reviewerCount} approval (reviewer), {$historyCount} history.";
        return redirect()->route('users.index')->with('success', $message);
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
