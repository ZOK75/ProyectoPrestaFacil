<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteSucursalController extends Controller
{
    /**
     * Dashboard Gerente de Sucursal:
     * - Gestión y supervisión del equipo / personal asignado a su sucursal.
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol', 'sucursal');
        $sucursalId = $operador->sucursal_id;

        // Personal asignado a esta sucursal
        $personalSucursal = User::where('sucursal_id', $sucursalId)
            ->with(['rol', 'sucursal'])
            ->orderBy('name')
            ->get();

        $statsEquipo = [
            'total_personal' => $personalSucursal->count(),
            'activos' => $personalSucursal->where('activo', true)->count(),
            'distribuidores' => $personalSucursal->filter(fn($u) => $u->esDistribuidor())->count(),
            'cajeros' => $personalSucursal->filter(fn($u) => $u->esCajero())->count(),
            'otros' => $personalSucursal->reject(fn($u) => $u->esDistribuidor() || $u->esCajero())->count(),
        ];

        // Distribuidores de la sucursal con sus préstamos
        $distribuidores = User::where('sucursal_id', $sucursalId)
            ->whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']))
            ->with(['prestamos' => fn($q) => $q->where('estado', 'activo')])
            ->orderBy('name')
            ->get();

        // Solicitudes pendientes de incremento de crédito para distribuidores de su sucursal
        $solicitudesCreditoPendientes = \App\Models\SolicitudCredito::where('estado', 'pendiente')
            ->whereHas('distribuidor', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de distribuidores aprobadas por el verificador pero pendientes de cuenta
        $solicitudesAprobadasSinCuenta = \App\Models\SolicitudDistribuidor::where('sucursal_id', $sucursalId)
            ->where('estado', 'aprobado')
            ->whereNull('user_id')
            ->with(['coordinador', 'verificador'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        return view('gerente-sucursal.dashboard', compact(
            'operador',
            'personalSucursal',
            'statsEquipo',
            'distribuidores',
            'solicitudesCreditoPendientes',
            'solicitudesAprobadasSinCuenta'
        ));
    }
}
