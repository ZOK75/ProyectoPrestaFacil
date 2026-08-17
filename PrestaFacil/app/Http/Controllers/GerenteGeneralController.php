<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\ProductoVale;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteGeneralController extends Controller
{
    /**
     * Dashboard Gerente General:
     * - Supervisión corporativa de sucursales, personal, catálogo de vales y reglas financieras.
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol');

        $sucursales = Sucursal::with(['usuarios.rol'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $valesActivos = ProductoVale::where('activo', true)->orderBy('monto_prestamo')->get();
        $configuracion = Configuracion::actual();

        $statsCorporativas = [
            'total_sucursales' => $sucursales->count(),
            'total_usuarios' => User::where('activo', true)->count(),
            'distribuidores' => User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))->where('activo', true)->count(),
            'cajeros' => User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Cajero', 'Cajera']))->where('activo', true)->count(),
            'vales_catalogo' => $valesActivos->count(),
        ];

        $solicitudesCreditoPendientes = \App\Models\SolicitudCredito::where('estado', 'pendiente')
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de distribuidores aprobadas por el verificador pero pendientes de cuenta a nivel global
        $solicitudesAprobadasSinCuenta = \App\Models\SolicitudDistribuidor::where('estado', 'aprobado')
            ->whereNull('user_id')
            ->with(['coordinador', 'verificador', 'sucursal'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        // Solicitudes en espera de la decisión final de la Gerencia
        $solicitudesEnEspera = \App\Models\SolicitudDistribuidor::where('estado', 'en espera')
            ->with(['coordinador', 'verificador', 'sucursal'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('gerente-general.dashboard', compact(
            'operador',
            'sucursales',
            'valesActivos',
            'configuracion',
            'statsCorporativas',
            'solicitudesCreditoPendientes',
            'solicitudesEnEspera',
            'solicitudesAprobadasSinCuenta'
        ));
    }

    /**
     * Procesar la decisión final del Gerente General (Aprobar o Rechazar)
     * basándose en la solicitud "en espera" (después del Verificador).
     */
    public function decidirSolicitudDistribuidor(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones_resolucion' => 'nullable|string|max:1000'
        ]);

        $solicitud = \App\Models\SolicitudDistribuidor::findOrFail($id);

        if ($solicitud->estado !== 'en espera') {
            return back()->with('error', 'Esta solicitud ya no está en espera de decisión.');
        }

        if ($request->accion === 'aprobar') {
            $solicitud->estado = 'aprobado';
            $solicitud->observaciones_resolucion = $request->observaciones_resolucion;
            $solicitud->resolved_at = now();
            $solicitud->save();

            return redirect()->route('gerente-general.dashboard')
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
                "La solicitud de {$solicitud->nombre_completo} fue rechazada por el Gerente General. Verificador dictaminó: {$solicitud->dictamen_verificador}. Comentarios finales: " . ($request->observaciones_resolucion ?? 'Sin comentarios.')
            );

            return redirect()->route('gerente-general.dashboard')
                ->with('info', "La solicitud de {$solicitud->nombre_completo} ha sido RECHAZADA de forma definitiva.");
        }
    }
}
