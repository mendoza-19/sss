<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = strtolower(trim((string) $request->user()?->empleado?->rol?->descripcion));
        $allowedRoles = array_map(fn (string $name) => strtolower(trim($name)), $roles);

        if (! in_array($role, $allowedRoles, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}