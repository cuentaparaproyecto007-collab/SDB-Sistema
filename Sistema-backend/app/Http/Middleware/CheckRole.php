<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Verificar si el usuario está logueado y si su rol está dentro de los permitidos en la ruta
        if (auth()->check() && in_array(auth()->user()->role->nombre, $roles)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Acceso denegado: Tu rango actual no cuenta con la autorización requerida para este módulo.'
        ], 403);
    }
}
