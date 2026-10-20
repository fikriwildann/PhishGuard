@extends('layouts.admin')

@section('title', 'Detail Pengguna: ' . $user->name)
@section('page-title', 'User Profile & Management')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumbs & Navigasi -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.users.index') }}" class="hover:text-indigo-400 transition-colors">Manajemen Pengguna</a>
        <span>/</span>
        <span class="text-slate-200">{{ $user->name }}</span>
    </div>

    <!-- Profil Pengguna & Kartu Kontrol Manajerial (Grid 2 Kolom) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Profil & Statistik Ringkas (1 Kolom) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-lg {{ $user->isAdmin() ? 'bg-purple-600/20 text-purple-300 border border-purple-500/40' : 'bg-slate-800 text-slate-300' }}">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase {{ $user->isAdmin() ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-slate-800 text-slate-400' }}">
                        {{ $user->role }}
                    </span>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-4 space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">ID Pengguna</span>
                    <span class="text-slate-300 font-mono">#{{ $user->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Terdaftar Pada</span>
                    <span class="text-slate-300">{{ $user->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Terakhir Diperbarui</span>
                    <span class="text-slate-300">{{ $user->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Ringkasan Scan Mini -->
            <div class="border-t border-slate-800 pt-4">
                <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3">Statistik Scan Pengguna</p>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-slate-950/70 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 block">Total</span>
                        <strong class="text-sm text-white font-mono">{{ $totalScans }}</strong>
                    </div>
                    <div class="bg-rose-500/10 p-2.5 rounded-xl border border-rose-500/20">
                        <span class="text-[10px] text-rose-400 block">Phishing</span>
                        <strong class="text-sm text-rose-400 font-mono">{{ $phishingCount }}</strong>
                    </div>
                    <div class="bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20">
                        <span class="text-[10px] text-emerald-400 block">Aman</span>
                        <strong class="text-sm text-emerald-400 font-mono">{{ $legitimateCount }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Aksi Manajerial (Ubah Role & Reset Password) (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Kartu 1: Ubah Role Pengguna -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-base font-semibold text-white flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Ubah Hak Akses (Role)
                </h3>
                <p class="text-xs text-slate-400 mb-4">
                    Ganti level otorisasi antara User biasa dan Administrator.
                </p>

                @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.update-role', $user->id) }}" method="POST" class="flex flex-col sm:flex-row items-center gap-3">
                        @csrf
                        @method('PUT')
                        <div class="w-full sm:w-60">
                            <select name="role"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User (Pengguna Biasa)</option>
                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin (Administrator Penuh)</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-purple-600/30 transition-colors">
                            Simpan Perubahan Role
                        </button>
                    </form>
                @else
                    <div class="rounded-xl bg-slate-950 p-3.5 text-xs text-slate-400 border border-slate-800">
                        🔒 Anda sedang mengelola akun Anda sendiri. Demi keamanan, Anda tidak dapat mengubah role akun sendiri.
                    </div>
                @endif
            </div>

            <!-- Kartu 2: Reset Kata Sandi Pengguna -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-base font-semibold text-white flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Reset Kata Sandi Pengguna
                </h3>
                <p class="text-xs text-slate-400 mb-4">
                    Tetapkan kata sandi baru apabila pengguna mengalami kendala lupa kata sandi.
                </p>

                <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="password" name="password" required placeholder="Kata sandi baru (min 8 char)"
                                   class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-700' }} bg-slate-950/80 text-slate-100 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                            @error('password')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <input type="password" name="password_confirmation" required placeholder="Konfirmasi kata sandi baru"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-950/80 text-slate-100 text-xs sm:text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-colors">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 10 Riwayat Scan Terakhir Milik Pengguna Ini -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800">
            <h3 class="text-sm font-semibold text-white">Riwayat Pemindaian Pengguna Ini (10 Terakhir)</h3>
        </div>

        @if($latestScans->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/60 uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3 font-medium">Waktu</th>
                            <th class="px-6 py-3 font-medium">URL</th>
                            <th class="px-6 py-3 font-medium">Prediksi</th>
                            <th class="px-6 py-3 font-medium">Skor Risiko</th>
                            <th class="px-6 py-3 font-medium">Model</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-normal">
                        @foreach($latestScans as $scan)
                            <tr class="hover:bg-slate-800/40">
                                <td class="px-6 py-3 whitespace-nowrap text-slate-400">{{ $scan->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 max-w-xs truncate font-mono" title="{{ $scan->url }}">{{ $scan->url }}</td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full font-semibold {{ $scan->isPhishing() ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' }}">
                                        {{ $scan->prediction }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap font-bold {{ $scan->risk_score >= 70 ? 'text-rose-400' : ($scan->risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400') }}">
                                    {{ $scan->risk_score }}% ({{ $scan->risk_level }})
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-slate-400">{{ $scan->model_name }} v{{ $scan->model_version }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-xs text-slate-500">
                Pengguna ini belum pernah melakukan pemindaian URL.
            </div>
        @endif
    </div>
</div>
@endsection
