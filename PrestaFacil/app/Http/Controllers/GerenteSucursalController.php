<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\SolicitudCliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteSucursalController extends Controller
{
    /**
     * Dashboard Gerente de Sucursal:
     * - Solicitudes pendientes de clientes de su propia sucursal
     * - Préstamos activos por distribuidora de su propia sucursal
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol', 'sucursal');
        $sucursalId = $operador->sucursal_id;

        // Solicitudes pendientes de clientes de su sucursal
        $solicitudesPendientes = SolicitudCliente::where('estado', 'pendiente')
            ->where('sucursal_id', $sucursalId)
            ->with(['cliente', 'distribuidor', 'sucursal'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $totalSolicitudesPendientes = SolicitudCliente::where('estado', 'pendiente')
            ->where('sucursal_id', $sucursalId)
            ->count();

        // Distribuidores pertenecientes a esta sucursal con sus préstamos activos
        $distribuidores = User::where('sucursal_id', $sucursalId)
            ->whereHas('rol', function ($q) {
                $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']);
            })
            ->with(['sucursal', 'prestamos' => function ($qp) {
                $qp->where('estado', 'activo')->with(['cliente', 'productoVale']);
            }])
            ->orderBy('name')
            ->get();

        // Métricas de préstamos activos de la sucursal
        $prestamosActivosSucursalQuery = Prestamo::where('estado', 'activo')
            ->whereHas('createdBy', function ($qu) use ($sucursalId) {
                $qu->where('sucursal_id', $sucursalId);
            });

        $statsPrestamos = [
            'total_activos' => (clone $prestamosActivosSucursalQuery)->count(),
            'monto_prestado' => (clone $prestamosActivosSucursalQuery)->sum('monto_prestamo'),
            'adeudo_pendiente' => (clone $prestamosActivosSucursalQuery)->sum('adeudo_pendiente'),
            'pagos_recibidos' => (clone $prestamosActivosSucursalQuery)->sum('pagos_recibidos'),
        ];

        return view('gerente-sucursal.dashboard', compact(
            'operador',
            'solicitudesPendientes',
            'totalSolicitudesPendientes',
            'distribuidores',
            'statsPrestamos'
        ));
    }
}
