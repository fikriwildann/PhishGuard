<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller untuk mengelola riwayat pemindaian URL milik pengguna.
 * Menerapkan isolasi data ketat: Pengguna hanya dapat melihat dan menghapus data miliknya sendiri.
 */
class HistoryController extends Controller
{
    /**
     * Tampilkan daftar riwayat pemindaian milik pengguna dengan filter & pagination.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = $user->scans()->latest();

        // 1. Filter Pencarian URL
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where('url', 'like', "%{$search}%");
        }

        // 2. Filter Status Prediksi
        if ($request->filled('prediction') && in_array($request->prediction, ['Phishing', 'Legitimate'], true)) {
            $query->where('prediction', $request->prediction);
        }

        // 3. Filter Tingkat Risiko
        if ($request->filled('risk_level') && in_array($request->risk_level, ['Low', 'Medium', 'High'], true)) {
            $query->where('risk_level', $request->risk_level);
        }

        // 4. Pagination (10 data per halaman dengan query string bertahan)
        $scans = $query->paginate(10)->withQueryString();

        // Statistik ringkas untuk header riwayat
        $totalCount = $user->scans()->count();
        $phishingCount = $user->scans()->phishing()->count();
        $legitimateCount = $user->scans()->legitimate()->count();

        return view('user.history.index', compact(
            'scans',
            'totalCount',
            'phishingCount',
            'legitimateCount'
        ));
    }

    /**
     * Tampilkan rincian lengkap satu hasil pemindaian.
     */
    public function show(int $id): View
    {
        $scan = Scan::findOrFail($id);

        // Otorisasi: Pengguna hanya boleh melihat data miliknya sendiri
        if ($scan->user_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda hanya diizinkan melihat riwayat pemindaian milik Anda sendiri.');
        }

        return view('user.history.show', compact('scan'));
    }

    /**
     * Hapus satu riwayat pemindaian milik pengguna sendiri.
     */
    public function destroy(int $id): RedirectResponse
    {
        $scan = Scan::findOrFail($id);

        // Otorisasi: Pengguna hanya boleh menghapus data miliknya sendiri
        if ($scan->user_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda hanya berhak menghapus riwayat pemindaian milik Anda sendiri.');
        }

        $url = $scan->url;
        $scanId = $scan->id;

        $scan->delete();

        // Catat jejak audit penghapusan
        ActivityLog::record(
            ActivityLog::DELETE_SCAN,
            "Pengguna menghapus riwayat scan #{$scanId} (URL: {$url})"
        );

        return redirect()->route('history.index')->with('success', 'Riwayat pemindaian berhasil dihapus.');
    }
}
