@extends('layouts.admin')

@section('title', 'Detail Pemindaian #' . $scan->id)
@section('page-title', 'Scan Record Detail')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.scans.index') }}" class="hover:text-indigo-400 transition-colors">Manajemen Pemindaian</a>
                <span>/</span>
                <span class="text-slate-200">Record #{{ $scan->id }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Audit Pemindaian URL #{{ $scan->id }} 🔍
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.scans.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                &larr; Kembali
            </a>

            <form action="{{ route('admin.scans.destroy', $scan->id) }}" method="POST" class="inline"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan scan ini secara permanen?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 rounded-xl border border-rose-500/30 hover:bg-rose-500/10 text-rose-400 text-xs font-medium transition-colors">
                    Hapus Data
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
    </div>

    <!-- Rincian Parameter Teknis & Info Pengguna -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Indikator Struktur URL -->
        <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-base font-semibold text-white flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Temuan Indikator Leksikal URL
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
                <p class="text-sm text-slate-400">Tidak ada anomali atau indikator heuristik yang terdeteksi.</p>
            @endif
        </div>

        <!-- Info Pemindai & Model -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-base font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Identitas Pemindai
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Nama Pengguna</span>
                    <span class="font-medium text-slate-200">
                        @if($scan->user)
                            <a href="{{ route('admin.users.show', $scan->user->id) }}" class="text-indigo-400 hover:underline">
                                {{ $scan->user->name }}
                            </a>
                        @else
                            Guest / Terhapus
                        @endif
                    </span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Email</span>
                    <span class="text-slate-200">{{ $scan->user?->email ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Waktu Pindai</span>
                    <span class="text-slate-200">{{ $scan->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Model AI</span>
                    <span class="text-slate-200">{{ $scan->model_name }} (v{{ $scan->model_version }})</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
