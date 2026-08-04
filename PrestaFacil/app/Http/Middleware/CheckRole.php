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
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        // Permite acceso si es Admin (rol_id 1) o Gerente General (rol_id 2) cuando la ruta pide 'gerente_general'
        if ($role === 'gerente_general' && in_array($user->rol_id, [1, 2])) {
            return $next($request);
        }

        // Validación general por relación con la tabla roles
        if ($user->rol && strtolower(str_replace(' ', '_', $user->rol->nombre)) === strtolower($role)) {
            return $next($request);
        }

        abort(403, 'Acceso no autorizado al panel de Mis Vales.');
    }
}
