@extends('layouts.admin')

@section('title', 'Manajemen Model Machine Learning')
@section('page-title', 'Model Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Manajemen Model Machine Learning 🧠
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Kelola versi model, lakukan evaluasi metrik, dan kendalikan active model produksi secara dinamis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.models.active') }}"
               class="px-4 py-2.5 rounded-xl border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 text-xs sm:text-sm font-semibold transition-colors">
                ⚡ Active Model
            </a>
            <a href="{{ route('admin.models.upload') }}"
               class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Unggah Model Baru
            </a>
        </div>
    </div>

    <!-- Banner Model Aktif Produksi -->
    @if($activeModel)
        <div class="bg-gradient-to-r from-purple-950/40 via-slate-900 to-slate-900 border border-purple-500/30 rounded-2xl p-6 shadow-xl">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30 mb-2">
                        <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                        Active Production Model
                    </span>
                    <h2 class="text-xl font-bold text-white">{{ $activeModel->name }}</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Versi <strong class="text-purple-300 font-mono">v{{ $activeModel->version }}</strong> • Algoritma: {{ $activeModel->algorithm }} • Diaktifkan: {{ $activeModel->activated_at?->format('d M Y, H:i') ?? '-' }}
                    </p>
                </div>

                <div class="flex items-center gap-4 text-center">
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 block uppercase">Accuracy</span>
                        <strong class="text-sm font-bold text-emerald-400">{{ number_format($activeModel->accuracy * 100, 1) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 block uppercase">F1-Score</span>
                        <strong class="text-sm font-bold text-purple-300">{{ number_format($activeModel->f1_score * 100, 1) }}%</strong>
                    </div>
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 block uppercase">ROC-AUC</span>
                        <strong class="text-sm font-bold text-indigo-400">{{ number_format($activeModel->roc_auc * 100, 1) }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabel Seluruh Model Tersedia -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-semibold text-white">Daftar Seluruh Model di Registry</h3>
            <a href="{{ route('admin.models.history') }}" class="text-xs text-indigo-400 hover:underline">
                Riwayat Aktivasi & Rollback &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4 font-medium">Model & Versi</th>
                        <th class="px-6 py-4 font-medium">Algoritma</th>
                        <th class="px-6 py-4 font-medium">Metrik (Acc / F1 / AUC)</th>
                        <th class="px-6 py-4 font-medium">Fitur</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-normal">
                    @forelse($models as $m)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-bold text-white">{{ $m->name }}</p>
                                <p class="text-xs font-mono text-purple-300">v{{ $m->version }} ({{ $m->bundle_name }})</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-300">
                                {{ $m->algorithm }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <span class="text-emerald-400">{{ number_format($m->accuracy * 100, 1) }}%</span> /
                                <span class="text-purple-300 font-bold">{{ number_format($m->f1_score * 100, 1) }}%</span> /
                                <span class="text-indigo-400">{{ number_format($m->roc_auc * 100, 1) }}%</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400 font-mono">
                                {{ $m->feature_count }} fitur
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($m->isActive())
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                        ⚡ Active
                                    </span>
                                @elseif($m->isArchived())
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase bg-slate-800 text-slate-400">
                                        Archived
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase bg-slate-800 text-slate-300">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.models.show', $m->id) }}"
                                   class="inline-block px-3 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-medium transition-colors">
                                    Kelola & Validasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-500 text-xs">Belum ada model yang diunggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
            {{ $models->links() }}
        </div>
    </div>
</div>
@endsection
