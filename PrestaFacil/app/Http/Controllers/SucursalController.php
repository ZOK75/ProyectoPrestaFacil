<?php

namespace App\Http\Controllers;

use App\Models\NotificacionCajero;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SucursalController extends Controller
{
    /**
     * Muestra la lista de sucursales y opciones de gestión (Gerente General / Administrador).
     */
    public function index()
    {
        $operador = Auth::user()->load('rol');

        if (!$operador->esAdminGeneralOAdmin()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder al módulo de sucursales.');
        }

        $sucursales = Sucursal::withCount([
            'usuarios' => function ($q) {
                $q->where('activo', true);
            }
        ])->orderBy('nombre')->get();

        // Obtener todos los gerentes de sucursal activos
        $gerentesSucursal = User::whereHas('rol', function ($q) {
            $q->where('nombre', 'Gerente de Sucursal');
        })->where('activo', true)->with('sucursal')->get();

        return view('sucursales.index', compact('sucursales', 'gerentesSucursal', 'operador'));
    }

    /**
     * Crear una nueva sucursal (Gerente General).
     */
    public function store(Request $request)
    {
        $operador = Auth::user();
        if (!$operador->esGerenteGeneral()) {
            return back()->with('error', 'Acceso denegado. Únicamente el Gerente General puede crear sucursales.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:sucursales,nombre',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:50',
        ]);

        $sucursal = Sucursal::create([
            'nombre' => trim($request->nombre),
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'activo' => true,
        ]);

        AuditService::registrar(
            'CREACION_SUCURSAL',
            "Sucursal '{$request->nombre}' creada por {$operador->name}",
            [
                'entidad_tipo' => 'sucursales',
                'entidad_id' => $sucursal->id,
                'user_id' => $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $sucursal->id,
                'despues' => $sucursal->toArray(),
            ]
        );

        return back()->with('success', "Sucursal '{$request->nombre}' creada exitosamente.");
    }

    /**
     * Actualizar una sucursal existente.
     */
    public function update(Request $request, Sucursal $sucursal)
    {
        $operador = Auth::user();
        if (!$operador->esGerenteGeneral()) {
            return back()->with('error', 'Acceso denegado. Únicamente el Gerente General puede modificar sucursales.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:sucursales,nombre,' . $sucursal->id,
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:50',
        ]);

        $datosAntes = $sucursal->toArray();
        $sucursal->update([
            'nombre' => trim($request->nombre),
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
        ]);

        AuditService::registrar(
            'ACTUALIZACION_SUCURSAL',
            "Sucursal '{$sucursal->nombre}' actualizada por {$operador->name}",
            [
                'entidad_tipo' => 'sucursales',
                'entidad_id' => $sucursal->id,
                'user_id' => $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $sucursal->id,
                'antes' => $datosAntes,
                'despues' => $sucursal->fresh()->toArray(),
            ]
        );

        return back()->with('success', "Sucursal '{$sucursal->nombre}' actualizada exitosamente.");
    }

    /**
     * Alternar estado activo/inactivo (Desactivación lógica). No borra nada de BD.
     */
    public function toggleStatus(Sucursal $sucursal)
    {
        $operador = Auth::user();
        if (!$operador->esGerenteGeneral()) {
            return back()->with('error', 'Acceso denegado. Únicamente el Gerente General puede cambiar el estado de una sucursal.');
        }

        $nuevoEstado = !$sucursal->activo;
        $sucursal->update(['activo' => $nuevoEstado]);

        $estadoTexto = $nuevoEstado ? 'activada' : 'desactivada';

        AuditService::registrar(
            'CAMBIO_ESTADO_SUCURSAL',
            "Sucursal '{$sucursal->nombre}' {$estadoTexto} por {$operador->name}",
            [
                'entidad_tipo' => 'sucursales',
                'entidad_id' => $sucursal->id,
                'user_id' => $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $sucursal->id,
                'despues' => $sucursal->fresh()->toArray(),
            ]
        );

        return back()->with('success', "La sucursal '{$sucursal->nombre}' ha sido {$estadoTexto}.");
    }

    /**
     * Reasignar Gerente de Sucursal a otra sucursal con propagación EN CASCADA:
     * - Gerente cambia sucursal_id
     * - Coordinadores asociados cambian sucursal_id
     * - Distribuidoras asociadas cambian sucursal_id
     */
    public function reasignarGerente(Request $request)
    {
        $operador = Auth::user();
        if (!$operador->esGerenteGeneral()) {
            return back()->with('error', 'Acceso denegado. Únicamente el Gerente General puede mover gerentes de sucursal.');
        }

        $request->validate([
            'gerente_id' => 'required|exists:users,id',
            'nueva_sucursal_id' => 'required|exists:sucursales,id',
        ]);

        $gerente = User::findOrFail($request->gerente_id);

        if (!$gerente->esGerenteSucursal()) {
            return back()->with('error', 'El usuario seleccionado no es un Gerente de Sucursal.');
        }

        $antiguaSucursalId = $gerente->sucursal_id;
        $nuevaSucursal = Sucursal::findOrFail($request->nueva_sucursal_id);

        if ($antiguaSucursalId === $nuevaSucursal->id) {
            return back()->with('info', "El Gerente {$gerente->name} ya pertenece a la sucursal '{$nuevaSucursal->nombre}'.");
        }

        $antiguaSucursalNombre = $gerente->sucursal?->nombre ?? 'Sin Asignar';

        DB::transaction(function () use ($gerente, $antiguaSucursalId, $nuevaSucursal, $operador) {
            // 1. Mover al Gerente
            $gerente->update(['sucursal_id' => $nuevaSucursal->id]);

            // 2. Mover en cascada a todos los Coordinadores de la antigua sucursal
            $coordinadores = User::whereHas('rol', fn($q) => $q->where('nombre', 'Coordinador'))
                ->where('sucursal_id', $antiguaSucursalId)
                ->get();

            foreach ($coordinadores as $coord) {
                $coord->update(['sucursal_id' => $nuevaSucursal->id]);

                // 3. Mover en cascada a las Distribuidoras asignadas a este Coordinador
                User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
                    ->where('coordinador_id', $coord->id)
                    ->update(['sucursal_id' => $nuevaSucursal->id]);

                // Notificar al Coordinador
                NotificacionCajero::enviar(
                    $coord->id,
                    'informativa',
                    'Reasignación Corporativa de Sucursal',
                    "El Gerente General ha reasignado al Gerente {$gerente->name} a la sucursal '{$nuevaSucursal->nombre}'. Tu cuenta y tus distribuidoras asociadas se han transferido automáticamente a la nueva sucursal."
                );
            }

            // Notificar al Gerente de Sucursal
            NotificacionCajero::enviar(
                $gerente->id,
                'informativa',
                'Cambio de Sucursal Asignada',
                "Has sido reasignado oficialmente a la sucursal '{$nuevaSucursal->nombre}' por la Gerencia General. Toda tu estructura de Coordinadores y Distribuidoras ha sido transferida."
            );

            AuditService::registrar(
                'REASIGNACION_GERENTE_SUCURSAL',
                "Gerente '{$gerente->name}' reasignado a sucursal '{$nuevaSucursal->nombre}' por {$operador->name}",
                [
                    'entidad_tipo' => 'users',
                    'entidad_id' => $gerente->id,
                    'user_id' => $operador->id,
                    'user_rol' => $operador->rol?->nombre,
                    'sucursal_id' => $nuevaSucursal->id,
                ]
            );
        });

        return back()->with('success', "Se ha trasladado al Gerente '{$gerente->name}' a la sucursal '{$nuevaSucursal->nombre}' junto con toda su estructura organizativa.");
    }
}
