<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\SolicitudCliente;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GerenteGeneralController extends Controller
{
    /**
     * Dashboard Gerente General:
     * - Solicitudes pendientes de clientes de todas las sucursales
     * - Préstamos activos por distribuidora (con filtro por sucursal)
     */
    public function index(Request $request): View
    {
        $sucursalId = $request->input('sucursal_id');

        // Solicitudes pendientes de clientes (global)
        $solicitudesPendientes = SolicitudCliente::where('estado', 'pendiente')
            ->with(['cliente', 'distribuidor', 'sucursal'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $totalSolicitudesPendientes = SolicitudCliente::where('estado', 'pendiente')->count();

        // Consulta de Distribuidores con Préstamos Activos
        $distribuidoresQuery = User::whereHas('rol', function ($q) {
            $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']);
        })->with(['sucursal', 'prestamos' => function ($qp) {
            $qp->where('estado', 'activo')->with(['cliente', 'productoVale']);
        }]);

        if ($request->filled('sucursal_id')) {
            $distribuidoresQuery->where('sucursal_id', $sucursalId);
        }

        $distribuidores = $distribuidoresQuery->orderBy('name')->get();

        // Métricas globales de préstamos activos
        $prestamosActivosGlobalQuery = Prestamo::where('estado', 'activo');
        if ($request->filled('sucursal_id')) {
            $prestamosActivosGlobalQuery->whereHas('createdBy', function ($qu) use ($sucursalId) {
                $qu->where('sucursal_id', $sucursalId);
            });
        }

        $statsPrestamos = [
            'total_activos' => (clone $prestamosActivosGlobalQuery)->count(),
            'monto_prestado' => (clone $prestamosActivosGlobalQuery)->sum('monto_prestamo'),
            'adeudo_pendiente' => (clone $prestamosActivosGlobalQuery)->sum('adeudo_pendiente'),
            'pagos_recibidos' => (clone $prestamosActivosGlobalQuery)->sum('pagos_recibidos'),
        ];

        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();

        return view('gerente-general.dashboard', compact(
            'solicitudesPendientes',
            'totalSolicitudesPendientes',
            'distribuidores',
            'statsPrestamos',
            'sucursales',
            'sucursalId'
        ));
    }
}
