<?php

namespace App\Http\Controllers;

use App\Models\NotificacionCajero;
use App\Models\SolicitudCategoria;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudCategoriaController extends Controller
{
    /**
     * Procesar solicitud de cambio de categoría de distribuidor (Aprobar o Rechazar).
     * Puede ser resuelta por el Gerente de Sucursal (de su misma sucursal) o por el Gerente General.
     * Solo un gerente la debe aceptar o rechazar.
     */
    public function procesar(Request $request, SolicitudCategoria $solicitud)
    {
        $operador = Auth::user()->load('rol');

        // Validar que sea gerente
        if (!$operador->esGerenteGeneral() && !$operador->esGerenteSucursal()) {
            abort(403, 'Acceso denegado: Se requieren permisos de Gerente.');
        }

        // Si es Gerente de Sucursal, validar que el distribuidor pertenezca a su misma sucursal
        if ($operador->esGerenteSucursal() && $solicitud->distribuidor->sucursal_id !== $operador->sucursal_id) {
            abort(403, 'Acceso denegado: La distribuidora no pertenece a tu sucursal.');
        }

        if (!$solicitud->esPendiente()) {
            return back()->with('error', 'Esta solicitud ya ha sido procesada previamente por la Gerencia.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'accion.required' => 'Debes indicar una acción a ejecutar (aprobar o rechazar).',
            'accion.in' => 'La acción indicada no es válida.',
            'observaciones.max' => 'Las observaciones no pueden superar los :max caracteres.',
        ]);

        $distribuidor = $solicitud->distribuidor;

        if ($request->accion === 'aprobar') {
            // Actualizar la solicitud
            $solicitud->update([
                'estado' => 'aprobado',
                'gerente_id' => $operador->id,
                'observaciones' => $request->observaciones,
            ]);

            // Actualizar la categoría de la distribuidora
            $distribuidor->update([
                'categoria_distribuidor' => $solicitud->categoria_nueva,
            ]);

            // Notificar al Coordinador
            if ($solicitud->coordinador_id) {
                NotificacionCajero::enviar(
                    $solicitud->coordinador_id,
                    'solicitud_categoria_aprobada',
                    'Aumento de Categoría Aprobado',
                    "La Gerencia ({$operador->name}) ha APROBADO el cambio a categoría " . strtoupper($solicitud->categoria_nueva) . " para {$distribuidor->name}."
                );
            }

            // Notificar a la Distribuidora
            NotificacionCajero::enviar(
                $distribuidor->id,
                'solicitud_categoria_aprobada',
                'Ascenso de Categoría Aprobado',
                "La Gerencia ha aprobado tu ascenso a categoría " . strtoupper($solicitud->categoria_nueva) . ". Tu porcentaje de ganancia por colocación ha sido actualizado."
            );

            AuditService::registrar(
                'APROBACION_AUMENTO_CATEGORIA',
                "Ascenso de categoría de '{$distribuidor->name}' a " . strtoupper($solicitud->categoria_nueva) . " aprobado por {$operador->name}",
                [
                    'entidad_tipo' => 'solicitudes_categoria',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'sucursal_id' => $distribuidor->sucursal_id,
                ]
            );

            $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($redirection)
                ->with('success', "Se ha APROBADO el cambio de categoría a " . strtoupper($solicitud->categoria_nueva) . " para {$distribuidor->name}.");
        } else {
            // Rechazar la solicitud
            $solicitud->update([
                'estado' => 'rechazado',
                'gerente_id' => $operador->id,
                'observaciones' => $request->observaciones,
            ]);

            // Notificar al Coordinador
            if ($solicitud->coordinador_id) {
                NotificacionCajero::enviar(
                    $solicitud->coordinador_id,
                    'solicitud_categoria_rechazada',
                    '❌ Solicitud de Categoría Rechazada',
                    "La Gerencia ({$operador->name}) ha RECHAZADO la solicitud de cambio de categoría para {$distribuidor->name}." . ($request->observaciones ? " Observaciones: \"{$request->observaciones}\"" : "")
                );
            }

            AuditService::registrar(
                'RECHAZO_AUMENTO_CATEGORIA',
                "Solicitud de cambio de categoría para '{$distribuidor->name}' rechazada por {$operador->name}",
                [
                    'entidad_tipo' => 'solicitudes_categoria',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'sucursal_id' => $distribuidor->sucursal_id,
                ]
            );

            $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($redirection)
                ->with('info', "Se ha RECHAZADO la solicitud de cambio de categoría para {$distribuidor->name}.");
        }
    }
}
