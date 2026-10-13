<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless(
            $request->user()?->role === Role::from($role),
            403,
            'Anda tidak memiliki izin untuk halaman ini.'
        );

        return $next($request);
    }
}
