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
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de solicitudes.');
        }

        if (!$operador->esAdministrador()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Este módulo es exclusivo para Administradores de auditoría.');
        }

        return null;
    }

    /**
     * Bandeja de solicitudes de clientes para Administrador (Auditoría).
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAccesoAuditor()) {
            return $redirect;
        }

        $operador = $this->operador();
        $query = SolicitudCliente::with(['cliente', 'distribuidor', 'sucursal', 'aprobadoPor', 'rechazadoPor']);

        // Filtro por estado (default: ver pendientes primero si no se especifica)
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por tipo de solicitud
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtro por sucursal
        if ($request->filled('sucursal_id')) {
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

        if ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de solicitudes.');
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

        return back()->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
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

        return back()->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
    }
}
