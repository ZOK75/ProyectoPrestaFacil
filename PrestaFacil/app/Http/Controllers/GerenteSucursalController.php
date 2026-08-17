<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteSucursalController extends Controller
{
    /**
     * Dashboard Gerente de Sucursal:
     * - Gestión y supervisión del equipo / personal asignado a su sucursal.
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol', 'sucursal');
        $sucursalId = $operador->sucursal_id;

        // Personal asignado a esta sucursal
        $personalSucursal = User::where('sucursal_id', $sucursalId)
            ->with(['rol', 'sucursal'])
            ->orderBy('name')
            ->get();

        $statsEquipo = [
            'total_personal' => $personalSucursal->count(),
            'activos' => $personalSucursal->where('activo', true)->count(),
            'distribuidores' => $personalSucursal->filter(fn($u) => $u->esDistribuidor())->count(),
            'cajeros' => $personalSucursal->filter(fn($u) => $u->esCajero())->count(),
            'otros' => $personalSucursal->reject(fn($u) => $u->esDistribuidor() || $u->esCajero())->count(),
        ];

        // Distribuidores de la sucursal con sus préstamos
        $distribuidores = User::where('sucursal_id', $sucursalId)
            ->whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']))
            ->with(['prestamos' => fn($q) => $q->where('estado', 'activo')])
            ->orderBy('name')
            ->get();

        // Solicitudes pendientes de incremento de crédito para distribuidores de su sucursal
        $solicitudesCreditoPendientes = \App\Models\SolicitudCredito::where('estado', 'pendiente')
            ->whereHas('distribuidor', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes en espera de la decisión final del gerente (dictaminadas por verificador)
        $solicitudesEnEspera = \App\Models\SolicitudDistribuidor::where('sucursal_id', $sucursalId)
            ->where('estado', 'en espera')
            ->with(['coordinador', 'verificador'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Solicitudes de distribuidores aprobadas por el gerente pero pendientes de cuenta
        $solicitudesAprobadasSinCuenta = \App\Models\SolicitudDistribuidor::where('sucursal_id', $sucursalId)
            ->where('estado', 'aprobado')
            ->whereNull('user_id')
            ->with(['coordinador', 'verificador'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        return view('gerente-sucursal.dashboard', compact(
            'operador',
            'personalSucursal',
            'statsEquipo',
            'distribuidores',
            'solicitudesCreditoPendientes',
            'solicitudesEnEspera',
            'solicitudesAprobadasSinCuenta'
        ));
    }

    /**
     * Procesar la decisión final del Gerente (Aprobar o Rechazar)
     * basándose en la solicitud "en espera" (después del Verificador).
     */
    public function decidirSolicitudDistribuidor(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones_resolucion' => 'nullable|string|max:1000'
        ]);

        $solicitud = \App\Models\SolicitudDistribuidor::findOrFail($id);

        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para decidir sobre esta solicitud.');
        }

        if ($solicitud->estado !== 'en espera') {
            return back()->with('error', 'Esta solicitud ya no está en espera de decisión.');
        }

        if ($request->accion === 'aprobar') {
            $solicitud->estado = 'aprobado';
            $solicitud->observaciones_resolucion = $request->observaciones_resolucion;
            $solicitud->resolved_at = now();
            $solicitud->save();

            return redirect()->route('gerente-sucursal.dashboard')
                ->with('success', "La solicitud de {$solicitud->nombre_completo} ha sido APROBADA. Ahora debes crear su cuenta para activar su acceso.");
        } else {
            $solicitud->estado = 'rechazado';
            $solicitud->observaciones_resolucion = $request->observaciones_resolucion;
            $solicitud->resolved_at = now();
            $solicitud->save();

            // Notificar al coordinador que fue rechazada (Paso 7/11)
            \App\Models\NotificacionCajero::enviar(
                $solicitud->coordinador_id,
                'alerta',
                'Solicitud Rechazada por Gerencia',
                "La solicitud de {$solicitud->nombre_completo} fue rechazada por el Gerente. Verificador dictaminó: {$solicitud->dictamen_verificador}. Comentarios finales: " . ($request->observaciones_resolucion ?? 'Sin comentarios.')
            );

            return redirect()->route('gerente-sucursal.dashboard')
                ->with('info', "La solicitud de {$solicitud->nombre_completo} ha sido RECHAZADA de forma definitiva.");
        }
    }
}
