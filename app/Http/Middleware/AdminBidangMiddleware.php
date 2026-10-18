<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBidangMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdminBidang()) {
            abort(403, 'Akses Ditolak: Halaman ini hanya dapat diakses oleh Admin Bidang DKIS.');
        }

        return $next($request);
    }
}
