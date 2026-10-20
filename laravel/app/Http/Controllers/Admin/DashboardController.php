<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MlModel;
use App\Models\Scan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controller untuk Dashboard Administrator PhishGuard.
 * Mengagregasi metrik sistem global, grafik analitik, dan log aktivitas real-time.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama Admin Dashboard.
     */
    public function index(): View
    {
        // 1. Statistik Global Sistem
        $totalUsers = User::count();
        $totalScans = Scan::count();
        $totalPhishing = Scan::phishing()->count();
        $totalLegitimate = Scan::legitimate()->count();
        $phishingRate = $totalScans > 0 ? round(($totalPhishing / $totalScans) * 100, 1) : 0;

        // Model Aktif saat ini
        $activeModel = MlModel::getActiveModel();

        // 2. Data Grafik 1: Tren Scan per Hari (7 Hari Terakhir)
        $dailyScansData = [];
        $dailyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dailyLabels[] = $date->format('d M');
            $dailyScansData[] = Scan::whereDate('created_at', $date)->count();
        }

        // 3. Data Grafik 2: Scan per Model Version
        $scansByModel = Scan::select('model_version', DB::raw('count(*) as count'))
            ->groupBy('model_version')
            ->orderByDesc('count')
            ->pluck('count', 'model_version')
            ->toArray();

        // 4. Data Grafik 3: User Growth (7 Hari Terakhir)
        $userGrowthData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $userGrowthData[] = User::whereDate('created_at', $date)->count();
        }

        // 5. 6 Log Aktivitas Sistem Terbaru
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalScans',
            'totalPhishing',
            'totalLegitimate',
            'phishingRate',
            'activeModel',
            'dailyLabels',
            'dailyScansData',
            'scansByModel',
            'userGrowthData',
            'recentActivities'
        ));
    }
}
