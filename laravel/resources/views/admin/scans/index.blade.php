@extends('layouts.admin')

@section('title', 'Manajemen Pemindaian Global')
@section('page-title', 'Scan Management')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Metrik Ringkas -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Manajemen Pemindaian Global 🌐
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Pantau seluruh URL yang dipindai oleh semua pengguna sistem secara menyeluruh.
            </p>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-300">
                Total: <strong class="text-white">{{ number_format($totalScans) }}</strong>
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300">
                Phishing: <strong class="text-rose-400">{{ number_format($totalPhishing) }}</strong>
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300">
                Legit: <strong class="text-emerald-400">{{ number_format($totalLegitimate) }}</strong>
            </span>
        </div>
    </div>

    <!-- Filter & Pencarian Lengkap -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 shadow-lg">
        <form action="{{ route('admin.scans.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Pencarian URL atau Pengguna -->
            <div class="lg:col-span-2 relative">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari URL, nama pengguna, atau email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-100 placeholder-slate-500 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Filter Hasil Prediksi -->
            <div>
                <select name="prediction"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">Semua Prediksi</option>
                    <option value="Phishing" {{ request('prediction') === 'Phishing' ? 'selected' : '' }}>Phishing</option>
                    <option value="Legitimate" {{ request('prediction') === 'Legitimate' ? 'selected' : '' }}>Legitimate</option>
                </select>
            </div>

            <!-- Filter Tingkat Risiko -->
            <div>
                <select name="risk_level"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">Semua Risiko</option>
                    <option value="Low" {{ request('risk_level') === 'Low' ? 'selected' : '' }}>Low</option>
                    <option value="Medium" {{ request('risk_level') === 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="High" {{ request('risk_level') === 'High' ? 'selected' : '' }}>High</option>
                </select>
            </div>

            <!-- Filter Model & Submit Button -->
            <div class="flex items-center gap-2">
                <select name="model_version"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">Semua Versi Model</option>
                    @foreach($availableModelVersions as $ver)
                        <option value="{{ $ver }}" {{ request('model_version') === $ver ? 'selected' : '' }}>v{{ $ver }}</option>
                    @endforeach
                </select>

                <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-colors flex-shrink-0">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'prediction', 'risk_level', 'model_version']))
                    <a href="{{ route('admin.scans.index') }}"
                       class="px-3 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-white text-xs transition-colors"
                       title="Reset filter">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Scan Global -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        @if($scans->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4 font-medium">Waktu</th>
                            <th class="px-6 py-4 font-medium">Pengguna</th>
                            <th class="px-6 py-4 font-medium">Target URL</th>
                            <th class="px-6 py-4 font-medium">Prediksi</th>
                            <th class="px-6 py-4 font-medium">Skor Risiko</th>
                            <th class="px-6 py-4 font-medium">Model</th>
                            <th class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-normal">
                        @foreach($scans as $scan)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $scan->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs">
                                        <p class="font-medium text-slate-200">{{ $scan->user?->name ?? 'Guest/Unknown' }}</p>
                                        <p class="text-slate-500 text-[11px]">{{ $scan->user?->email ?? '-' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate text-slate-200 font-mono text-xs" title="{{ $scan->url }}">
                                    {{ $scan->url }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($scan->isPhishing())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Phishing
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Legitimate
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs {{ $scan->risk_score >= 70 ? 'text-rose-400' : ($scan->risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400') }}">
                                            {{ $scan->risk_score }}%
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            ({{ $scan->risk_level }})
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $scan->model_name }} <span class="text-purple-300 font-mono">v{{ $scan->model_version }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                    <a href="{{ route('admin.scans.show', $scan->id) }}"
                                       class="inline-block px-3 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-medium transition-colors">
                                        Detail
                                    </a>

                                    <form action="{{ route('admin.scans.destroy', $scan->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan scan ini secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 text-xs transition-colors"
                                                title="Hapus pemindaian">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginasi -->
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $scans->links() }}
            </div>
        @else
            <div class="text-center py-16 px-4">
                <p class="text-sm text-slate-400">Tidak ada riwayat pemindaian yang ditemukan.</p>
            </div>
        @endif
    </div>
</div>
@endsection
