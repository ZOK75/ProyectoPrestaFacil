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
            return redirect()->route('login');
        }

        // Si el usuario está inactivo, cerrar sesión
        if (isset($user->activo) && !$user->activo) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta se encuentra inactiva. Comunícate con Gerencia.']);
        }

        // Si no se especificaron roles, permite el paso
        if (empty($roles)) {
            return $next($request);
        }

        // Normalizar los roles permitidos en la ruta
        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            $parts = explode(',', $roleGroup);
            foreach ($parts as $r) {
                $allowedRoles[] = $this->normalize($r);
            }
        }

        $userRole = $this->normalize($user->rol?->nombre ?? '');

        // El Administrador tiene prohibidas las mutaciones de datos en toda la aplicación
        if ($userRole === 'administrador' && !($request->isMethod('get') || $request->isMethod('head'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Acceso denegado',
                    'message' => 'El rol de Administrador cuenta con permisos de solo lectura (auditoría).'
                ], 403);
            }
            return redirect()->route('gerente-general.dashboard')->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        // El Administrador tiene acceso de visualización (GET/HEAD) transversal a toda la aplicación
        if ($userRole === 'administrador') {
            if ($request->isMethod('get') || $request->isMethod('head')) {
                return $next($request);
            }
        }

        // Validación directa por nombre de rol
        if (in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        // Compatibilidad para rol 'distribuidora' / 'distribuidor'
        if (in_array('distribuidor', $allowedRoles, true) && in_array($userRole, ['distribuidor', 'distribuidora'], true)) {
            return $next($request);
        }

        // Si es petición AJAX / API
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'Tu rol no tiene permisos para realizar esta acción.'
            ], 403);
        }

        // Redirección amigable directa al dashboard correspondiente del usuario con mensaje de error
        if ($user->esGerenteSucursal()) {
            return redirect()->route('gerente-sucursal.dashboard')->with('error', 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esGerenteGeneral() || $user->esAdministrador()) {
            return redirect()->route('gerente-general.dashboard')->with('error', 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esDistribuidor()) {
            return redirect()->route('distribuidor.dashboard')->with('error', 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }
        if ($user->esCajero()) {
            return redirect()->route('cajero.dashboard')->with('error', 'Acceso denegado: Tu rol (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado: Tu rol actual (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para realizar esta acción.');
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
