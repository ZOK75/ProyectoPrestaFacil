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
        $user = $request->user();

        if (! $user) {
            abort(403, 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esGerenteGeneral() || $user->esAdministrador()) {
            abort(403, 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esCajero()) {
            abort(403, 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }

        abort(403, 'Acceso denegado: Tu rol actual (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
    }

    /**
     * Normaliza los nombres de rol quitando espacios, guiones y mayúsculas.
     */
    private function normalize(string $name): string
    {
        $name = strtolower(trim($name));
        return str_replace(['_', '-', ' ', 'de'], '', $name);
    }
}
