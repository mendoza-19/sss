<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $descripcionRol = $request->user()?->empleado?->rol?->descripcion;

        if (strtolower(trim((string) $descripcionRol)) !== 'administrador') {
            abort(403, 'No tienes permiso para administrar empleados.');
        }

        return $next($request);
    }
}