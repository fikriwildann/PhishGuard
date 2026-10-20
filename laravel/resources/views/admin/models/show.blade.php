@extends('layouts.admin')

@section('title', 'Detail Model: ' . $model->name)
@section('page-title', 'Model Details & Validation')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.models.index') }}" class="hover:text-indigo-400 transition-colors">Model Management</a>
        <span>/</span>
        <span class="text-slate-200">{{ $model->name }} (v{{ $model->version }})</span>
    </div>

    <!-- Alert Hasil Validasi -->
    @if(session('validation_success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-sm text-emerald-400 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('validation_success') }}</span>
        </div>
    @endif

    @if(session('validation_error'))
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-sm text-rose-400 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('validation_error') }}</span>
        </div>
    @endif

    <!-- Kartu Informasi & Aksi Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informasi Metrik Model (2 Kolom) -->
        <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">{{ $model->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Algoritma: <strong class="text-slate-200">{{ $model->algorithm }}</strong> • Versi: <span class="font-mono text-purple-300">v{{ $model->version }}</span> • Bundle: <code class="text-indigo-300">{{ $model->bundle_name }}</code>
                    </p>
                </div>
                <div>
                    @if($model->isActive())
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            ⚡ Active Production
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-medium uppercase bg-slate-800 text-slate-400">
                            {{ $model->status }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Metrik Evaluasi -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Metrik Evaluasi Pengujian</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block uppercase">Accuracy</span>
                        <strong class="text-base font-bold text-emerald-400">{{ number_format($model->accuracy * 100, 2) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block uppercase">Precision</span>
                        <strong class="text-base font-bold text-slate-200">{{ number_format($model->precision * 100, 2) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block uppercase">Recall</span>
                        <strong class="text-base font-bold text-slate-200">{{ number_format($model->recall * 100, 2) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block uppercase">F1-Score</span>
                        <strong class="text-base font-bold text-purple-300">{{ number_format($model->f1_score * 100, 2) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block uppercase">ROC-AUC</span>
                        <strong class="text-base font-bold text-indigo-400">{{ number_format($model->roc_auc * 100, 2) }}%</strong>
                    </div>
                </div>
            </div>

            <!-- Detail Teknis & Dataset -->
            <div class="border-t border-slate-800 pt-4 space-y-2 text-xs">
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Dataset Pelatihan</span>
                    <span class="text-slate-300 font-medium">{{ $model->dataset_name ?? 'Hannousse Phishing Dataset' }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Jumlah Fitur Leksikal</span>
                    <span class="text-slate-300 font-mono">{{ $model->feature_count }} fitur</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">SHA-256 Hash Berkas</span>
                    <span class="text-slate-400 font-mono truncate max-w-xs" title="{{ $model->file_hash }}">{{ $model->file_hash }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Diunggah Oleh</span>
                    <span class="text-slate-300">{{ $model->uploader?->name ?? 'Admin' }} ({{ $model->created_at->format('d M Y, H:i') }})</span>
                </div>
            </div>
        </div>

        <!-- Panel Kontrol: Validasi & Aktivasi (1 Kolom) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
            <h3 class="text-base font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Kontrol Model
            </h3>

            <!-- 1. Tombol Validasi (Phase 29) -->
            <div class="rounded-xl bg-slate-950/70 p-4 border border-slate-800 space-y-2">
                <p class="text-xs font-semibold text-slate-300">1. Uji Kompatibilitas</p>
                <p class="text-[11px] text-slate-400">Jalankan 10 tes validasi: berkas, joblib unpickler aman, feature count & dummy predict.</p>
                <form action="{{ route('admin.models.validate', $model->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full mt-2 py-2 px-3 rounded-lg border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 text-xs font-semibold transition-colors">
                        Jalankan Validasi Model
                    </button>
                </form>
            </div>

            <!-- 2. Tombol Aktivasi (Phase 30) -->
            <div class="rounded-xl bg-slate-950/70 p-4 border border-slate-800 space-y-2">
                <p class="text-xs font-semibold text-slate-300">2. Aktivasi Produksi</p>
                <p class="text-[11px] text-slate-400">Jadikan model ini sebagai model utama untuk melayani prediksi pemindaian URL.</p>

                @if(! $model->isActive())
                    <form action="{{ route('admin.models.activate', $model->id) }}" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan model ini sebagai model produksi aktif?')">
                        @csrf
                        <input type="text" name="reason" placeholder="Alasan aktivasi (opsional)..."
                               class="w-full mb-2 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-900 text-slate-100 text-xs">
                        <button type="submit"
                                class="w-full py-2.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md shadow-emerald-600/30 transition-all">
                            Aktifkan Model Sekarang
                        </button>
                    </form>
                @else
                    <div class="text-xs text-purple-300 bg-purple-500/10 border border-purple-500/20 p-2.5 rounded-lg text-center font-semibold">
                        Model ini sedang aktif di produksi
                    </div>
                @endif
            </div>

            <!-- 3. Rollback jika model lain aktif (Phase 31) -->
            @if(! $model->isActive() && $model->scans_count > 0)
                <form action="{{ route('admin.models.rollback', $model->id) }}" method="POST"
                      onsubmit="return confirm('Apakah Anda yakin ingin rollback ke versi model ini?')">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-lg border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-semibold transition-colors">
                        ↺ Rollback ke Model Ini
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
