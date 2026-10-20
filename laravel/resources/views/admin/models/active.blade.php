@extends('layouts.admin')

@section('title', 'Active Model Produksi')
@section('page-title', 'Active Model Monitor')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Active Production Model ⚡
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Model Machine Learning yang saat ini bertanggung jawab menangani seluruh inferensi prediksi URL.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.models.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                &larr; Semua Model
            </a>
        </div>
    </div>

    @if($activeModel)
        <!-- Kartu Detail Model Aktif -->
        <div class="bg-slate-900/90 border border-purple-500/30 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30 mb-2">
                        Status: Active & Serving
                    </span>
                    <h2 class="text-2xl font-bold text-white">{{ $activeModel->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Versi: <strong class="text-purple-300 font-mono">v{{ $activeModel->version }}</strong> • Algoritma: {{ $activeModel->algorithm }} • Diaktifkan Pada: {{ $activeModel->activated_at?->format('d M Y, H:i') ?? '-' }}
                    </p>
                </div>

                <div class="text-right">
                    <span class="text-xs text-slate-400 block uppercase">Total URL Diprediksi</span>
                    <span class="text-3xl font-extrabold text-white font-mono">{{ number_format($activeModel->scans_count) }}</span>
                </div>
            </div>

            <!-- Metrik Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center border-t border-slate-800 pt-6">
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800">
                    <span class="text-[10px] text-slate-500 block uppercase">Accuracy</span>
                    <strong class="text-lg font-bold text-emerald-400">{{ number_format($activeModel->accuracy * 100, 2) }}%</strong>
                </div>
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800">
                    <span class="text-[10px] text-slate-500 block uppercase">Precision</span>
                    <strong class="text-lg font-bold text-slate-200">{{ number_format($activeModel->precision * 100, 2) }}%</strong>
                </div>
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800">
                    <span class="text-[10px] text-slate-500 block uppercase">Recall</span>
                    <strong class="text-lg font-bold text-slate-200">{{ number_format($activeModel->recall * 100, 2) }}%</strong>
                </div>
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800">
                    <span class="text-[10px] text-slate-500 block uppercase">F1-Score</span>
                    <strong class="text-lg font-bold text-purple-300">{{ number_format($activeModel->f1_score * 100, 2) }}%</strong>
                </div>
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800">
                    <span class="text-[10px] text-slate-500 block uppercase">ROC-AUC</span>
                    <strong class="text-lg font-bold text-indigo-400">{{ number_format($activeModel->roc_auc * 100, 2) }}%</strong>
                </div>
            </div>
        </div>

        <!-- Riwayat Pergantian Model (Activation History) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Riwayat Log Aktivasi Terakhir</h3>
                <a href="{{ route('admin.models.history') }}" class="text-xs text-indigo-400 hover:underline">
                    Semua Log Aktivasi &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-800/60 text-xs">
                @forelse($activationHistory as $log)
                    <div class="px-6 py-3.5 flex items-center justify-between hover:bg-slate-800/30">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded font-mono font-semibold uppercase text-[10px] {{ $log->action === 'activate' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' }}">
                                {{ $log->action }}
                            </span>
                            <div>
                                <p class="text-slate-200 font-medium">
                                    {{ $log->newModel?->name ?? 'Model' }} (v{{ $log->newModel?->version ?? '-' }})
                                    @if($log->previousModel)
                                        <span class="text-slate-500 font-normal">menggantikan v{{ $log->previousModel->version }}</span>
                                    @endif
                                </p>
                                <p class="text-slate-500 text-[11px]">Oleh: {{ $log->admin?->name ?? 'Admin' }} • Alasan: {{ $log->reason ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="text-slate-500 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-500 text-xs">Belum ada riwayat aktivasi tercatat.</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-400">
            Belum ada active model yang terkonfigurasi di sistem.
        </div>
    @endif
</div>
@endsection
