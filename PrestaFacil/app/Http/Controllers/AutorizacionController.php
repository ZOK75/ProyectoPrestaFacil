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
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de autorizaciones.');
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = $this->autorizador();

        if ($redirect = $this->verificarBloqueoGerencial($user)) {
            return $redirect;
        }
        
        $query = SolicitudAutorizacion::with(['solicitante.sucursal'])
            ->orderByRaw("estado = 'pendiente' DESC")
            ->orderBy('created_at', 'desc');

        // El Administrador ve todas
        if (!$user->esAdministrador()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $solicitudes = $query->paginate(20);

        return view('autorizaciones.index', compact('solicitudes'));
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
            return back()->with('error', 'Acceso denegado: No tienes permisos para aprobar solicitudes.');
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
            return back()->with('error', 'Acceso denegado: No tienes permisos para rechazar solicitudes.');
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
