<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Akses Ditolak — PhishGuard</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950">
    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-2xl p-8 text-center shadow-2xl backdrop-blur-md">
        <div class="flex justify-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="PhishGuard Logo" class="w-14 h-14 object-contain drop-shadow-[0_8px_16px_rgba(99,102,241,0.35)]">
        </div>

        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 mb-3">
            HTTP 403 FORBIDDEN
        </span>

        <h1 class="text-2xl font-bold text-white mb-2">Akses Ditolak</h1>
        <p class="text-sm text-slate-400 mb-6">
            {{ $exception->getMessage() ?: 'Halaman ini khusus untuk Administrator. Akun Anda tidak memiliki hak akses ke panel administrasi PhishGuard.' }}
        </p>

        <div class="space-y-3">
            <a href="{{ route('dashboard') }}" class="block w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition-colors shadow-lg shadow-indigo-600/30">
                Kembali ke Dashboard Pengguna
            </a>
            <a href="{{ url('/') }}" class="block w-full py-2.5 px-4 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 font-medium text-sm transition-colors">
                Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
