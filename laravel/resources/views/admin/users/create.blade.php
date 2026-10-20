@extends('layouts.admin')

@section('title', 'Tambah Pengguna Baru')
@section('page-title', 'Create User')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.users.index') }}" class="hover:text-indigo-400 transition-colors">Manajemen Pengguna</a>
        <span>/</span>
        <span class="text-slate-200">Tambah Baru</span>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-xl font-bold text-white mb-1">Tambah Pengguna Baru</h2>
        <p class="text-xs text-slate-400 mb-6">Buat akun akses baru untuk pengguna sistem atau administrator tambahan.</p>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300">Nama Lengkap</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                       placeholder="Contoh: Budi Santoso"
                       class="mt-1.5 block w-full rounded-xl border {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-700' }} bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">Alamat Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                       placeholder="nama@email.com"
                       class="mt-1.5 block w-full rounded-xl border {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-700' }} bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('email')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-slate-300">Peran Akun (Role)</label>
                <select id="role" name="role" required
                        class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User (Pengguna Biasa)</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Administrator Penuh)</option>
                </select>
                @error('role')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300">Kata Sandi</label>
                <input id="password" name="password" type="password" required
                       placeholder="Minimal 8 karakter"
                       class="mt-1.5 block w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-700' }} bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Konfirmasi Kata Sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       placeholder="Ulangi kata sandi"
                       class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                <a href="{{ route('admin.users.index') }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
