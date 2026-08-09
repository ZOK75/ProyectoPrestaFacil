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
     * Verifica que el usuario tenga permisos gerenciales para gestionar solicitudes.
     */
    private function verificarAccesoGerente(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();
        if (!$operador->esGerenteGeneral() && !$operador->esGerenteSucursal()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Este módulo de autorizaciones es exclusivo para Gerentes.');
        }
        return null;
    }

    /**
     * Bandeja de solicitudes de clientes para Gerentes (General y Sucursal).
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAccesoGerente()) {
            return $redirect;
        }

        $operador = $this->operador();
        $query = SolicitudCliente::with(['cliente', 'distribuidor', 'sucursal', 'aprobadoPor', 'rechazadoPor']);

        // Gerente de Sucursal solo ve solicitudes de su propia sucursal
        if ($operador->esGerenteSucursal()) {
            $query->where('sucursal_id', $operador->sucursal_id);
        }

        // Filtro por estado (default: ver pendientes primero si no se especifica)
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por tipo de solicitud
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtro por sucursal (solo Gerente General)
        if ($request->filled('sucursal_id') && $operador->esGerenteGeneral()) {
            $query->where('sucursal_id', $request->input('sucursal_id'));
        }

        // Búsqueda por texto (Nombre de cliente, CURP o Nombre de Distribuidor)
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('cliente', function ($qc) use ($buscar) {
                    $qc->where('nombre', 'like', "%{$buscar}%")
                       ->orWhere('curp', 'like', "%{$buscar}%");
                })->orWhereHas('distribuidor', function ($qd) use ($buscar) {
                    $qd->where('name', 'like', "%{$buscar}%")
                       ->orWhere('email', 'like', "%{$buscar}%");
                });
            });
        }

        $solicitudes = $query->orderByRaw("CASE WHEN estado = 'pendiente' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $baseCountQuery = SolicitudCliente::query();
        if ($operador->esGerenteSucursal()) {
            $baseCountQuery->where('sucursal_id', $operador->sucursal_id);
        }

        $stats = [
            'total' => (clone $baseCountQuery)->count(),
            'pendientes' => (clone $baseCountQuery)->where('estado', 'pendiente')->count(),
            'aprobadas' => (clone $baseCountQuery)->where('estado', 'aprobada')->count(),
            'rechazadas' => (clone $baseCountQuery)->where('estado', 'rechazada')->count(),
        ];

        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();

        return view('solicitudes-clientes.index', compact('solicitudes', 'stats', 'operador', 'sucursales'));
    }

    /**
     * Detalle y comparador de una solicitud de cliente.
     */
    public function show(SolicitudCliente $solicitud)
    {
        $operador = $this->operador();

        // Validar acceso: Gerente General ve todas; Gerente Sucursal ve de su sucursal; Distribuidor ve las suyas
        if ($operador->esGerenteSucursal() && $solicitud->sucursal_id != $operador->sucursal_id) {
            return redirect()->route('solicitudes-clientes.index')
                ->with('error', 'No tienes permiso para ver solicitudes de otra sucursal.');
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
     * Regla: Con que un solo gerente (de sucursal o general) acepte, queda aprobada.
     */
    public function aprobar(Request $request, SolicitudCliente $solicitud)
    {
        if ($redirect = $this->verificarAccesoGerente()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esGerenteSucursal() && $solicitud->sucursal_id != $operador->sucursal_id) {
            return back()->with('error', 'No puedes autorizar solicitudes de otra sucursal.');
        }

        if (!$solicitud->esPendiente()) {
            return back()->with('error', 'Esta solicitud ya fue procesada anteriormente por otro gerente.');
        }

        $observaciones = $request->input('observaciones_resolucion');
        $solicitud->aplicarAprobacion($operador, $observaciones);

        $accion = $solicitud->esDesactivacion() ? 'desactivación' : 'actualización de datos';
        return redirect()->route('solicitudes-clientes.index')
            ->with('success', "La solicitud de {$accion} para el cliente '{$solicitud->cliente->nombre}' ha sido APROBADA con éxito.");
    }

    /**
     * Rechazar la solicitud de cliente.
     */
    public function rechazar(Request $request, SolicitudCliente $solicitud)
    {
        if ($redirect = $this->verificarAccesoGerente()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esGerenteSucursal() && $solicitud->sucursal_id != $operador->sucursal_id) {
            return back()->with('error', 'No puedes resolver solicitudes de otra sucursal.');
        }

        if (!$solicitud->esPendiente()) {
            return back()->with('error', 'Esta solicitud ya fue procesada anteriormente.');
        }

        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ], [
            'motivo_rechazo.required' => 'Debes ingresar el motivo del rechazo para informar al distribuidor.',
        ]);

        $solicitud->aplicarRechazo($operador, $request->input('motivo_rechazo'));

        return redirect()->route('solicitudes-clientes.index')
            ->with('info', "La solicitud #{$solicitud->id} ha sido RECHAZADA.");
    }
}
