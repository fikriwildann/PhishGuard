<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Controller untuk menangani alur autentikasi pengguna: Login, Registrasi, dan Logout.
 */
class AuthController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses permohonan autentikasi login pengguna.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ActivityLog::record(
            ActivityLog::LOGIN,
            'Pengguna berhasil masuk ke sistem.'
        );

        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Tampilkan formulir registrasi akun baru.
     */
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    /**
     * Proses pendaftaran akun pengguna baru.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Akun baru otomatis memiliki role reguler 'user'
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        ActivityLog::record(
            ActivityLog::LOGIN,
            'Registrasi akun baru berhasil dan pengguna langsung login.'
        );

        return redirect()->route('dashboard');
    }

    /**
     * Proses logout pengguna dan pembersihan sesi.
     */
    public function logout(Request $request): RedirectResponse
    {
        ActivityLog::record(
            ActivityLog::LOGOUT,
            'Pengguna keluar dari sesi sistem.'
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
