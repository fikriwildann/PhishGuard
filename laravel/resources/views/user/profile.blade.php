@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            Pengaturan Profil Akun 👤
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Kelola data identitas akun Anda dan perbarui kata sandi untuk menjaga keamanan akun.
        </p>
    </div>

    <!-- Form Ubah Data Profil -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-semibold text-white mb-1">Informasi Akun</h2>
        <p class="text-xs text-slate-400 mb-6">Perbarui nama tampilan dan alamat email akun Anda.</p>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-300">Nama Lengkap</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                       class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">Alamat Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"
                       class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                @error('email')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition-all">
                    Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>

    <!-- Form Ubah Password -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-semibold text-white mb-1">Keamanan Kata Sandi</h2>
        <p class="text-xs text-slate-400 mb-6">Pastikan akun Anda menggunakan kata sandi yang kuat dan unik (minimal 8 karakter).</p>

        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-300">Kata Sandi Saat Ini</label>
                <input id="current_password" name="current_password" type="password" required
                       class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                @error('current_password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300">Kata Sandi Baru</label>
                    <input id="password" name="password" type="password" required placeholder="Min 8 karakter"
                           class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('password')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Konfirmasi Kata Sandi Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="Ulangi kata sandi"
                           class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-purple-600/30 transition-all">
                    Perbarui Kata Sandi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
