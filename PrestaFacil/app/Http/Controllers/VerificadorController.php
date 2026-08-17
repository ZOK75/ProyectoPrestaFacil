<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\SolicitudDistribuidor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificadorController extends Controller
{
    /**
     * Dashboard del Verificador:
     * - Muestra solicitudes "en espera de verificacion" en su misma sucursal
     * - Historial de solicitudes procesadas por este verificador
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Solicitudes pendientes de evaluar en la sucursal del verificador
        $solicitudesPendientes = SolicitudDistribuidor::where('sucursal_id', $user->sucursal_id)
            ->where('estado', 'en espera de verificacion')
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes resueltas por este verificador
        $solicitudesResueltas = SolicitudDistribuidor::where('verificador_id', $user->id)
            ->with(['coordinador'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        return view('verificador.dashboard', compact('solicitudesPendientes', 'solicitudesResueltas'));
    }

    /**
     * Detalle de la solicitud para el verificador
     */
    public function showSolicitud(SolicitudDistribuidor $solicitud)
    {
        // Validar que la solicitud sea de la sucursal del verificador
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        $solicitud->load(['coordinador', 'sucursal']);

        return view('verificador.solicitudes.show', compact('solicitud'));
    }

    /**
     * Procesar solicitud (Aprobar / Rechazar)
     */
    public function procesarSolicitud(Request $request, SolicitudDistribuidor $solicitud)
    {
        // Validar sucursal
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para procesar esta solicitud.');
        }

        if ($solicitud->estado !== 'en espera de verificacion') {
            return back()->with('error', 'Esta solicitud ya no se encuentra pendiente de verificación.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones_resolucion' => 'required|string|max:1000',
        ], [
            'observaciones_resolucion.required' => 'Debes registrar observaciones o notas sobre la evaluación presencial.',
        ]);

        $verificador = Auth::user();

        if ($request->accion === 'aprobar') {
            $solicitud->update([
                'dictamen_verificador' => 'aceptado',
                'comentarios_verificador' => $request->observaciones_resolucion,
                'verificador_id' => $verificador->id,
                'estado' => 'en espera', // Pasa al Gerente
            ]);

            return redirect()->route('verificador.dashboard')
                ->with('success', "La solicitud para {$solicitud->nombre_completo} ha sido verificada y turnada a la Gerencia.");
        } else {
            // Rechazar solicitud por parte del Verificador (Gerente aún decidirá)
            $solicitud->update([
                'dictamen_verificador' => 'rechazado',
                'comentarios_verificador' => $request->observaciones_resolucion,
                'verificador_id' => $verificador->id,
                'estado' => 'en espera', // Pasa al Gerente de todos modos
            ]);

            return redirect()->route('verificador.dashboard')
                ->with('info', "La solicitud para {$solicitud->nombre_completo} ha sido dictaminada como RECHAZADA. Se ha turnado a la Gerencia para la decisión final.");
        }
    }
}
