@extends('layouts.admin')

@section('title', 'Unggah Model Baru')
@section('page-title', 'Upload Machine Learning Model')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.models.index') }}" class="hover:text-indigo-400 transition-colors">Model Management</a>
        <span>/</span>
        <span class="text-slate-200">Upload New Model</span>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-xl font-bold text-white mb-1">Unggah Model Machine Learning Baru 📦</h2>
        <p class="text-xs text-slate-400 mb-6">
            Unggah bundle ZIP yang memuat <code>model.pkl</code>, <code>metadata.json</code>, dan <code>feature_names.json</code> hasil pelatihan.
        </p>

        <form action="{{ route('admin.models.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- Arsip ZIP Bundle -->
            <div>
                <label for="model_archive" class="block text-sm font-medium text-slate-300">
                    Berkas ZIP Bundle Model (.zip) <span class="text-rose-400">*</span>
                </label>
                <input id="model_archive" name="model_archive" type="file" required accept=".zip"
                       class="mt-1.5 block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer rounded-xl border border-slate-700 bg-slate-950/80 p-2">
                <p class="mt-1 text-[11px] text-slate-500">Maksimum ukuran: 50MB. Berkas zip harus memuat 3 file utama.</p>
                @error('model_archive')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nama Model -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-300">Nama Model <span class="text-rose-400">*</span></label>
                    <input id="name" name="name" type="text" required value="{{ old('name', 'PhishGuard Random Forest') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Versi Model -->
                <div>
                    <label for="version" class="block text-sm font-medium text-slate-300">Versi Model <span class="text-rose-400">*</span></label>
                    <input id="version" name="version" type="text" required value="{{ old('version', '1.0') }}"
                           placeholder="Contoh: 1.0 atau 2.0"
                           class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('version')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Algoritma -->
            <div>
                <label for="algorithm" class="block text-sm font-medium text-slate-300">Algoritma <span class="text-rose-400">*</span></label>
                <select id="algorithm" name="algorithm" required
                        class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="XGBoost">XGBoost Classifier</option>
                    <option value="Random Forest">Random Forest Classifier</option>
                    <option value="Logistic Regression">Logistic Regression</option>
                </select>
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-300">Deskripsi Model</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Catatan mengenai konfigurasi model, hyperparameter, atau dataset training..."
                          class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">{{ old('description') }}</textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                <a href="{{ route('admin.models.index') }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all">
                    Unggah & Simpan Bundle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
