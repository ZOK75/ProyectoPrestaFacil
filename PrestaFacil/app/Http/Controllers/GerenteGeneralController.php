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

        // Solicitudes de Traspaso de Distribuidora pendientes de autorización gerencial
        $transferenciasPendientesGerente = \App\Models\SolicitudTransferencia::where('estado', 'pendiente_gerente')
            ->with(['distribuidor', 'coordinadorEmisor', 'coordinadorReceptor', 'sucursalOrigen', 'sucursalDestino'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de Traspaso de Coordinador pendientes de decisión final de la Gerencia General (Paso 2)
        $transferenciasCoordinadorPendientesGG = \App\Models\SolicitudTransferenciaCoordinador::where('estado', 'pendiente_gerente_general')
            ->with(['coordinador', 'gerenteEmisor', 'gerenteReceptor', 'sucursalOrigen', 'sucursalDestino'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('gerente-general.dashboard', compact(
            'operador',
            'sucursales',
            'valesActivos',
            'configuracion',
            'statsCorporativas',
            'solicitudesCreditoPendientes',
            'solicitudesEnEspera',
            'solicitudesAprobadasSinCuenta',
            'transferenciasPendientesGerente',
            'transferenciasCoordinadorPendientesGG'
        ));
    }

    /**
     * Procesar la decisión final del Gerente General (Aprobar o Rechazar) para Traspaso de Coordinador (Paso 2).
     * Si se aprueba, se traslada en cascada al Coordinador y sus Distribuidoras.
     */
    public function decidirTraspasoCoordinadorGG(Request $request, \App\Models\SolicitudTransferenciaCoordinador $transferencia)
    {
        $operador = Auth::user();
        if (!$operador->esGerenteGeneral()) {
            return back()->with('error', 'Acceso denegado. Únicamente la Gerencia General puede emitir el dictamen final.');
        }

        if ($transferencia->estado !== 'pendiente_gerente_general') {
            return back()->with('error', 'Esta solicitud ya no está pendiente de autorización por la Gerencia General.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $coordinador = $transferencia->coordinador;
        $gerenteEmisor = $transferencia->gerenteEmisor;
        $gerenteReceptor = $transferencia->gerenteReceptor;
        $nuevaSucursalId = $transferencia->sucursal_destino_id;

        if ($request->accion === 'rechazar') {
            $transferencia->update([
                'estado' => 'rechazada_gerente_general',
                'observaciones_gerente_general' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            // Notificar a Gerente Emisor, Gerente Receptor y Coordinador
            \App\Models\NotificacionCajero::enviar(
                $gerenteEmisor->id,
                'alerta',
                'Traspaso de Coordinador Rechazado por Dirección General',
                "La Gerencia General ha rechazado el traspaso del coordinador {$coordinador->name} a la sucursal {$transferencia->sucursalDestino?->nombre}." . ($request->observaciones ? " Motivo: {$request->observaciones}" : "")
            );

            \App\Models\NotificacionCajero::enviar(
                $gerenteReceptor->id,
                'alerta',
                'Traspaso de Coordinador Rechazado por Dirección General',
                "La Gerencia General ha rechazado la incorporación del coordinador {$coordinador->name} a tu sucursal." . ($request->observaciones ? " Motivo: {$request->observaciones}" : "")
            );

            \App\Models\NotificacionCajero::enviar(
                $coordinador->id,
                'alerta',
                'Traspaso de Sucursal Cancelado',
                "La Gerencia General no ha autorizado tu transferencia de sucursal. Permaneces asignado a la sucursal {$transferencia->sucursalOrigen?->nombre}."
            );

            return back()->with('info', "Has rechazado el traspaso del coordinador {$coordinador->name}.");
        }

        // APROBAR: Transferencia en CASCADA del Coordinador y sus Distribuidoras
        \Illuminate\Support\Facades\DB::transaction(function () use ($transferencia, $coordinador, $nuevaSucursalId, $request) {
            $transferencia->update([
                'estado' => 'aprobada',
                'observaciones_gerente_general' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            // 1. Mover al Coordinador
            $coordinador->update(['sucursal_id' => $nuevaSucursalId]);

            // 2. Mover en cascada a sus Distribuidoras asociadas
            User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']))
                ->where('coordinador_id', $coordinador->id)
                ->update(['sucursal_id' => $nuevaSucursalId]);
        });

        // Notificaciones de Aprobación
        \App\Models\NotificacionCajero::enviar(
            $gerenteEmisor->id,
            'informativa',
            'Traspaso de Coordinador Aprobado',
            "La Gerencia General ha autorizado formalmente la transferencia del coordinador {$coordinador->name} a la sucursal {$transferencia->sucursalDestino?->nombre}."
        );

        \App\Models\NotificacionCajero::enviar(
            $gerenteReceptor->id,
            'informativa',
            'Coordinador Incorporado a tu Sucursal',
            "¡Traspaso Aprobado! El coordinador {$coordinador->name} y sus distribuidoras asociadas han sido integrados oficialmente a tu sucursal."
        );

        \App\Models\NotificacionCajero::enviar(
            $coordinador->id,
            'informativa',
            'Reasignación Oficial de Sucursal Aprobada',
            "La Gerencia General ha aprobado tu transferencia a la sucursal {$transferencia->sucursalDestino?->nombre}. Toda tu estructura de distribuidoras se ha trasladado contigo."
        );

        return back()->with('success', "Has APROBADO la transferencia del coordinador {$coordinador->name} a la sucursal '{$transferencia->sucursalDestino?->nombre}' exitosamente.");
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
