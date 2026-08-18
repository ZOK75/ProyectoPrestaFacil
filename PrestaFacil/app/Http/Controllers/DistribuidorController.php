<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\SolicitudCliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistribuidorController extends Controller
{
    private function operador(): User
    {
        return Auth::user()->load('rol', 'sucursal');
    }

    /**
     * Dashboard personalizado para el rol de Distribuidor.
     */
    public function dashboard(Request $request)
    {
        $distribuidor = $this->operador();

        // Validar que sea distribuidor o gerente general
        if (!$distribuidor->esDistribuidor() && !$distribuidor->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso exclusivo para Distribuidores.');
        }

        // Métricas financieras del distribuidor
        $limiteCredito = floatval($distribuidor->limite_credito ?? 20000.00);
        $creditoUtilizado = $distribuidor->creditoUtilizado();
        $creditoDisponible = $distribuidor->creditoDisponible();
        $porcentajeGanancia = $distribuidor->obtenerPorcentajeGanancia();
        $montoMaximoVale = $distribuidor->montoMaximoPermitidoPorVale();
        $referenciaPago = $distribuidor->referenciaPago();
        $puntos = intval($distribuidor->puntos ?? 0);

        // Clientes del distribuidor
        $totalClientes = Cliente::where('created_by_user_id', $distribuidor->id)->count();
        $clientesActivos = Cliente::where('created_by_user_id', $distribuidor->id)->where('activo', true)->count();

        // Préstamos / Vales activos (entregados)
        $prestamosActivos = Prestamo::where('created_by_user_id', $distribuidor->id)
            ->where('estado', 'activo')
            ->with(['cliente', 'productoVale'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Préstamos / Vales pendientes de entrega en caja
        $prestamosPendientes = Prestamo::where('created_by_user_id', $distribuidor->id)
            ->where('estado', 'pendiente')
            ->with(['cliente', 'productoVale'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPrestadoActivo = $prestamosActivos->sum('monto_prestamo');
        $totalAdeudoPendiente = $prestamosActivos->sum('adeudo_pendiente');
        $totalCobrado = $prestamosActivos->sum('pagos_recibidos');

        // Solicitudes recientes enviadas por este distribuidor
        $solicitudesRecientes = SolicitudCliente::where('distribuidor_id', $distribuidor->id)
            ->with(['cliente', 'aprobadoPor', 'rechazadoPor'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $solicitudesPendientesCount = SolicitudCliente::where('distribuidor_id', $distribuidor->id)
            ->where('estado', 'pendiente')
            ->count();

        return view('distribuidor.dashboard', compact(
            'distribuidor',
            'limiteCredito',
            'creditoUtilizado',
            'creditoDisponible',
            'porcentajeGanancia',
            'montoMaximoVale',
            'referenciaPago',
            'puntos',
            'totalClientes',
            'clientesActivos',
            'prestamosActivos',
            'prestamosPendientes',
            'totalPrestadoActivo',
            'totalAdeudoPendiente',
            'totalCobrado',
            'solicitudesRecientes',
            'solicitudesPendientesCount'
        ));
    }
}
