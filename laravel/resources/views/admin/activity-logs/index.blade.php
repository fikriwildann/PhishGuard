@extends('layouts.admin')

@section('title', 'Audit Log Aktivitas Sistem')
@section('page-title', 'System Activity Logs')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Audit Trail Aktivitas Sistem 🛡️
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Jejak audit seluruh peristiwa sistem: otentikasi, pemindaian URL, manajemen pengguna, dan manipulasi model AI.
            </p>
        </div>
    </div>

    <!-- Filter & Pencarian Log -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 shadow-lg">
        <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari deskripsi, nama user, email, atau alamat IP..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-100 placeholder-slate-500 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div class="w-full sm:w-60">
                <select name="activity"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">Semua Tipe Aktivitas</option>
                    @foreach($activityTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('activity') === $type ? 'selected' : '' }}>{{ $label }} ({{ $type }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-colors">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'activity']))
                    <a href="{{ route('admin.activity-logs.index') }}"
                       class="px-3.5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-white text-xs transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Log Aktivitas -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4 font-medium">Waktu</th>
                        <th class="px-6 py-4 font-medium">Tipe Aktivitas</th>
                        <th class="px-6 py-4 font-medium">Deskripsi Peristiwa</th>
                        <th class="px-6 py-4 font-medium">Pengguna</th>
                        <th class="px-6 py-4 font-medium">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-normal text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-400">
                                {{ $log->created_at->format('d M Y, H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md font-mono font-semibold uppercase text-[10px]
                                    {{ in_array($log->activity, ['LOGIN', 'LOGOUT']) ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/30' : '' }}
                                    {{ $log->activity === 'SCAN_URL' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30' : '' }}
                                    {{ in_array($log->activity, ['UPLOAD_MODEL', 'ACTIVATE_MODEL', 'ROLLBACK_MODEL']) ? 'bg-purple-500/10 text-purple-300 border border-purple-500/30' : '' }}
                                    {{ in_array($log->activity, ['DELETE_SCAN', 'CHANGE_PASSWORD']) ? 'bg-rose-500/10 text-rose-300 border border-rose-500/30' : '' }}
                                    {{ $log->activity === 'UPDATE_PROFILE' ? 'bg-amber-500/10 text-amber-300 border border-amber-500/30' : '' }}">
                                    {{ $log->activity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 max-w-md truncate text-slate-200" title="{{ $log->description }}">
                                {{ $log->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="text-xs">
                                        <p class="font-medium text-slate-200">{{ $log->user->name }}</p>
                                        <p class="text-slate-500 text-[11px]">{{ $log->user->email }}</p>
                                    </div>
                                @else
                                    <span class="text-slate-500 italic">Sistem / Tamu</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-400">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-slate-500 text-xs">
                                Tidak ada log aktivitas yang ditemukan.
                            </td>
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
