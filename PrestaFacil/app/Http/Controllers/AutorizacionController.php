<?php

namespace App\Http\Controllers;

use App\Models\Conciliacion;
use App\Models\SolicitudAutorizacion;
use App\Services\AuditService;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AutorizacionController extends Controller
{
    private function autorizador()
    {
        return Auth::user()->load('sucursal');
    }

    private function verificarBloqueoGerencial($user): ?\Illuminate\Http\RedirectResponse
    {
        if ($user->esGerenteGeneral() || $user->esGerenteSucursal()) {
            $ruta = $user->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            abort(403, 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de autorizaciones.');
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = $this->autorizador();

        if ($redirect = $this->verificarBloqueoGerencial($user)) {
            return $redirect;
        }

        $items = collect();

        // 1. Solicitudes de Autorización Operativas Aprobadas/Aceptadas
        $autorizaciones = SolicitudAutorizacion::with(['solicitante.sucursal', 'autorizador'])
            ->whereIn('estado', ['aprobada', 'aprobado', 'conciliado', 'completada'])
            ->get();

        foreach ($autorizaciones as $aut) {
            $items->push([
                'id' => $aut->id,
                'tipo_categoria' => 'Autorización ' . str_replace('_', ' ', $aut->tipo),
                'usuario_envio' => $aut->solicitante?->name ?? 'Usuario',
                'rol_envio' => $aut->solicitante?->rol?->nombre ?? 'Solicitante',
                'usuario_acepto' => $aut->autorizador?->name ?? ($aut->autorizador_rol ?? 'Autorizador'),
                'rol_acepto' => $aut->autorizador_rol ?? 'Autorizador',
                'estado' => $aut->estado,
                'fecha' => $aut->resolved_at ?? $aut->updated_at,
                'comentario' => $aut->observaciones_resolucion ?: $aut->motivo,
                'sucursal' => $aut->sucursal?->nombre ?? 'N/A',
            ]);
        }

        // 2. Solicitudes de Alta de Distribuidora Dictaminadas/Cuenta Creada
        $solDist = \App\Models\SolicitudDistribuidor::with(['coordinador', 'verificador', 'user', 'sucursal'])
            ->whereIn('estado', ['aprobado', 'cuenta_creada'])
            ->get();

        foreach ($solDist as $sd) {
            $acepto = $sd->user?->name ?? ($sd->verificador?->name ?? 'Gerencia / Verificación');
            $items->push([
                'id' => $sd->id,
                'tipo_categoria' => 'Alta Distribuidora Aprobada',
                'usuario_envio' => $sd->coordinador?->name ?? 'Coordinador',
                'rol_envio' => 'Coordinador',
                'usuario_acepto' => $acepto,
                'rol_acepto' => 'Verificación / Gerencia',
                'estado' => $sd->estado,
                'fecha' => $sd->resolved_at ?? $sd->updated_at,
                'comentario' => $sd->observaciones_verificador ?: 'Dictamen favorable y cuenta creada',
                'sucursal' => $sd->sucursal?->nombre ?? 'N/A',
            ]);
        }

        // 3. Solicitudes de Incremento de Crédito Aprobadas
        $solCred = \App\Models\SolicitudCredito::with(['distribuidor', 'coordinador', 'gerente'])
            ->where('estado', 'aprobada')
            ->get();

        foreach ($solCred as $sc) {
            $items->push([
                'id' => $sc->id,
                'tipo_categoria' => 'Crédito Incrementado',
                'usuario_envio' => $sc->distribuidor?->name ?? 'Distribuidora',
                'rol_envio' => 'Distribuidora',
                'usuario_acepto' => $sc->gerente?->name ?? ($sc->coordinador?->name ?? 'Gerencia'),
                'rol_acepto' => 'Gerencia / Coordinación',
                'estado' => $sc->estado,
                'fecha' => $sc->updated_at,
                'comentario' => "Crédito autorizado: $" . number_format($sc->monto_solicitado, 2) . ". " . ($sc->observaciones_gerente ?? ''),
                'sucursal' => $sc->distribuidor?->sucursal?->nombre ?? 'N/A',
            ]);
        }

        // 4. Traspasos de Distribuidora Formalizados
        $solTransDist = \App\Models\SolicitudTransferencia::with(['distribuidor', 'coordinadorEmisor', 'coordinadorReceptor', 'gerente', 'sucursalDestino'])
            ->where('estado', 'aprobada')
            ->get();

        foreach ($solTransDist as $st) {
            $items->push([
                'id' => $st->id,
                'tipo_categoria' => 'Traspaso Distribuidora Aprobado',
                'usuario_envio' => $st->coordinadorEmisor?->name ?? 'Coordinador Emisor',
                'rol_envio' => 'Coordinador',
                'usuario_acepto' => $st->gerente?->name ?? ($st->coordinadorReceptor?->name ?? 'Gerencia / Receptor'),
                'rol_acepto' => 'Gerencia de Sucursal',
                'estado' => $st->estado,
                'fecha' => $st->resolved_at ?? $st->updated_at,
                'comentario' => "Reasignación aceptada hacia " . ($st->sucursalDestino?->nombre ?? 'Sucursal') . ". " . ($st->observaciones_gerente ?? ''),
                'sucursal' => $st->sucursalDestino?->nombre ?? 'N/A',
            ]);
        }

        // 5. Traspasos de Coordinador Aprobados
        $solTransCoord = \App\Models\SolicitudTransferenciaCoordinador::with(['coordinador', 'gerenteEmisor', 'gerenteReceptor', 'gerenteGeneral', 'sucursalDestino'])
            ->where('estado', 'aprobada')
            ->get();

        foreach ($solTransCoord as $stc) {
            $items->push([
                'id' => $stc->id,
                'tipo_categoria' => 'Traspaso Coordinador Aprobado',
                'usuario_envio' => $stc->gerenteEmisor?->name ?? 'Gerente Emisor',
                'rol_envio' => 'Gerente Sucursal',
                'usuario_acepto' => $stc->gerenteGeneral?->name ?? ($stc->gerenteReceptor?->name ?? 'Gerente General'),
                'rol_acepto' => 'Gerencia General',
                'estado' => $stc->estado,
                'fecha' => $stc->resolved_at ?? $stc->updated_at,
                'comentario' => "Traspaso de coordinador aprobado a " . ($stc->sucursalDestino?->nombre ?? 'Sucursal') . ". " . ($stc->observaciones_gerente_general ?? ''),
                'sucursal' => $stc->sucursalDestino?->nombre ?? 'N/A',
            ]);
        }

        // 6. Conciliaciones Aprobadas
        $solConc = \App\Models\Conciliacion::with(['solicitante', 'conciliadoPor', 'autorizador'])
            ->whereIn('estado', ['conciliado', 'aprobada'])
            ->get();

        foreach ($solConc as $cn) {
            $items->push([
                'id' => $cn->id,
                'tipo_categoria' => 'Conciliación Aplicada',
                'usuario_envio' => $cn->solicitante?->name ?? 'Cajero',
                'rol_envio' => 'Cajero',
                'usuario_acepto' => $cn->conciliadoPor?->name ?? ($cn->autorizador?->name ?? 'Coordinador / Gerencia'),
                'rol_acepto' => $cn->autorizador_rol ?? 'Coordinador / Gerencia',
                'estado' => $cn->estado,
                'fecha' => $cn->resolved_at ?? ($cn->conciliado_at ?? $cn->updated_at),
                'comentario' => "Conciliación aceptada por $" . number_format($cn->monto_corregido, 2) . ". " . ($cn->observaciones_resolucion ?? ''),
                'sucursal' => $cn->solicitante?->sucursal?->nombre ?? 'N/A',
            ]);
        }

        // 7. Solicitudes de Clientes Aprobadas
        $solCli = \App\Models\SolicitudCliente::with(['distribuidor', 'aprobadoPor', 'sucursal'])
            ->where('estado', 'aprobada')
            ->get();

        foreach ($solCli as $sc) {
            $items->push([
                'id' => $sc->id,
                'tipo_categoria' => 'Cambio Cliente Aprobado',
                'usuario_envio' => $sc->distribuidor?->name ?? 'Distribuidora',
                'rol_envio' => 'Distribuidora',
                'usuario_acepto' => $sc->aprobadoPor?->name ?? 'Gerencia / Administrador',
                'rol_acepto' => 'Gerencia / Administrador',
                'estado' => $sc->estado,
                'fecha' => $sc->resolved_at ?? $sc->updated_at,
                'comentario' => $sc->observaciones_resolucion ?: $sc->motivo,
                'sucursal' => $sc->sucursal?->nombre ?? 'N/A',
            ]);
        }

        // Ordenar por fecha descendente
        $items = $items->sortByDesc('fecha')->values();

        return view('autorizaciones.index', compact('items'));
    }

    public function show(SolicitudAutorizacion $solicitud)
    {
        $user = $this->autorizador();

        if ($redirect = $this->verificarBloqueoGerencial($user)) {
            return $redirect;
        }

        if (!$user->esAdministrador() && !$user->puedeAutorizar($solicitud->tipo, $solicitud->sucursal_id)) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        return view('autorizaciones.show', compact('solicitud'));
    }

    public function aprobar(Request $request, SolicitudAutorizacion $solicitud)
    {
        $user = $this->autorizador();

        if ($redirect = $this->verificarBloqueoGerencial($user)) {
            return $redirect;
        }

        if ($user->esAdministrador() || !$user->puedeAutorizar($solicitud->tipo, $solicitud->sucursal_id)) {
            abort(403, 'Acceso denegado: No tienes permisos para aprobar solicitudes.');
        }

        DB::transaction(function () use ($request, $solicitud, $user) {
            $solicitud->aprobar($user, $request->observaciones);

            // Si es conciliación manual, aplicamos el cambio y registramos auditoría de conciliación
            if ($solicitud->tipo === 'conciliacion_manual') {
                $conciliacion = Conciliacion::find($solicitud->entidad_id);
                if ($conciliacion) {
                    $conciliacion->update([
                        'estado' => 'conciliado',
                        'autorizador_id' => $user->id,
                        'autorizador_rol' => $user->rol->nombre,
                        'conciliado_por_user_id' => $user->id,
                        'resolved_at' => now(),
                        'conciliado_at' => now(),
                        'observaciones_resolucion' => $request->observaciones,
                    ]);
                }
            }
            
            // Si es modificación de datos, aplicamos el cambio al cliente
            if ($solicitud->tipo === 'modificacion_datos') {
                $cliente = \App\Models\Cliente::find($solicitud->entidad_id);
                if ($cliente) {
                    $cliente->update($solicitud->datos_propuestos);
                }
            }

            NotificacionService::enviar(
                $solicitud->solicitante_id,
                'SOLICITUD_APROBADA',
                'Solicitud Aprobada',
                "Tu solicitud #{$solicitud->id} ha sido aprobada por {$user->name}."
            );

            AuditService::registrar('APROBAR_SOLICITUD', "Solicitud #{$solicitud->id} aprobada por {$user->name}");
        });

        return redirect()->route('autorizaciones.index')->with('success', 'Solicitud aprobada correctamente.');
    }

    public function rechazar(Request $request, SolicitudAutorizacion $solicitud)
    {
        $request->validate(['motivo' => 'required|string|max:500']);

        $user = $this->autorizador();

        if ($redirect = $this->verificarBloqueoGerencial($user)) {
            return $redirect;
        }

        if ($user->esAdministrador() || !$user->puedeAutorizar($solicitud->tipo, $solicitud->sucursal_id)) {
            abort(403, 'Acceso denegado: No tienes permisos para rechazar solicitudes.');
        }

        DB::transaction(function () use ($request, $solicitud, $user) {
            $solicitud->rechazar($user, $request->motivo, $request->observaciones);

            if ($solicitud->tipo === 'conciliacion_manual') {
                Conciliacion::where('id', $solicitud->entidad_id)->update([
                    'estado' => 'rechazada',
                    'autorizador_id' => $user->id,
                    'autorizador_rol' => $user->rol->nombre,
                    'observaciones_resolucion' => $request->motivo,
                    'resolved_at' => now(),
                ]);
            }

            NotificacionService::enviar(
                $solicitud->solicitante_id,
                'SOLICITUD_RECHAZADA',
                'Solicitud Rechazada',
                "Tu solicitud #{$solicitud->id} fue rechazada: {$request->motivo}"
            );

            AuditService::registrar('RECHAZAR_SOLICITUD', "Solicitud #{$solicitud->id} rechazada por {$user->name}");
        });

        return redirect()->route('autorizaciones.index')->with('success', 'Solicitud rechazada.');
    }
}
