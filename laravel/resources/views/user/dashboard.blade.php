@extends('layouts.app')

@section('title', 'Dashboard Pengguna')

@section('content')
<div class="space-y-8">
    <!-- Header Halaman & Tombol Aksi Cepat -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-2">
                Selamat Datang, {{ auth()->user()->name }}! 👋
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Pantau statistik keamanan tautan dan riwayat pemindaian URL phishing Anda.
            </p>
        </div>
        <div>
            <a href="{{ url('/scanner') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Pindai URL Baru
            </a>
        </div>
    </div>

    <!-- 4 Kartu Metrik Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Kartu 1: Total Scan -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Pemindaian</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($totalScans) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-400 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                Semua tautan yang pernah dianalisis
            </div>
        </div>

        <!-- Kartu 2: Phishing -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-rose-400">Terdeteksi Phishing</p>
                    <p class="mt-2 text-3xl font-extrabold text-rose-400">{{ number_format($phishingCount) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-rose-300 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                Rasio ancaman: {{ $phishingRate }}%
            </div>
        </div>

        <!-- Kartu 3: Legitimate -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-400">Terdeteksi Aman</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-400">{{ number_format($legitimateCount) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-emerald-300 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Tautan bersih tanpa indikasi phishing
            </div>
        </div>

        <!-- Kartu 4: Model Aktif -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Model AI Aktif</p>
                    <p class="mt-2 text-lg font-bold text-white truncate" title="{{ $activeModel?->name ?? 'PhishGuard XGBoost' }}">
                        {{ $activeModel?->name ?? 'PhishGuard XGBoost' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 font-mono font-semibold">
                    v{{ $activeModel?->version ?? '1.2' }}
                </span>
                <span class="text-slate-400">
                    F1: <strong class="text-slate-200">{{ $activeModel ? number_format($activeModel->f1_score * 100, 1) : '87.4' }}%</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Banner Pintasan Pemindai URL Cepat -->
    <div class="bg-gradient-to-r from-indigo-950/80 via-slate-900 to-slate-900 border border-indigo-500/30 rounded-2xl p-6 sm:p-8 shadow-xl">
        <div class="max-w-3xl">
            <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Pemindaian Cepat URL
            </h2>
            <p class="mt-1 text-sm text-slate-300">
                Masukkan tautan website mencurigakan untuk diekstraksi 49 fitur leksikalnya dan dianalisis langsung oleh model Machine Learning.
            </p>

            <form action="{{ url('/scanner') }}" method="GET" class="mt-5 flex flex-col sm:flex-row gap-3">
                <input type="url" name="url" required
                       placeholder="https://contoh-link-mencurigakan.com/login"
                       class="flex-1 rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-inner">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2">
                    <span>Analisis Tautan</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Pemindaian Terakhir (Latest Scans) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-white">Pemindaian Terakhir Anda</h3>
                <p class="text-xs text-slate-400 mt-0.5">5 aktivitas deteksi URL terbaru milik Anda</p>
            </div>
            <a href="{{ url('/history') }}" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                Lihat Semua Riwayat &rarr;
            </a>
        </div>

        @if($latestScans->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5 font-medium">Waktu</th>
                            <th class="px-6 py-3.5 font-medium">Target URL</th>
                            <th class="px-6 py-3.5 font-medium">Prediksi</th>
                            <th class="px-6 py-3.5 font-medium">Tingkat Risiko</th>
                            <th class="px-6 py-3.5 font-medium">Model</th>
                            <th class="px-6 py-3.5 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-normal">
                        @foreach($latestScans as $scan)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $scan->created_at->format('d M Y, H:i') }}
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
                                    {{ $scan->model_name }} <span class="text-slate-500">v{{ $scan->model_version }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ url('/history/' . $scan->id) }}"
                                       class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Tampilan Kosong (Empty State) -->
            <div class="text-center py-12 px-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h4 class="text-base font-semibold text-slate-200">Belum Ada Riwayat Pemindaian</h4>
                <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                    Anda belum pernah melakukan analisis URL. Coba masukkan tautan pertama Anda untuk menguji sistem deteksi kami.
                </p>
                <div class="mt-5">
                    <a href="{{ url('/scanner') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-colors">
                        Mulai Pindai Sekarang
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
