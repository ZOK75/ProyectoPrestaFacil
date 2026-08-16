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

            $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($redirection)
                ->with('info', "Se ha RECHAZADO la solicitud de incremento de crédito para {$distribuidor->name}.");
        }
    }
}
