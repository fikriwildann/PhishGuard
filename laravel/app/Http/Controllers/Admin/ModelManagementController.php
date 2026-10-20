<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MlModel;
use App\Models\ModelActivationLog;
use App\Services\PhishGuardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

/**
 * Controller untuk mengelola lifecycle Model Machine Learning:
 * Upload, Validasi, Review, Aktivasi, Rollback, dan Arsip.
 */
class ModelManagementController extends Controller
{
    public function __construct(
        protected PhishGuardService $service
    ) {}

    /**
     * Tampilkan daftar semua model yang terdaftar di sistem.
     */
    public function index(Request $request): View
    {
        $query = MlModel::with('uploader', 'activator')->withCount('scans')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('algorithm')) {
            $query->where('algorithm', $request->algorithm);
        }

        $models = $query->paginate(10)->withQueryString();
        $activeModel = MlModel::getActiveModel();

        return view('admin.models.index', compact('models', 'activeModel'));
    }

    /**
     * Tampilkan halaman khusus Active Model saat ini.
     */
    public function active(): View
    {
        $activeModel = MlModel::with('uploader', 'activator')->withCount('scans')->where('status', MlModel::STATUS_ACTIVE)->first();
        $activationHistory = ModelActivationLog::with('admin', 'previousModel', 'newModel')->latest()->take(5)->get();

        return view('admin.models.active', compact('activeModel', 'activationHistory'));
    }

    /**
     * Tampilkan formulir unggah model baru.
     */
    public function create(): View
    {
        return view('admin.models.upload');
    }

    /**
     * Proses unggah model baru (file .zip bundle atau .pkl + metadata.json).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'model_archive' => ['required', 'file', 'mimes:zip', 'max:51200'], // Maksimal 50MB zip bundle
            'name' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\.\_\-]+$/'],
            'algorithm' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('model_archive');
        $bundleName = Str::slug($request->algorithm . '_' . $request->version, '_');

        // Target path penyimpanan internal ML models
        $targetDir = base_path('../ml/models/' . $bundleName);

        if (File::exists($targetDir)) {
            return back()->withInput()->with('error', "Bundle model dengan nama '{$bundleName}' sudah ada. Silakan gunakan versi yang berbeda.");
        }

        // Ekstrak ZIP bundle dengan proteksi Path Traversal (Zip Slip)
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                    $zip->close();
                    return back()->withInput()->with('error', 'Arsip zip tidak valid (terdeteksi path traversal).');
                }
            }

            File::makeDirectory($targetDir, 0755, true);
            $zip->extractTo($targetDir);
            $zip->close();
        } else {
            return back()->withInput()->with('error', 'Gagal mengekstrak berkas arsip zip model.');
        }

        // Periksa keberadaan berkas wajib: model.pkl, metadata.json, feature_names.json
        $modelPkl = $targetDir . '/model.pkl';
        $metadataJson = $targetDir . '/metadata.json';
        $featuresJson = $targetDir . '/feature_names.json';

        if (! File::exists($modelPkl) || ! File::exists($metadataJson) || ! File::exists($featuresJson)) {
            File::deleteDirectory($targetDir);
            return back()->withInput()->with('error', 'Bundle zip tidak lengkap! Wajib memuat berkas: model.pkl, metadata.json, dan feature_names.json.');
        }

        // Baca metadata
        $meta = json_decode(File::get($metadataJson), true) ?? [];
        $features = json_decode(File::get($featuresJson), true) ?? [];

        $fileHash = hash_file('sha256', $modelPkl);

        // Simpan ke database dengan status INACTIVE
        $newModel = MlModel::create([
            'name' => $request->name,
            'version' => $request->version,
            'algorithm' => $request->algorithm,
            'bundle_name' => $bundleName,
            'file_path' => 'models/' . $bundleName . '/model.pkl',
            'file_hash' => $fileHash,
            'dataset_name' => $meta['dataset']['name'] ?? 'Hannousse & Yahiouche Dataset B',
            'feature_count' => count($features),
            'feature_names' => $features,
            'accuracy' => (float) ($meta['metrics']['accuracy'] ?? 0.85),
            'precision' => (float) ($meta['metrics']['precision'] ?? 0.85),
            'recall' => (float) ($meta['metrics']['recall'] ?? 0.85),
            'f1_score' => (float) ($meta['metrics']['f1_score'] ?? 0.85),
            'roc_auc' => (float) ($meta['metrics']['roc_auc'] ?? 0.90),
            'description' => $request->description ?? ($meta['description'] ?? null),
            'status' => MlModel::STATUS_INACTIVE,
            'uploaded_by' => Auth::id(),
        ]);

        ActivityLog::record(
            ActivityLog::UPLOAD_MODEL,
            "Admin mengunggah model baru: {$newModel->name} v{$newModel->version} (Bundle: {$bundleName})"
        );

        return redirect()->route('admin.models.show', $newModel->id)
            ->with('success', 'Model berhasil diunggah! Silakan lakukan validasi sebelum aktivasi.');
    }

    /**
     * Tampilkan detail teknis model, metrik, serta panel validasi/aktivasi.
     */
    public function show(int $id): View
    {
        $model = MlModel::with('uploader', 'activator')->withCount('scans')->findOrFail($id);
        $activeModel = MlModel::getActiveModel();

        return view('admin.models.show', compact('model', 'activeModel'));
    }

    /**
     * PHASE 29: Validasi model terhadap FastAPI model manager.
     */
    public function validateBundle(int $id): RedirectResponse
    {
        $model = MlModel::findOrFail($id);
        $res = $this->service->validateModel($model->bundle_name, $model->id);

        if ($res['ok'] ?? false) {
            return back()->with('validation_success', 'Model lolos seluruh 10 uji validasi dan kompatibel penuh dengan feature extractor.');
        }

        $errors = implode(', ', $res['errors'] ?? ['Validasi gagal']);
        return back()->with('validation_error', "Model tidak kompatibel dengan sistem: {$errors}");
    }

    /**
     * PHASE 30: Aktivasi model menjadi Active Model produksi.
     */
    public function activate(Request $request, int $id): RedirectResponse
    {
        $model = MlModel::findOrFail($id);
        $currentActive = MlModel::getActiveModel();

        if ($currentActive && $currentActive->id === $model->id) {
            return back()->with('error', 'Model ini sudah berstatus aktif.');
        }

        // Panggil FastAPI reload-model
        $reload = $this->service->reloadModel($model->bundle_name, $model->id);

        if (! ($reload['ok'] ?? false)) {
            $err = implode(', ', $reload['errors'] ?? ['AI engine menolak aktivasi']);
            return back()->with('error', "Gagal mengaktifkan model di AI engine: {$err}");
        }

        // Update database: Nonaktifkan model lama
        if ($currentActive) {
            $currentActive->update(['status' => MlModel::STATUS_INACTIVE]);
        }

        // Aktifkan model baru
        $model->update([
            'status' => MlModel::STATUS_ACTIVE,
            'activated_by' => Auth::id(),
            'activated_at' => now(),
        ]);

        // Catat di model activation logs
        ModelActivationLog::create([
            'previous_model_id' => $currentActive?->id,
            'new_model_id' => $model->id,
            'action' => ModelActivationLog::ACTION_ACTIVATE,
            'admin_id' => Auth::id(),
            'reason' => $request->input('reason', 'Aktivasi model produksi oleh administrator.'),
        ]);

        // Catat activity log
        ActivityLog::record(
            ActivityLog::ACTIVATE_MODEL,
            "Admin mengaktifkan model {$model->name} v{$model->version} menggantikan " . ($currentActive ? "{$currentActive->name} v{$currentActive->version}" : 'tidak ada')
        );

        return redirect()->route('admin.models.active')->with('success', "Model {$model->name} v{$model->version} berhasil diaktifkan sebagai model produksi!");
    }

    /**
     * PHASE 31: Rollback ke model sebelumnya.
     */
    public function rollback(Request $request, int $id): RedirectResponse
    {
        $targetModel = MlModel::findOrFail($id);
        $currentActive = MlModel::getActiveModel();

        if ($targetModel->isActive()) {
            return back()->with('error', 'Model ini sedang aktif saat ini.');
        }

        $reload = $this->service->reloadModel($targetModel->bundle_name, $targetModel->id);
        if (! ($reload['ok'] ?? false)) {
            return back()->with('error', 'Gagal melakukan rollback di AI Engine.');
        }

        if ($currentActive) {
            $currentActive->update(['status' => MlModel::STATUS_INACTIVE]);
        }

        $targetModel->update([
            'status' => MlModel::STATUS_ACTIVE,
            'activated_by' => Auth::id(),
            'activated_at' => now(),
        ]);

        ModelActivationLog::create([
            'previous_model_id' => $currentActive?->id,
            'new_model_id' => $targetModel->id,
            'action' => ModelActivationLog::ACTION_ROLLBACK,
            'admin_id' => Auth::id(),
            'reason' => $request->input('reason', 'Rollback ke versi sebelumnya oleh administrator.'),
        ]);

        ActivityLog::record(
            ActivityLog::ROLLBACK_MODEL,
            "Admin melakukan rollback model ke {$targetModel->name} v{$targetModel->version}"
        );

        return redirect()->route('admin.models.active')->with('success', "Rollback berhasil! Model {$targetModel->name} v{$targetModel->version} kini aktif kembali.");
    }

    /**
     * Tampilkan riwayat aktivasi & rollback model.
     */
    public function history(): View
    {
        $logs = ModelActivationLog::with('admin', 'previousModel', 'newModel')->latest()->paginate(15);
        return view('admin.models.history', compact('logs'));
    }
}
