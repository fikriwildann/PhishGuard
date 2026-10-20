<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Controller untuk mengelola data seluruh pengguna sistem oleh Administrator.
 */
class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna dengan filter pencarian dan role.
     */
    public function index(Request $request): View
    {
        $query = User::withCount('scans')->latest();

        // 1. Pencarian nama atau email
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Filter Role
        if ($request->filled('role') && in_array($request->role, ['user', 'admin'], true)) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10)->withQueryString();

        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $regularUserCount = User::where('role', 'user')->count();

        return view('admin.users.index', compact(
            'users',
            'totalUsers',
            'adminCount',
            'regularUserCount'
        ));
    }

    /**
     * Tampilkan formulir pembuatan pengguna baru.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Simpan pengguna baru ke database.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(),
        ]);

        ActivityLog::record(
            ActivityLog::UPDATE_PROFILE,
            "Admin menambahkan pengguna baru: {$user->name} ({$user->email}) dengan role [{$user->role}]"
        );

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    /**
     * Tampilkan detail pengguna, statistik scan miliknya, dan jejak aktivitasnya.
     */
    public function show(int $id): View
    {
        $user = User::withCount('scans')->findOrFail($id);

        $totalScans = $user->scans_count;
        $phishingCount = $user->scans()->phishing()->count();
        $legitimateCount = $user->scans()->legitimate()->count();

        $latestScans = $user->scans()->latest()->take(10)->get();
        $userActivities = $user->activityLogs()->latest()->take(10)->get();

        return view('admin.users.show', compact(
            'user',
            'totalScans',
            'phishingCount',
            'legitimateCount',
            'latestScans',
            'userActivities'
        ));
    }

    /**
     * Ubah role pengguna (user <-> admin).
     */
    public function updateRole(UpdateUserRoleRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proteksi: Admin tidak boleh mengubah role dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak diizinkan mengubah role akun Anda sendiri.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        ActivityLog::record(
            ActivityLog::UPDATE_PROFILE,
            "Admin mengubah role pengguna {$user->email} dari [{$oldRole}] menjadi [{$request->role}]"
        );

        return back()->with('success', "Role pengguna {$user->name} berhasil diubah menjadi {$request->role}.");
    }

    /**
     * Reset kata sandi pengguna oleh Administrator.
     */
    public function resetPassword(ResetUserPasswordRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record(
            ActivityLog::CHANGE_PASSWORD,
            "Admin mereset kata sandi pengguna {$user->email}"
        );

        return back()->with('success', "Kata sandi untuk {$user->name} berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proteksi Kritis: Admin tidak boleh menghapus akun dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tindakan ditolak! Anda dilarang menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        ActivityLog::record(
            ActivityLog::UPDATE_PROFILE,
            "Admin menghapus pengguna: {$userName} ({$userEmail})"
        );

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$userName} ({$userEmail}) berhasil dihapus.");
    }
}
