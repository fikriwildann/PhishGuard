<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MlModel;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller untuk mengelola Dashboard Pengguna.
 * Menghitung dan menyajikan ringkasan statistik pemindaian serta riwayat terbaru milik user.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama Dashboard Pengguna.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Statistik pemindaian milik pengguna sendiri
        $totalScans = $user->scans()->count();
        $phishingCount = $user->scans()->phishing()->count();
        $legitimateCount = $user->scans()->legitimate()->count();
        $phishingRate = $totalScans > 0 ? round(($phishingCount / $totalScans) * 100, 1) : 0;

        // 2. 5 riwayat pemindaian terakhir
        $latestScans = $user->scans()
            ->latest()
            ->take(5)
            ->get();

        // 3. Informasi model aktif produksi yang digunakan sistem
        $activeModel = MlModel::getActiveModel();

        return view('user.dashboard', compact(
            'totalScans',
            'phishingCount',
            'legitimateCount',
            'phishingRate',
            'latestScans',
            'activeModel'
        ));
    }
}
