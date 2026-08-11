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

        return view('gerente-sucursal.dashboard', compact(
            'operador',
            'personalSucursal',
            'statsEquipo'
        ));
    }
}
