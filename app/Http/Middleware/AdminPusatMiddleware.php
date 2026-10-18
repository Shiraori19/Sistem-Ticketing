<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPusatMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdminPusat()) {
            abort(403, 'Akses Ditolak: Halaman ini hanya dapat diakses oleh Admin Pusat DKIS.');
        }

        return $next($request);
    }
}
