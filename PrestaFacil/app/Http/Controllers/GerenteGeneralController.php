<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\ProductoVale;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteGeneralController extends Controller
{
    /**
     * Dashboard Gerente General:
     * - Supervisión corporativa de sucursales, personal, catálogo de vales y reglas financieras.
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol');

        $sucursales = Sucursal::with(['usuarios.rol'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $valesActivos = ProductoVale::where('activo', true)->orderBy('monto_prestamo')->get();
        $configuracion = Configuracion::actual();

        $statsCorporativas = [
            'total_sucursales' => $sucursales->count(),
            'total_usuarios' => User::where('activo', true)->count(),
            'distribuidores' => User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))->where('activo', true)->count(),
            'cajeros' => User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Cajero', 'Cajera']))->where('activo', true)->count(),
            'vales_catalogo' => $valesActivos->count(),
        ];

        $solicitudesCreditoPendientes = \App\Models\SolicitudCredito::where('estado', 'pendiente')
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de distribuidores aprobadas por el verificador pero pendientes de cuenta a nivel global
        $solicitudesAprobadasSinCuenta = \App\Models\SolicitudDistribuidor::where('estado', 'aprobado')
            ->whereNull('user_id')
            ->with(['coordinador', 'verificador', 'sucursal'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        return view('gerente-general.dashboard', compact(
            'operador',
            'sucursales',
            'valesActivos',
            'configuracion',
            'statsCorporativas',
            'solicitudesCreditoPendientes',
            'solicitudesAprobadasSinCuenta'
        ));
    }
}
