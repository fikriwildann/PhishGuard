@extends('layouts.admin')

@section('title', 'Riwayat Aktivasi & Rollback Model')
@section('page-title', 'Model Activation History')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Riwayat Aktivasi & Rollback Model 📜
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Jejak audit seluruh perubahan model aktif produksi, alasan perubahan, dan administrator yang mengeksekusi.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.models.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                &larr; Kembali
            </a>
        </div>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4 font-medium">Waktu</th>
                        <th class="px-6 py-4 font-medium">Tindakan</th>
                        <th class="px-6 py-4 font-medium">Model Baru Diaktifkan</th>
                        <th class="px-6 py-4 font-medium">Model Sebelumnya</th>
                        <th class="px-6 py-4 font-medium">Administrator</th>
                        <th class="px-6 py-4 font-medium">Alasan / Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-normal text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-400">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full font-mono font-semibold uppercase text-[10px] {{ $log->action === 'activate' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-bold text-white">{{ $log->newModel?->name ?? 'Model Terhapus' }}</p>
                                <p class="font-mono text-purple-300 text-[11px]">v{{ $log->newModel?->version ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->previousModel)
                                    <p class="text-slate-300">{{ $log->previousModel->name }}</p>
                                    <p class="font-mono text-slate-500 text-[11px]">v{{ $log->previousModel->version }}</p>
                                @else
                                    <span class="text-slate-600">- Inisialisasi -</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $log->admin?->name ?? 'Sistem' }}
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate text-slate-400" title="{{ $log->reason }}">
                                {{ $log->reason ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-500 text-xs">Belum ada catatan log aktivasi model.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
