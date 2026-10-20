@extends('layouts.app')

@section('title', 'Detail Pemindaian #' . $scan->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header & Tombol Navigasi Kembali -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('history.index') }}" class="hover:text-indigo-400 transition-colors">Riwayat</a>
                <span>/</span>
                <span class="text-slate-200">Detail Pemindaian #{{ $scan->id }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Rincian Analisis URL 🔍
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('scanner.index', ['url' => $scan->url]) }}"
               class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all">
                Pindai Ulang Tautan
            </a>

            <form action="{{ route('history.destroy', $scan->id) }}" method="POST" class="inline"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan riwayat ini?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 rounded-xl border border-rose-500/30 hover:bg-rose-500/10 text-rose-400 text-xs font-medium transition-colors">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- Kartu Hasil Utama -->
    <div class="rounded-2xl border p-6 sm:p-8 shadow-2xl relative overflow-hidden {{ $scan->isPhishing() ? 'bg-rose-950/20 border-rose-500/40' : 'bg-emerald-950/20 border-emerald-500/40' }}">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $scan->isPhishing() ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' }}">
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

    <!-- Rincian Temuan & Metadata -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Temuan Indikator Leksikal -->
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

        <!-- Metadata Teknis Model & Waktu -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-base font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Informasi Snapshot Model
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Model AI</span>
                    <span class="font-medium text-slate-200">{{ $scan->model_name }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Versi Model</span>
                    <span class="font-mono text-purple-300 font-semibold">v{{ $scan->model_version }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">ID Model DB</span>
                    <span class="font-mono text-slate-300">#{{ $scan->model_id ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Waktu Pindai</span>
                    <span class="text-slate-200">{{ $scan->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800">
                <a href="{{ route('history.index') }}"
                   class="block text-center w-full py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 font-medium text-xs transition-colors">
                    &larr; Kembali ke Daftar Riwayat
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
