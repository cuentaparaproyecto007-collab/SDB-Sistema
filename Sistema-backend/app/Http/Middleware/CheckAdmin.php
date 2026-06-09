<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, $next)
    {
        // Verificamos si el usuario está logueado y si su rol es el que definiste en el seeder
        if (auth()->check() && auth()->user()->role->nombre === 'Administrador') {
            return $next($request);
        }

        return response()->json([
            'message' => 'Acceso denegado: Se requieren privilegios de Administrador.'
        ], 403);
    }
}
