<?php

namespace App\Http\Middleware;

use App\Services\CorteCobranzaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarCorteMiddleware
{
    protected CorteCobranzaService $corteService;

    public function __construct(CorteCobranzaService $corteService)
    {
        $this->corteService = $corteService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica y procesa cortes y vencimientos automáticamente con la hora del servidor en cada petición web
        try {
            $this->corteService->verificarYProcesarCortesYVencimientos();
        } catch (\Throwable $e) {
            // Prevenir que un fallo en la verificación interrumpa la navegación
        }

        return $next($request);
    }
}
