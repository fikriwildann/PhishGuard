<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') — {{ config('app.name', 'PhishGuard') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN untuk Visualisasi Grafik Admin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex font-sans antialiased bg-slate-950 text-slate-100">
    <!-- SIDEBAR ADMIN (Sesuai Poin 37 Spesifikasi) -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col flex-shrink-0 min-h-screen">
        <!-- Brand Header Sidebar -->
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800 bg-slate-950/40">
            <img src="{{ asset('images/logo.png') }}" alt="PhishGuard Logo"
                 class="w-9 h-9 object-contain drop-shadow-[0_4px_10px_rgba(168,85,247,0.35)]">
            <div>
                <span class="text-base font-bold text-white tracking-tight">PHISH<span class="text-indigo-400">GUARD</span></span>
                <span class="block text-[10px] font-semibold text-purple-400 uppercase tracking-widest -mt-0.5">ADMIN PANEL</span>
            </div>
        </div>

        <!-- Menu Navigasi Sidebar -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto text-sm">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Users Management -->
            <a href="{{ url('/admin/users') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition-colors {{ request()->is('admin/users*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>Users</span>
            </a>

            <!-- Scans Management -->
            <a href="{{ url('/admin/scans') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition-colors {{ request()->is('admin/scans*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Scans</span>
            </a>

            <!-- Menu Models (Dropdown / Group Sesuai Poin 37) -->
            <div class="pt-2">
                <div class="px-3.5 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    Model Management
                </div>
                <div class="space-y-1 pl-2">
                    <a href="{{ url('/admin/models') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->is('admin/models') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ request()->is('admin/models') ? 'bg-indigo-400' : 'bg-slate-600' }}"></span>
                        <span>All Models</span>
                    </a>
                    <a href="{{ url('/admin/models/active') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->is('admin/models/active*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ request()->is('admin/models/active*') ? 'bg-purple-400' : 'bg-slate-600' }}"></span>
                        <span>Active Model</span>
                    </a>
                    <a href="{{ url('/admin/models/upload') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->is('admin/models/upload*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ request()->is('admin/models/upload*') ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                        <span>Upload Model</span>
                    </a>
                    <a href="{{ url('/admin/models/history') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->is('admin/models/history*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ request()->is('admin/models/history*') ? 'bg-amber-400' : 'bg-slate-600' }}"></span>
                        <span>Model History</span>
                    </a>
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="pt-2">
                <a href="{{ url('/admin/activity-logs') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition-colors {{ request()->is('admin/activity-logs*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Activity Logs</span>
                </a>
            </div>
        </nav>

        <!-- Sisi Bawah Sidebar: Pintasan Portal User -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <a href="{{ route('dashboard') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800 text-slate-300 text-xs font-medium transition-colors">
                <span>&larr; Portal Pengguna</span>
                <span class="text-indigo-400 font-bold">&rarr;</span>
            </a>
        </div>
    </aside>

    <!-- KONTEN UTAMA ADMIN -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        <!-- Top Bar Admin -->
        <header class="h-16 bg-slate-900/90 border-b border-slate-800 px-6 flex items-center justify-between backdrop-blur-md sticky top-0 z-40">
            <div>
                <span class="text-xs text-slate-400">Admin Workspace</span>
                <h2 class="text-sm font-semibold text-white">@yield('page-title', 'Overview')</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                    <div class="w-6 h-6 rounded-full bg-purple-600/30 text-purple-300 flex items-center justify-center font-bold text-[10px]">
                        A
                    </div>
                    <span class="font-medium text-slate-200">{{ auth()->user()->name }}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-500/20 text-purple-300">
                        ADMIN
                    </span>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            title="Keluar"
                            class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
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

        <!-- Konten Halaman -->
        <main class="flex-1 p-6 space-y-6">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="p-6 border-t border-slate-800 text-xs text-slate-500 text-center bg-slate-950">
            &copy; {{ date('Y') }} PhishGuard Admin Control System — Machine Learning Lifecycle Platform.
        </footer>
    </div>
</body>
</html>
