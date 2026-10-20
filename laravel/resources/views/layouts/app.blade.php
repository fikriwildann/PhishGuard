<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'PhishGuard') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
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
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans antialiased bg-slate-950 text-slate-100">
    <!-- Navbar Pengguna Utama -->
    <nav class="sticky top-0 z-50 bg-slate-900/90 border-b border-slate-800 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Sisi Kiri: Brand Logo & Menu Navigasi Desktop -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo.png') }}" alt="PhishGuard Logo"
                             class="w-10 h-10 object-contain drop-shadow-[0_4px_10px_rgba(99,102,241,0.35)] group-hover:scale-105 transition-transform duration-200">
                        <span class="text-xl font-bold tracking-tight text-white">Phish<span class="text-indigo-400">Guard</span></span>
                    </a>

                    <!-- Nav Items Desktop -->
                    <div class="hidden md:flex items-center gap-1">
                        <a href="{{ route('dashboard') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            Dashboard
                        </a>
                        <a href="{{ url('/scanner') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('scanner*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            Scanner
                        </a>
                        <a href="{{ url('/history') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('history*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            History
                        </a>
                        <a href="{{ url('/profile') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('profile*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            Profile
                        </a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                               class="ml-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/30 hover:bg-purple-500/20 transition-colors">
                                Panel Admin ⚡
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Sisi Kanan: Profil Pengguna & Tombol Logout -->
                <div class="hidden md:flex items-center gap-4">
                    <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                        <div class="w-6 h-6 rounded-full bg-indigo-600/30 text-indigo-300 flex items-center justify-center font-bold text-[10px]">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="font-medium text-slate-200">{{ auth()->user()->name }}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase {{ auth()->user()->isAdmin() ? 'bg-purple-500/20 text-purple-300' : 'bg-slate-800 text-slate-400' }}">
                            {{ auth()->user()->role }}
                        </span>
                    </div>

                    <!-- Tombol Form Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                title="Keluar dari sistem"
                                class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Tombol Menu Mobile -->
                <div class="flex md:hidden">
                    <button type="button"
                            onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                            class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-800 px-4 pt-3 pb-4 space-y-1 bg-slate-900">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800">Dashboard</a>
            <a href="{{ url('/scanner') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800">Scanner</a>
            <a href="{{ url('/history') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800">History</a>
            <a href="{{ url('/profile') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800">Profile</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold text-purple-400 hover:bg-purple-500/10">Panel Admin</a>
            @endif
            <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-400">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-rose-400 hover:underline">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Notifikasi Flash Global -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('success'))
            <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-sm text-emerald-400 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-sm text-rose-400 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <!-- Konten Utama Halaman -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-slate-800 bg-slate-950 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            &copy; {{ date('Y') }} PhishGuard — AI-Powered Phishing URL Detection System.
        </div>
    </footer>
</body>
</html>
