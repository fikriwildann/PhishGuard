<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ModelManagementController as AdminModelController;
use App\Http\Controllers\Admin\ScanController as AdminScanController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\HistoryController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ScannerController;
use App\Models\MlModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - PhishGuard
|--------------------------------------------------------------------------
*/

// 1. Landing Page
Route::get('/', function () {
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// 2. Rute Tamu (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 3. Rute Portal Pengguna (Wajib Login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner.index');
    Route::post('/scanner', [ScannerController::class, 'scan'])->name('scanner.scan');

    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{id}', [HistoryController::class, 'show'])->name('history.show');
    Route::delete('/history/{id}', [HistoryController::class, 'destroy'])->name('history.destroy');

    // Profil Pengguna (Ubah Nama & Kata Sandi Mandiri)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// 4. Panel Admin (/admin/*) dilindungi AdminMiddleware
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard Admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Pengguna (User Management)
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
    Route::put('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Manajemen Pemindaian Global (Scan Management)
    Route::get('/scans', [AdminScanController::class, 'index'])->name('scans.index');
    Route::get('/scans/{id}', [AdminScanController::class, 'show'])->name('scans.show');
    Route::delete('/scans/{id}', [AdminScanController::class, 'destroy'])->name('scans.destroy');

    // Manajemen Model Machine Learning (Phases 27-31)
    Route::get('/models', [AdminModelController::class, 'index'])->name('models.index');
    Route::get('/models/active', [AdminModelController::class, 'active'])->name('models.active');
    Route::get('/models/upload', [AdminModelController::class, 'create'])->name('models.upload');
    Route::post('/models/upload', [AdminModelController::class, 'store'])->name('models.store');
    Route::get('/models/history', [AdminModelController::class, 'history'])->name('models.history');
    Route::get('/models/{id}', [AdminModelController::class, 'show'])->name('models.show');
    Route::post('/models/{id}/validate', [AdminModelController::class, 'validateBundle'])->name('models.validate');
    Route::post('/models/{id}/activate', [AdminModelController::class, 'activate'])->name('models.activate');
    Route::post('/models/{id}/rollback', [AdminModelController::class, 'rollback'])->name('models.rollback');

    // Audit Trail Activity Logs (Phase 32)
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
});

// 5. Endpoint Internal untuk Sinkronisasi Active Model FastAPI (Token-Protected)
Route::get('/api/internal/active-model', function (Request $request) {
    $token = $request->header('X-Internal-Token');
    $validToken = config('phishguard.ml.internal_token');

    if (! $token || ! hash_equals($validToken, $token)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $active = MlModel::getActiveModel();

    return response()->json([
        'bundle_name' => $active?->bundle_name,
        'model_id' => $active?->id,
    ]);
});
