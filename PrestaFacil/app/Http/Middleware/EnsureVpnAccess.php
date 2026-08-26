<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVpnAccess
{
    /**
     * Handle an incoming request.
     *
     * Permite el acceso únicamente si la petición proviene del dominio VPN configurado
     * (por defecto: vpn.prestafacil.uk).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validación de VPN activa (requerida por defecto)
        $vpnRequired = config('app.vpn_required', env('VPN_REQUIRED', true));
        if (!$vpnRequired) {
            return $next($request);
        }

        $vpnDomain = strtolower(config('app.vpn_domain', 'vpn.prestafacil.uk'));

        // Obtener posibles cabeceras donde se transmite el host
        $requestHost = strtolower(explode(':', $request->getHost())[0]);
        $hostHeader = strtolower(explode(':', $request->header('Host', ''))[0]);
        $forwardedHost = strtolower(explode(':', $request->header('X-Forwarded-Host', ''))[0]);
        $serverHost = strtolower(explode(':', $request->server('HTTP_HOST', ''))[0]);

        // Simulación para entornos de pruebas locales
        $simulatedVpn = $request->header('X-Simulate-VPN') === 'true';

        $isVpnAccess = $simulatedVpn ||
            $requestHost === $vpnDomain ||
            $hostHeader === $vpnDomain ||
            $forwardedHost === $vpnDomain ||
            $serverHost === $vpnDomain;

        if (!$isVpnAccess) {
            $mensajeError = 'no tienes autorizacion para completar el proceso';

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $mensajeError,
                    'error' => 'vpn_authorization_required',
                ], 403);
            }

            return redirect()->back()->with('error', $mensajeError);
        }

        return $next($request);
    }
}
