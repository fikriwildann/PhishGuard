<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Autentikasi') — {{ config('app.name', 'PhishGuard') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN untuk rendering instan yang andal -->
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="min-h-screen font-sans antialiased bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 flex flex-col py-8 sm:py-12 px-4 sm:px-6 lg:px-8 overflow-y-auto">
    <div class="my-auto sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Brand PhishGuard & Header -->
        <div class="text-center">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3.5 group">
                <img src="{{ asset('images/logo.png') }}" alt="PhishGuard Logo"
                     class="w-14 h-14 object-contain drop-shadow-[0_8px_16px_rgba(99,102,241,0.35)] group-hover:scale-105 transition-transform duration-200">
                <span class="text-2xl font-bold tracking-tight text-white">Phish<span class="text-indigo-400">Guard</span></span>
            </a>

            <h2 class="mt-4 text-xl font-semibold tracking-tight text-slate-200">
                @yield('header-title')
            </h2>
            <p class="mt-1 text-sm text-slate-400">
                @yield('header-subtitle')
            </p>
        </div>

        <!-- Flash Message & Form Card -->
        <div class="mt-6 sm:mt-8">
            <!-- Notifikasi Status / Flash Message -->
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 p-4 text-sm text-emerald-400 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any() && !old('email'))
                <div class="mb-4 rounded-lg bg-rose-500/10 border border-rose-500/30 p-4 text-sm text-rose-400 flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Card Form Auth -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl shadow-black/40 backdrop-blur-md px-6 py-8 sm:px-10">
                @yield('content')
            </div>

            <div class="mt-6 sm:mt-8 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} PhishGuard — AI-Powered Phishing URL Detection System.
            </div>
        </div>
    </div>
</body>
</html>
