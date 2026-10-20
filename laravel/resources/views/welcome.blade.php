<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PhishGuard — AI-Powered Phishing URL Detection System</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex flex-col font-sans antialiased bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white">
    <!-- Navbar Landing Page -->
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="PhishGuard Logo"
                     class="w-10 h-10 object-contain drop-shadow-[0_4px_10px_rgba(99,102,241,0.35)]">
                <span class="text-xl font-bold tracking-tight text-white">Phish<span class="text-indigo-400">Guard</span></span>
            </div>

            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                           class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all">
                            Buka Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-slate-300 hover:text-white text-xs font-semibold hover:bg-slate-900 transition-colors">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all">
                            Daftar Sekarang
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-20 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-900/20 via-slate-950 to-slate-950 -z-10"></div>

        <div class="max-w-4xl space-y-6">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shadow-inner">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                Sistem Deteksi Phishing Berbasis Machine Learning & Analisis URL
            </span>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-tight">
                Lindungi Pengguna dari <br>
                <span class="bg-gradient-to-r from-indigo-400 via-purple-300 to-rose-400 bg-clip-text text-transparent">Ancaman Tautan Palsu</span>
            </h1>

            <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto">
                Deteksi tautan phishing dengan cerdas dan cepat. PhishGuard menganalisis 49 fitur URL menggunakan Machine Learning untuk mengidentifikasi potensi ancaman dan membantu Anda menjelajahi internet dengan lebih aman.
            </p>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('login') }}"
                   class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 hover:scale-[1.02] transition-all">
                    Mulai Pindai Tautan &rarr;
                </a>
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto px-8 py-3.5 rounded-xl border border-slate-700 hover:bg-slate-900 text-slate-300 font-semibold text-sm transition-colors">
                    Buat Akun Gratis
                </a>
            </div>
        </div>

        <!-- 3 Pilar Keunggulan -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mt-24 text-left">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#222cb9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap preview-icon"><path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z"/></svg>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Inferensi Cepat & Aman</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Menganalisis struktur dan karakteristik URL untuk mendeteksi potensi phishing tanpa perlu membuka website tujuan.
                </p>
            </div>

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5c1881" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rotate-ccw-clock preview-icon"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Skor Risiko & Hasil Analisis</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Menampilkan hasil klasifikasi dan tingkat risiko URL agar pengguna lebih mudah memahami potensi ancaman yang terdeteksi.
                </p>
            </div>

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#298118" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined preview-icon"><path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/></svg>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Riwayat Pemeriksaan URL</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Memudahkan pengguna meninjau kembali hasil pemeriksaan URL yang telah dilakukan sebelumnya melalui fitur riwayat analisis.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer Landing -->
    <footer class="border-t border-slate-800/80 py-8 text-center text-xs text-slate-500 bg-slate-950">
        <p>&copy; {{ date('Y') }} PhishGuard — AI-Powered Phishing URL Detection System.</p>
    </footer>
</body>
</html>
