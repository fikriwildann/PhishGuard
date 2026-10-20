<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk mengelola dan memantau audit trail Activity Logs oleh Administrator.
 */
class ActivityLogController extends Controller
{
    /**
     * Tampilkan seluruh rekaman jejak audit sistem dengan pencarian dan filter tipe aktivitas.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest();

        // 1. Pencarian teks deskripsi, nama user, email, atau alamat IP
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Tipe Aktivitas
        if ($request->filled('activity')) {
            $query->where('activity', $request->activity);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Daftar jenis aktivitas untuk dropdown filter
        $activityTypes = [
            ActivityLog::LOGIN => 'Login',
            ActivityLog::LOGOUT => 'Logout',
            ActivityLog::SCAN_URL => 'Scan URL',
            ActivityLog::UPLOAD_MODEL => 'Upload Model',
            ActivityLog::ACTIVATE_MODEL => 'Activate Model',
            ActivityLog::ROLLBACK_MODEL => 'Rollback Model',
            ActivityLog::DELETE_SCAN => 'Delete Scan',
            ActivityLog::UPDATE_PROFILE => 'Update User/Profile',
            ActivityLog::CHANGE_PASSWORD => 'Change Password',
        ];

        return view('admin.activity-logs.index', compact('logs', 'activityTypes'));
    }
}
