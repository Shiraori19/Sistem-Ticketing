<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeknisiMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Route /teknisi/* HANYA untuk role Teknisi
        if ($user->isTeknisi()) {
            return $next($request);
        }

        abort(403, 'Akses Ditolak: Halaman portal ini khusus diperuntukkan bagi akun Teknisi.');
    }
}
