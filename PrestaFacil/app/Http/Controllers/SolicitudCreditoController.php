<?php

namespace App\Http\Controllers;

use App\Models\SolicitudCredito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudCreditoController extends Controller
{
    /**
     * Procesar solicitud de incremento de crédito (Aprobar o Rechazar)
     */
    public function procesar(Request $request, SolicitudCredito $solicitud)
    {
        $operador = Auth::user()->load('rol');

        // Validar que sea gerente
        if (!$operador->esGerenteGeneral() && !$operador->esGerenteSucursal()) {
            abort(403, 'Acceso denegado: Se requieren permisos de Gerente.');
        }

        // Si es Gerente de Sucursal, validar que el distribuidor pertenezca a su misma sucursal
        if ($operador->esGerenteSucursal() && $solicitud->distribuidor->sucursal_id !== $operador->sucursal_id) {
            abort(403, 'Acceso denegado: El distribuidor no pertenece a tu sucursal.');
        }

        if (!$solicitud->esPendiente()) {
            return back()->with('error', 'Esta solicitud ya ha sido procesada previamente.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $distribuidor = $solicitud->distribuidor;

        if ($request->accion === 'aprobar') {
            // Actualizar la solicitud
            $solicitud->update([
                'estado' => 'aprobado',
                'gerente_id' => $operador->id,
                'observaciones' => $request->observaciones,
            ]);

            // Actualizar el límite de crédito del distribuidor
            $distribuidor->update([
                'limite_credito' => $solicitud->limite_nuevo,
            ]);

            // Notificar al Coordinador
            if ($solicitud->coordinador_id) {
                \App\Models\NotificacionCajero::enviar(
                    $solicitud->coordinador_id,
                    'solicitud_credito_aprobada',
                    'Aumento de Crédito Aprobado',
                    "La Gerencia ({$operador->name}) ha APROBADO la solicitud de aumento de crédito para {$distribuidor->name}. Nuevo límite: $" . number_format($solicitud->limite_nuevo, 2) . "."
                );
            }

            // Notificar al Distribuidor
            \App\Models\NotificacionCajero::enviar(
                $distribuidor->id,
                'solicitud_credito_aprobada',
                'Incremento de Línea de Crédito Aprobado',
                "Tu línea de crédito ha sido incrementada a $" . number_format($solicitud->limite_nuevo, 2) . ". Ya cuentas con más saldo disponible para emitir vales a tus clientes."
            );

            \App\Services\AuditService::registrar(
                'APROBACION_AUMENTO_CREDITO',
                "Incremento de crédito aprobado para '{$distribuidor->name}' de $" . number_format($solicitud->limite_actual, 2) . " a $" . number_format($solicitud->limite_nuevo, 2) . " por {$operador->name}",
                [
                    'entidad_tipo' => 'solicitudes_credito',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'sucursal_id' => $distribuidor->sucursal_id,
                ]
            );

            $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($redirection)
                ->with('success', "Se ha APROBADO el incremento de crédito a {$distribuidor->name}. Nuevo límite: $" . number_format($solicitud->limite_nuevo, 2));
        } else {
            // Rechazar la solicitud
            $solicitud->update([
                'estado' => 'rechazado',
                'gerente_id' => $operador->id,
                'observaciones' => $request->observaciones,
            ]);

            // Notificar al Coordinador
            if ($solicitud->coordinador_id) {
                \App\Models\NotificacionCajero::enviar(
                    $solicitud->coordinador_id,
                    'solicitud_credito_rechazada',
                    'Solicitud de Aumento de Crédito Rechazada',
                    "La Gerencia ({$operador->name}) ha RECHAZADO la solicitud de aumento para {$distribuidor->name}." . ($request->observaciones ? " Observaciones: \"{$request->observaciones}\"" : "")
                );
            }

            \App\Services\AuditService::registrar(
                'RECHAZO_AUMENTO_CREDITO',
                "Solicitud de aumento de crédito para '{$distribuidor->name}' rechazada por {$operador->name}",
                [
                    'entidad_tipo' => 'solicitudes_credito',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'sucursal_id' => $distribuidor->sucursal_id,
                ]
            );

            $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($redirection)
                ->with('info', "Se ha RECHAZADO la solicitud de incremento de crédito para {$distribuidor->name}.");
        }
    }
}
