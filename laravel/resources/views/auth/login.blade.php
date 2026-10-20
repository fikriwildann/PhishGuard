@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')
@section('header-title', 'Selamat Datang Kembali')
@section('header-subtitle', 'Masuk untuk melakukan pemindaian URL phishing dan memantau status keamanan')

@section('content')
    <form class="space-y-6" action="{{ route('login') }}" method="POST">
        @csrf

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
                <p class="mt-2 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-300">
                Kata Sandi
            </label>
            <div class="mt-2">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    placeholder="••••••••"
                    class="block w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-500 bg-rose-500/5' : 'border-slate-700 bg-slate-950/60' }} px-4 py-3 text-slate-100 placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm transition-colors">
            </div>
            @error('password')
                <p class="mt-2 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ingat Saya -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox"
                    class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900">
                <label for="remember" class="ml-2 block text-xs text-slate-400">
                    Ingat sesi saya
                </label>
            </div>
        </div>

        <!-- Tombol Submit -->
        <div>
            <button type="submit"
                class="flex w-full justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all">
                Masuk ke PhishGuard
            </button>
        </div>
    </form>

    <!-- Tautan Pendaftaran -->
    <div class="mt-6 text-center border-t border-slate-800/80 pt-6">
        <p class="text-xs text-slate-400">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                Daftar sekarang
            </a>
        </p>
    </div>
@endsection
