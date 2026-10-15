<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Izin keluar, laundry, dan rating catering hanya untuk penghuni yang sedang menginap
 * (punya kamar dan sudah check-in). Keputusan pengelola no. 10 dan 17.
 */
class RequireStaying
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaying()) {
            throw ValidationException::withMessages([
                'room' => 'Fitur ini hanya untuk penghuni yang sedang menginap (sudah check-in).',
            ]);
        }

        return $next($request);
    }
}
