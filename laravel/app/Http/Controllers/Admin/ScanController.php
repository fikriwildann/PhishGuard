<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk mengelola seluruh data pemindaian URL sistem oleh Administrator.
 */
class ScanController extends Controller
{
    /**
     * Tampilkan seluruh riwayat scan dari semua user dengan pencarian, filter, dan paginasi.
     */
    public function index(Request $request): View
    {
        $query = Scan::with('user', 'mlModel')->latest();

        // 1. Pencarian URL atau Nama/Email User
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Prediksi
        if ($request->filled('prediction') && in_array($request->prediction, ['Phishing', 'Legitimate'], true)) {
            $query->where('prediction', $request->prediction);
        }

        // 3. Filter Tingkat Risiko
        if ($request->filled('risk_level') && in_array($request->risk_level, ['Low', 'Medium', 'High'], true)) {
            $query->where('risk_level', $request->risk_level);
        }

        // 4. Filter Versi Model
        if ($request->filled('model_version')) {
            $query->where('model_version', $request->model_version);
        }

        $scans = $query->paginate(15)->withQueryString();

        // Statistik global untuk header
        $totalScans = Scan::count();
        $totalPhishing = Scan::phishing()->count();
        $totalLegitimate = Scan::legitimate()->count();
        $availableModelVersions = Scan::select('model_version')->distinct()->pluck('model_version');

        return view('admin.scans.index', compact(
            'scans',
            'totalScans',
            'totalPhishing',
            'totalLegitimate',
            'availableModelVersions'
        ));
    }

    /**
     * Tampilkan rincian lengkap suatu hasil pemindaian.
     */
    public function show(int $id): View
    {
        $scan = Scan::with('user', 'mlModel')->findOrFail($id);

        return view('admin.scans.show', compact('scan'));
    }

    /**
     * Hapus data pemindaian oleh Administrator.
     */
    public function destroy(int $id): RedirectResponse
    {
        $scan = Scan::findOrFail($id);
        $url = $scan->url;
        $scanId = $scan->id;

        $scan->delete();

        ActivityLog::record(
            ActivityLog::DELETE_SCAN,
            "Admin menghapus riwayat scan #{$scanId} (URL: {$url})"
        );

        return redirect()->route('admin.scans.index')->with('success', "Riwayat pemindaian #{$scanId} berhasil dihapus.");
    }
}
