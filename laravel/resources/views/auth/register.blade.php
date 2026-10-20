@extends('layouts.auth')

@section('title', 'Daftar Akun Baru')
@section('header-title', 'Buat Akun PhishGuard')
@section('header-subtitle', 'Daftar untuk mulai menganalisis tautan mencurigakan secara mandiri')

@section('content')
    <form class="space-y-5" action="{{ route('register') }}" method="POST">
        @csrf

        <!-- Input Nama Lengkap -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-300">
                Nama Lengkap
            </label>
            <div class="mt-2">
                <input id="name" name="name" type="text" autocomplete="name" required
                    value="{{ old('name') }}"
                    placeholder="Nama Anda"
                    class="block w-full rounded-xl border {{ $errors->has('name') ? 'border-rose-500 bg-rose-500/5' : 'border-slate-700 bg-slate-950/60' }} px-4 py-3 text-slate-100 placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition-colors">
            </div>
            @error('name')
                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-300">
                Alamat Email
            </label>
            <div class="mt-2">
                <input id="email" name="email" type="email" autocomplete="email" required
                    value="{{ old('email') }}"
                    placeholder="nama@email.com"
                    class="block w-full rounded-xl border {{ $errors->has('email') ? 'border-rose-500 bg-rose-500/5' : 'border-slate-700 bg-slate-950/60' }} px-4 py-3 text-slate-100 placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition-colors">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-300">
                Kata Sandi
            </label>
            <div class="mt-2">
                <input id="password" name="password" type="password" autocomplete="new-password" required
                    placeholder="Minimal 8 karakter"
                    class="block w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-500 bg-rose-500/5' : 'border-slate-700 bg-slate-950/60' }} px-4 py-3 text-slate-100 placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition-colors">
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-300">
                Konfirmasi Kata Sandi
            </label>
            <div class="mt-2">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                    placeholder="Ulangi kata sandi"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-slate-100 placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition-colors">
            </div>
        </div>

        <!-- Tombol Submit -->
        <div class="pt-2">
            <button type="submit"
                class="flex w-full justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all">
                Daftar Akun Baru
            </button>
        </div>
    </form>

    <!-- Tautan Kembali ke Login -->
    <div class="mt-6 text-center border-t border-slate-800/80 pt-6">
        <p class="text-xs text-slate-400">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                Masuk di sini
            </a>
        </p>
    </div>
@endsection
