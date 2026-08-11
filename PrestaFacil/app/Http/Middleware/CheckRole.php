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

        // Validación directa por nombre de rol
        if (in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        // Compatibilidad para rol 'distribuidora' / 'distribuidor'
        if (in_array('distribuidor', $allowedRoles, true) && in_array($userRole, ['distribuidor', 'distribuidora'], true)) {
            return $next($request);
        }

        // Alias 'gerente': permite tanto a Gerente General como Gerente de Sucursal
        if (in_array('gerente', $allowedRoles, true) && ($user->esGerenteGeneral() || $user->esGerenteSucursal())) {
            return $next($request);
        }

        // El Gerente General y Administrador tienen acceso gerencial transversal
        if (in_array($userRole, ['gerentegeneral', 'administrador'], true)) {
            // Si la ruta pide gerencia, autorizaciones, usuarios o configuracion, el Gerente General tiene acceso
            if (array_intersect($allowedRoles, ['gerentedesucursal', 'gerente', 'coordinador', 'admin'])) {
                return $next($request);
            }
        }

        // Si es petición AJAX / API
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'Tu rol no tiene permisos para realizar esta acción.'
            ], 403);
        }

        // Redirección amigable al dashboard correspondiente del usuario con mensaje de error
        return redirect()->route('dashboard')->with('error', 'Acceso denegado: Tu rol actual (' . ($user->rol?->nombre ?? 'Usuario') . ') no tiene permiso para ingresar a esa sección.');
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
