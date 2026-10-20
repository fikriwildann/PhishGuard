@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'User Management')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Manajemen Pengguna 👥
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Kelola seluruh akun terdaftar, hak akses role, serta pantau aktivitas pemindaian setiap pengguna.
            </p>
        </div>

        <div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Pengguna Baru
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Pengguna -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">Total Terdaftar</p>
                <p class="text-2xl font-bold text-white mt-1">{{ number_format($totalUsers) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold">
                👥
            </div>
        </div>

        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-purple-400">Administrator</p>
                <p class="text-2xl font-bold text-purple-400 mt-1">{{ number_format($adminCount) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold">
                ⚡
            </div>
        </div>

        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">User Reguler</p>
                <p class="text-2xl font-bold text-slate-200 mt-1">{{ number_format($regularUserCount) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center font-bold">
                👤
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Pengguna -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 shadow-lg">
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari berdasarkan nama atau alamat email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-100 placeholder-slate-500 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div class="w-full sm:w-48">
                <select name="role"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User Reguler</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-colors">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'role']))
                    <a href="{{ route('admin.users.index') }}"
                       class="px-3.5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-white text-xs transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4 font-medium">Pengguna</th>
                            <th class="px-6 py-4 font-medium">Role</th>
                            <th class="px-6 py-4 font-medium">Total Scan</th>
                            <th class="px-6 py-4 font-medium">Terdaftar Sejak</th>
                            <th class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-normal">
                        @foreach($users as $user)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs {{ $user->isAdmin() ? 'bg-purple-600/20 text-purple-300 border border-purple-500/30' : 'bg-slate-800 text-slate-300' }}">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-100 flex items-center gap-1.5">
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <span class="text-[10px] text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20 font-normal">Anda</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->isAdmin())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/30">
                                            ⚡ Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400">
                                            User
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-300">
                                    <strong class="text-white font-mono">{{ $user->scans_count }}</strong> tautan
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $user->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="inline-block px-3 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-medium transition-colors">
                                        Kelola
                                    </a>

                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus akun pengguna {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 text-xs transition-colors"
                                                    title="Hapus akun pengguna">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-2.5 py-1 text-xs text-slate-600 cursor-not-allowed" title="Anda tidak dapat menghapus akun Anda sendiri">
                                            Terkunci
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $users->links() }}
            </div>
        @else
            <div class="text-center py-16 px-4">
                <p class="text-sm text-slate-400">Tidak ada data pengguna yang sesuai dengan kriteria.</p>
            </div>
        @endif
    </div>
</div>
@endsection
