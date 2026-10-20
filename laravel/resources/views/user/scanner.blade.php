@extends('layouts.app')

@section('title', 'Pemindai URL Phishing')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- Header Halaman & Status Engine AI -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-2">
                Pemindai URL Phishing 🛡️
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Analisis tautan mencurigakan secara instan menggunakan ekstraksi 49 fitur leksikal dan model Machine Learning.
            </p>
        </div>

        <!-- Status Koneksi FastAPI -->
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium border {{ $health['reachable'] && $health['model_loaded'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30' }}">
            <span class="w-2 h-2 rounded-full {{ $health['reachable'] && $health['model_loaded'] ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
            @if($health['reachable'] && $health['model_loaded'])
                <span>AI Engine Siap ({{ $health['model_name'] }} v{{ $health['model_version'] }})</span>
            @elseif($health['reachable'])
                <span>FastAPI Terhubung (Model Belum Aktif)</span>
            @else
                <span>Layanan AI Belum Aktif (Port 8000)</span>
            @endif
        </div>
    </div>

    <!-- Kotak Form Input Pemindaian -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="{{ route('scanner.scan') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="url" class="block text-sm font-medium text-slate-200 mb-2">
                    Masukkan Alamat URL / Tautan Website
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <input id="url" name="url" type="text" required
                           value="{{ old('url', $initialUrl) }}"
                           placeholder="https://contoh-website-mencurigakan.com/verify-account"
                           class="block w-full pl-11 pr-32 py-3.5 rounded-xl border {{ $errors->has('url') ? 'border-rose-500 bg-rose-500/5' : 'border-slate-700 bg-slate-950/80' }} text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono transition-colors shadow-inner">

                    <button type="submit"
                            class="absolute right-2 top-2 bottom-2 px-5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Analisis</span>
                    </button>
                </div>
                @error('url')
                    <p class="mt-2 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <p class="text-xs text-slate-500">
                💡 Tip: Anda dapat memasukkan URL dengan atau tanpa <code>https://</code>. Sistem akan otomatis menormalkan format tautan sebelum diekstraksi.
            </p>
        </form>
    </div>

    <!-- HASIL PEMINDAIAN (Tampil jika ada $scan) -->
    @if($scan)
        <div class="space-y-6">
            <!-- Kartu Utama Hasil Prediksi -->
            <div class="rounded-2xl border p-6 sm:p-8 shadow-2xl relative overflow-hidden {{ $scan->isPhishing() ? 'bg-rose-950/20 border-rose-500/40' : 'bg-emerald-950/20 border-emerald-500/40' }}">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <!-- Status & Badge Prediksi -->
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $scan->isPhishing() ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40 shadow-lg shadow-rose-500/20' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-lg shadow-emerald-500/20' }}">
                            @if($scan->isPhishing())
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @else
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-3">
                                <span class="text-2xl sm:text-3xl font-extrabold tracking-tight {{ $scan->isPhishing() ? 'text-rose-400' : 'text-emerald-400' }}">
                                    {{ $scan->prediction }}
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $scan->isPhishing() ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                                    Risiko: {{ $scan->risk_level }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-300">
                                {{ $scan->message }}
                            </p>
                            <p class="mt-2 text-xs font-mono text-slate-400 break-all bg-slate-950/60 px-3 py-1.5 rounded-lg border border-slate-800">
                                {{ $scan->url }}
                            </p>
                        </div>
                    </div>

                    <!-- Skor Metrik Risiko & Keyakinan Model -->
                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 border-slate-800 pt-4 sm:pt-0 gap-3">
                        <div class="text-right">
                            <span class="text-xs uppercase tracking-wider text-slate-400 block">Skor Risiko</span>
                            <span class="text-3xl font-extrabold {{ $scan->risk_score >= 70 ? 'text-rose-400' : ($scan->risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400') }}">
                                {{ $scan->risk_score }}<span class="text-base font-normal text-slate-500">/100</span>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs uppercase tracking-wider text-slate-400 block">Probabilitas Model</span>
                            <span class="text-sm font-semibold text-slate-200">
                                {{ number_format($scan->probability * 100, 2) }}% Keyakinan
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bar Meter Risiko Visual -->
                <div class="mt-6 pt-6 border-t border-slate-800/80">
                    <div class="flex justify-between text-xs text-slate-400 mb-2">
                        <span>Tingkat Bahaya Indikator</span>
                        <span>{{ $scan->risk_score }}% ({{ $scan->risk_level }})</span>
                    </div>
                    <div class="h-2.5 w-full bg-slate-950 rounded-full overflow-hidden p-0.5 border border-slate-800">
                        <div class="h-full rounded-full transition-all duration-500 {{ $scan->risk_score >= 70 ? 'bg-rose-500 shadow-md shadow-rose-500/50' : ($scan->risk_score >= 30 ? 'bg-amber-500 shadow-md shadow-amber-500/50' : 'bg-emerald-500 shadow-md shadow-emerald-500/50') }}"
                             style="width: {{ max(5, $scan->risk_score) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Kartu Rincian Indikator Heuristik & Metadata Model -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Rincian Indikator Heuristik (2 Kolom) -->
                <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-base font-semibold text-white flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Temuan Indikator Struktur URL
                    </h3>

                    @if(!empty($scan->indicators))
                        <ul class="space-y-2.5 text-xs sm:text-sm">
                            @foreach($scan->indicators as $indicator)
                                <li class="flex items-start gap-2.5 text-slate-300 bg-slate-950/50 px-3.5 py-2.5 rounded-xl border border-slate-800/80">
                                    <span class="mt-0.5 w-4 h-4 rounded-full flex-shrink-0 flex items-center justify-center {{ $scan->isPhishing() ? 'bg-rose-500/20 text-rose-400' : 'bg-emerald-500/20 text-emerald-400' }}">
                                        •
                                    </span>
                                    <span>{{ $indicator }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-400">Tidak ada indikator heuristik khusus yang ditemukan.</p>
                    @endif
                </div>

                <!-- Metadata Model AI yang Digunakan (1 Kolom) -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h3 class="text-base font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Metadata Model
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Nama Model</span>
                            <span class="font-medium text-slate-200">{{ $scan->model_name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Versi Model</span>
                            <span class="font-mono text-purple-300 font-semibold">v{{ $scan->model_version }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Waktu Pindai</span>
                            <span class="text-slate-200">{{ $scan->created_at->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">ID Riwayat</span>
                            <span class="font-mono text-slate-400">#{{ $scan->id }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ url('/history') }}" class="block text-center w-full py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 font-medium text-xs transition-colors">
                            Buka Riwayat Pemindaian
                        </a>
                    </div>
                </div>
            </div>

            <!-- Disclaimer Keamanan (Sesuai Poin 38 Spesifikasi) -->
            <div class="rounded-xl bg-slate-900/50 border border-slate-800/80 p-4 text-xs text-slate-400 flex items-start gap-3">
                <svg class="w-5 h-5 text-slate-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>
                    <strong>Disclaimer:</strong> PhishGuard merupakan sistem klasifikasi berbasis Machine Learning. Hasil deteksi bersifat prediktif berdasarkan analisis leksikal struktur URL dan tidak menjamin keamanan mutlak suatu website. Tetap berhati-hati sebelum memasukkan data pribadi atau kredensial rahasia.
                </p>
            </div>
        </div>
    @else
        <!-- Panduan Informasi Saat Belum Ada URL yang Dipindai -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-200">1. Ekstraksi Fitur URL</h4>
                <p class="mt-1 text-xs text-slate-400">
                    Sistem mengekstrak 49 parameter struktural: panjang hostname, keberadaan IP, TLD di subdomain, rasio digit, dan kata kunci penipuan.
                </p>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-200">2. Klasifikasi Machine Learning</h4>
                <p class="mt-1 text-xs text-slate-400">
                    Vektor fitur diklasifikasikan oleh model aktif produksi (XGBoost) untuk memprediksi apakah website tersebut berbahaya atau sah.
                </p>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-200">3. Skor Risiko & Indikator</h4>
                <p class="mt-1 text-xs text-slate-400">
                    Dapatkan probabilitas keyakinan model, tingkat risiko (Low, Medium, High), dan rincian anomali leksikal yang ditemukan pada tautan.
                </p>
            </div>
        </div>
    @endif
</div>
@endsection
