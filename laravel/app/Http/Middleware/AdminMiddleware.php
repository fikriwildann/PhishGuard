<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk membatasi akses rute khusus Administrator (/admin/*).
 * Pengguna dengan role selain 'admin' akan ditolak dengan respons HTTP 403 Forbidden.
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan pengguna sudah login
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Pastikan pengguna memiliki role 'admin'
        if (! $request->user()->isAdmin()) {
            abort(403, 'Akses ditolak. Halaman ini hanya dapat diakses oleh Administrator PhishGuard.');
        }

        return $next($request);
    }
}
