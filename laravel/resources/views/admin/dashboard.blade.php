@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Overview & Analytic Metrics')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman Admin -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                Pusat Kendali Sistem PhishGuard ⚡
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Pantau seluruh metrik operasional, tren pemindaian tautan, dan aktivitas model Machine Learning secara terpusat.
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Sistem Operasional Normal
            </span>
        </div>
    </div>

    <!-- 4 Kartu Metrik Global Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Total Users -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Pengguna</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($totalUsers) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">
                Akun terdaftar dalam sistem
            </p>
        </div>

        <!-- 2. Total Scans -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Pemindaian</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($totalScans) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">
                Kumulatif seluruh URL yang dipindai
            </p>
        </div>

        <!-- 3. Phishing vs Legitimate -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-rose-400">Phishing Rate</p>
                    <p class="mt-2 text-3xl font-extrabold text-rose-400">{{ $phishingRate }}%</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-400 flex items-center justify-between">
                <span class="text-rose-400">Phish: {{ number_format($totalPhishing) }}</span>
                <span class="text-emerald-400">Legit: {{ number_format($totalLegitimate) }}</span>
            </div>
        </div>

        <!-- 4. Active Model -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Model AI Aktif</p>
                    <p class="mt-2 text-base font-bold text-white truncate" title="{{ $activeModel?->name ?? 'Belum ada model' }}">
                        {{ $activeModel?->name ?? 'Belum ada model' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 font-mono font-semibold">
                    v{{ $activeModel?->version ?? '-' }}
                </span>
                <span class="text-slate-400">
                    F1: <strong class="text-slate-200">{{ $activeModel ? number_format($activeModel->f1_score * 100, 1) : 0 }}%</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- 4 GRAFIK ANALITIK (Sesuai Poin 27 Spesifikasi) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Grafik 1: Phishing vs Legitimate (Donut Chart) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                </svg>
                Proporsi Hasil Prediksi (Phishing vs Legitimate)
            </h3>
            <div class="h-64 flex items-center justify-center">
                <canvas id="chartPhishingVsLegitimate"></canvas>
            </div>
        </div>

        <!-- Grafik 2: Tren Scan per Hari (Line Chart) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Tren Pemindaian URL per Hari (7 Hari Terakhir)
            </h3>
            <div class="h-64">
                <canvas id="chartDailyScans"></canvas>
            </div>
        </div>

        <!-- Grafik 3: Scan per Model Version (Bar Chart) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Distribusi Pemindaian per Model Version
            </h3>
            <div class="h-64">
                <canvas id="chartScansByModel"></canvas>
            </div>
        </div>

        <!-- Grafik 4: User Growth (Line Chart) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Pertumbuhan Pengguna Baru (7 Hari Terakhir)
            </h3>
            <div class="h-64">
                <canvas id="chartUserGrowth"></canvas>
            </div>
        </div>
    </div>

    <!-- Aktivitas Sistem Terbaru (Activity Logs Stream) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-white">Log Aktivitas Sistem Terkini</h3>
                <p class="text-xs text-slate-400 mt-0.5">Jejak audit peristiwa pengguna dan administrasi</p>
            </div>
            <a href="{{ url('/admin/activity-logs') }}" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                Lihat Seluruh Log &rarr;
            </a>
        </div>

        <div class="divide-y divide-slate-800/60">
            @forelse($recentActivities as $log)
                <div class="px-6 py-4 flex items-center justify-between text-xs hover:bg-slate-800/30 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 rounded-md font-mono text-[11px] font-semibold
                            {{ in_array($log->activity, ['LOGIN', 'LOGOUT']) ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/30' : '' }}
                            {{ $log->activity === 'SCAN_URL' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30' : '' }}
                            {{ in_array($log->activity, ['UPLOAD_MODEL', 'ACTIVATE_MODEL', 'ROLLBACK_MODEL']) ? 'bg-purple-500/10 text-purple-300 border border-purple-500/30' : '' }}
                            {{ $log->activity === 'DELETE_SCAN' ? 'bg-rose-500/10 text-rose-300 border border-rose-500/30' : '' }}">
                            {{ $log->activity }}
                        </span>
                        <div>
                            <p class="text-slate-200 font-medium">{{ $log->description }}</p>
                            <p class="text-slate-500 text-[11px]">
                                Oleh: <strong class="text-slate-400">{{ $log->user?->name ?? 'Sistem' }}</strong> (IP: {{ $log->ip_address ?? '-' }})
                            </p>
                        </div>
                    </div>
                    <span class="text-slate-500 whitespace-nowrap">
                        {{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
            @empty
                <div class="text-center py-8 text-slate-500 text-xs">
                    Belum ada log aktivitas yang tercatat.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Inisialisasi Script Chart.js -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Skema Warna Global Charts
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = '#1e293b';
        Chart.defaults.font.family = 'Figtree, sans-serif';

        // 1. Chart Phishing vs Legitimate (Donut)
        new Chart(document.getElementById('chartPhishingVsLegitimate'), {
            type: 'doughnut',
            data: {
                labels: ['Phishing', 'Legitimate'],
                datasets: [{
                    data: [{{ $totalPhishing }}, {{ $totalLegitimate }}],
                    backgroundColor: ['#f43f5e', '#10b981'],
                    borderColor: '#0f172a',
                    borderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                }
            }
        });

        // 2. Chart Tren Scan per Hari (Line)
        new Chart(document.getElementById('chartDailyScans'), {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyLabels) !!},
                datasets: [{
                    label: 'Jumlah Scan',
                    data: {!! json_encode($dailyScansData) !!},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 3. Chart Scan per Model Version (Bar)
        new Chart(document.getElementById('chartScansByModel'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($scansByModel) ?: ['v1.2']) !!},
                datasets: [{
                    label: 'Total Scan',
                    data: {!! json_encode(array_values($scansByModel) ?: [0]) !!},
                    backgroundColor: '#a855f7',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 4. Chart User Growth (Line)
        new Chart(document.getElementById('chartUserGrowth'), {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyLabels) !!},
                datasets: [{
                    label: 'Registrasi Baru',
                    data: {!! json_encode($userGrowthData) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    });
</script>
@endsection
