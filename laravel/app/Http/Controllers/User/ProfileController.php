<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Controller untuk mengelola profil dan keamanan kata sandi pengguna secara mandiri.
 */
class ProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        ActivityLog::record(
            ActivityLog::UPDATE_PROFILE,
            "Pengguna memperbarui informasi profil akun ({$user->email})"
        );

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
            'password.min' => 'Kata sandi baru minimal terdiri dari 8 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record(
            ActivityLog::CHANGE_PASSWORD,
            "Pengguna berhasil mengubah kata sandi akun ({$user->email})"
        );

        return back()->with('success', 'Kata sandi Anda berhasil diubah.');
    }
}
