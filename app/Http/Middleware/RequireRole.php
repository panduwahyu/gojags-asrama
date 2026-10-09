<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        abort_unless($request->user()?->role === $role, 403, 'Anda tidak memiliki izin untuk halaman ini.');
        return $next($request);
    }
}
