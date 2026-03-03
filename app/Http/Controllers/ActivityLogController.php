<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter by tanggal mulai
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }

        // Filter by tanggal selesai
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Daftar user untuk dropdown filter
        $users = User::select('id', 'name', 'username')->orderBy('name')->get();

        // Daftar action untuk dropdown
        $actions = [
            'login'   => 'Login',
            'logout'  => 'Logout',
            'create'  => 'Tambah',
            'update'  => 'Ubah',
            'delete'  => 'Hapus',
            'import'  => 'Import',
            'restore' => 'Pulihkan',
        ];

        // Daftar module untuk dropdown
        $modules = [
            'auth'      => 'Autentikasi',
            'pelanggan' => 'Pelanggan',
            'user'      => 'User',
        ];

        return view('activity-log.index', compact('logs', 'users', 'actions', 'modules'));
    }

    public function export(Request $request)
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        }

        $logs = $query->get();

        $filename = 'log-aktivitas-' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new ActivityLogExport($logs), $filename);
    }
}
