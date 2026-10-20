<?php

namespace App\Http\Controllers\User;

use App\Exceptions\MlServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScanUrlRequest;
use App\Models\ActivityLog;
use App\Models\MlModel;
use App\Models\Scan;
use App\Services\PhishGuardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller untuk menangani pemindaian URL phishing secara real-time.
 * Menghubungkan input pengguna ke layanan FastAPI Machine Learning dan menyimpan hasilnya ke database MySQL.
 */
class ScannerController extends Controller
{
    public function __construct(
        protected PhishGuardService $service
    ) {}

    /**
     * Tampilkan formulir pemindai URL.
     */
    public function index(Request $request): View
    {
        $health = $this->service->health();
        $initialUrl = $request->query('url', '');

        return view('user.scanner', [
            'health' => $health,
            'initialUrl' => $initialUrl,
            'scan' => null,
        ]);
    }

    /**
     * Eksekusi analisis URL melalui layanan Machine Learning dan simpan ke database.
     */
    public function scan(ScanUrlRequest $request): View
    {
        $health = $this->service->health();
        $targetUrl = $request->url;

        try {
            // 1. Eksekusi inferensi ML melalui FastAPI
            $result = $this->service->predict($targetUrl);
        } catch (MlServiceException $e) {
            return view('user.scanner', [
                'health' => $health,
                'initialUrl' => $targetUrl,
                'scan' => null,
            ])->with('error', $e->userMessage());
        }

        // 2. Hubungkan dengan ID Model di database MySQL
        $model = MlModel::where('name', $result->modelName)
            ->where('version', $result->modelVersion)
            ->first() ?? MlModel::getActiveModel();

        // 3. Simpan hasil pemindaian ke tabel `scans`
        $scan = Scan::create([
            'user_id' => Auth::id(),
            'url' => $result->url,
            'prediction' => $result->prediction,
            'probability' => $result->probability,
            'risk_score' => $result->riskScore,
            'risk_level' => $result->riskLevel,
            'model_id' => $model?->id,
            'model_name' => $result->modelName,
            'model_version' => $result->modelVersion,
            'message' => $result->message,
            'indicators' => $result->indicators,
        ]);

        // 4. Catat aktivitas pemindaian ke tabel `activity_logs`
        ActivityLog::record(
            ActivityLog::SCAN_URL,
            "Memindai URL: {$result->url} (Prediksi: {$result->prediction}, Skor Risiko: {$result->riskScore}%)"
        );

        return view('user.scanner', [
            'health' => $health,
            'initialUrl' => $targetUrl,
            'scan' => $scan,
        ])->with('success', 'URL berhasil dianalisis oleh model AI.');
    }
}
