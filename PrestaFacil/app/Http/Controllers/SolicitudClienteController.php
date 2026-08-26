<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\SolicitudCliente;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudClienteController extends Controller
{
    /**
     * Obtiene el operador actual autenticado.
     */
    private function operador(): User
    {
        return Auth::user()->load('rol', 'sucursal');
    }

    /**
     * Verifica que el usuario tenga permisos de auditoría para ver solicitudes.
     */
    private function verificarAccesoAuditor(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();

        if ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            abort(403, 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de solicitudes.');
        }

        if (!$operador->esAdministrador()) {
            abort(403, 'Acceso denegado: Este módulo es exclusivo para Administradores de auditoría.');
        }

        return null;
    }

    /**
     * Bandeja de solicitudes de clientes para Administrador (Auditoría).
     */
    /**
     * Bandeja unificada de todas las solicitudes del sistema para Administrador (Auditoría Corporativa).
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAccesoAuditor()) {
            return $redirect;
        }

        $operador = $this->operador();

        // Colección unificada de todas las solicitudes emitidas en el sistema
        $items = collect();

        // 1. Solicitudes de Alta de Distribuidora (Coordinador -> Verificador -> Gerente -> Cuenta)
        $solDistribuidoras = \App\Models\SolicitudDistribuidor::with(['coordinador', 'verificador', 'user', 'sucursal'])->get();
        foreach ($solDistribuidoras as $sol) {
            $dest = 'Por Asignar (Verificador/Gerencia)';
            if ($sol->user) {
                $dest = $sol->user->name . ' (Distribuidor)';
            } elseif ($sol->verificador) {
                $dest = $sol->verificador->name . ' (Verificador)';
            }
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Alta Distribuidora',
                'tipo_badge_color' => 'indigo',
                'usuario_emisor' => $sol->coordinador?->name ?? 'Coordinador',
                'rol_emisor' => 'Coordinador',
                'usuario_receptor' => $dest,
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => $sol->observaciones_coordinador ?: ($sol->observaciones_verificador ?: 'Solicitud de incorporación de distribuidora'),
                'sucursal' => $sol->sucursal?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // 2. Solicitudes de Incremento de Crédito (Distribuidor -> Coordinador -> Gerente)
        $solCreditos = \App\Models\SolicitudCredito::with(['distribuidor', 'coordinador', 'gerente'])->get();
        foreach ($solCreditos as $sol) {
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Incremento Crédito',
                'tipo_badge_color' => 'purple',
                'usuario_emisor' => $sol->distribuidor?->name ?? 'Distribuidor',
                'rol_emisor' => 'Distribuidora',
                'usuario_receptor' => $sol->gerente?->name ?? ($sol->coordinador?->name ?? 'Gerencia / Coordinación'),
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => "Monto actual: $" . number_format($sol->monto_actual, 2) . " -> Solicitado: $" . number_format($sol->monto_solicitado, 2) . ". " . ($sol->motivo ?? ''),
                'sucursal' => $sol->distribuidor?->sucursal?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // 3. Solicitudes de Traspaso de Distribuidora (Coordinador -> Coordinador -> Gerente)
        $solTransDist = \App\Models\SolicitudTransferencia::with(['distribuidor', 'coordinadorEmisor', 'coordinadorReceptor', 'gerente', 'sucursalDestino'])->get();
        foreach ($solTransDist as $sol) {
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Traspaso Distribuidora',
                'tipo_badge_color' => 'cyan',
                'usuario_emisor' => $sol->coordinadorEmisor?->name ?? 'Coordinador Emisor',
                'rol_emisor' => 'Coordinador Emisor',
                'usuario_receptor' => $sol->gerente?->name ?? ($sol->coordinadorReceptor?->name ?? 'Coordinador Destino'),
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => "Traspaso de {$sol->distribuidor?->name} a {$sol->sucursalDestino?->nombre}. Motivo: {$sol->motivo}",
                'sucursal' => $sol->sucursalDestino?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // 4. Solicitudes de Traspaso de Coordinador (Gerente -> Gerente -> Gerente General)
        $solTransCoord = \App\Models\SolicitudTransferenciaCoordinador::with(['coordinador', 'gerenteEmisor', 'gerenteReceptor', 'sucursalDestino'])->get();
        foreach ($solTransCoord as $sol) {
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Traspaso Coordinador',
                'tipo_badge_color' => 'amber',
                'usuario_emisor' => $sol->gerenteEmisor?->name ?? 'Gerente Emisor',
                'rol_emisor' => 'Gerente Sucursal',
                'usuario_receptor' => $sol->gerenteReceptor?->name ?? 'Gerencia Receptora',
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => "Reasignación del coordinador {$sol->coordinador?->name} a sucursal '{$sol->sucursalDestino?->nombre}'. Motivo: {$sol->motivo}",
                'sucursal' => $sol->sucursalDestino?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // 5. Solicitudes de Conciliación Manual (Cajero -> Coordinador -> Gerencia)
        $solConciliaciones = \App\Models\Conciliacion::with(['solicitante', 'distribuidora', 'autorizador', 'conciliadoPor'])->get();
        foreach ($solConciliaciones as $sol) {
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Conciliación Manual',
                'tipo_badge_color' => 'emerald',
                'usuario_emisor' => $sol->solicitante?->name ?? 'Cajero',
                'rol_emisor' => 'Cajero',
                'usuario_receptor' => $sol->conciliadoPor?->name ?? ($sol->autorizador?->name ?? 'Coordinador / Gerencia'),
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => "Ajuste de ref {$sol->referencia_original} -> {$sol->referencia_conciliacion} por $" . number_format($sol->monto_corregido, 2) . ". Motivo: {$sol->motivo}",
                'sucursal' => $sol->solicitante?->sucursal?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // 6. Solicitudes sobre Clientes (Distribuidor -> Administrador/Gerencia)
        $solClientes = SolicitudCliente::with(['cliente', 'distribuidor', 'sucursal', 'aprobadoPor', 'rechazadoPor'])->get();
        foreach ($solClientes as $sol) {
            $dest = $sol->aprobadoPor?->name ?? ($sol->rechazadoPor?->name ?? 'Administración / Gerencia');
            $items->push([
                'id' => $sol->id,
                'tipo_categoria' => 'Cambio en Cliente (' . ucfirst($sol->tipo) . ')',
                'tipo_badge_color' => 'rose',
                'usuario_emisor' => $sol->distribuidor?->name ?? 'Distribuidora',
                'rol_emisor' => 'Distribuidora',
                'usuario_receptor' => $dest,
                'fecha' => $sol->created_at,
                'estado' => $sol->estado,
                'comentario' => "Cliente: {$sol->cliente?->nombre}. Motivo: {$sol->motivo}",
                'sucursal' => $sol->sucursal?->nombre ?? 'N/A',
                'raw_model' => $sol,
            ]);
        }

        // Aplicar Filtros sobre la colección global
        if ($request->filled('estado')) {
            $est = $request->input('estado');
            $items = $items->filter(function($i) use ($est) {
                if ($est === 'pendiente') {
                    return str_contains(strtolower($i['estado']), 'pendiente') || $i['estado'] === 'en espera';
                }
                return str_contains(strtolower($i['estado']), strtolower($est));
            });
        }

        if ($request->filled('buscar')) {
            $b = strtolower($request->input('buscar'));
            $items = $items->filter(function($i) use ($b) {
                return str_contains(strtolower($i['usuario_emisor']), $b) ||
                       str_contains(strtolower($i['usuario_receptor']), $b) ||
                       str_contains(strtolower($i['comentario']), $b) ||
                       str_contains(strtolower($i['tipo_categoria']), $b);
            });
        }

        // Ordenar por fecha descendente
        $items = $items->sortByDesc('fecha')->values();

        $stats = [
            'total' => $items->count(),
            'pendientes' => $items->filter(fn($i) => str_contains(strtolower($i['estado']), 'pendiente') || $i['estado'] === 'en espera')->count(),
            'aprobadas' => $items->filter(fn($i) => in_array(strtolower($i['estado']), ['aprobada', 'aprobado', 'conciliado', 'completada']))->count(),
            'rechazadas' => $items->filter(fn($i) => str_contains(strtolower($i['estado']), 'rechazad'))->count(),
        ];

        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();

        return view('solicitudes-clientes.index', compact('items', 'stats', 'operador', 'sucursales'));
    }

    /**
     * Detalle y comparador de una solicitud de cliente.
     */
    public function show(SolicitudCliente $solicitud)
    {
        $operador = $this->operador();

        if ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            abort(403, 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de solicitudes.');
        }

        if ($operador->esDistribuidor() && $solicitud->distribuidor_id != $operador->id) {
            return redirect()->route('distribuidor.dashboard')
                ->with('error', 'No tienes permiso para ver esta solicitud.');
        }

        $solicitud->load(['cliente', 'distribuidor', 'sucursal', 'aprobadoPor', 'rechazadoPor']);

        return view('solicitudes-clientes.show', compact('solicitud', 'operador'));
    }

    /**
     * Aprobar la solicitud y aplicar los cambios directamente en el cliente.
     * Restringido para Administrador (solo lectura) y Gerentes.
     */
    public function aprobar(Request $request, SolicitudCliente $solicitud)
    {
        if ($redirect = $this->verificarAccesoAuditor()) {
            return $redirect;
        }

        abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
    }

    /**
     * Rechazar la solicitud de cliente.
     * Restringido para Administrador (solo lectura) y Gerentes.
     */
    public function rechazar(Request $request, SolicitudCliente $solicitud)
    {
        if ($redirect = $this->verificarAccesoAuditor()) {
            return $redirect;
        }

        abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
    }
}
