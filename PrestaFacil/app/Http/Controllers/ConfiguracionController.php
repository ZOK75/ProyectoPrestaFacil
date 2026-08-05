<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\Configuracion;
use App\Models\ConfiguracionLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    /**
     * Obtiene el usuario operador actual o, mientras no haya sesión,
     * devuelve el primer Gerente General para desarrollo.
     */
    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        // Fallback desarrollo sin sesión: actuar como Gerente General
        return User::whereHas('rol', fn ($q) => $q->where('nombre', 'Gerente General'))
            ->first() ?? User::first();
    }

    /**
     * Muestra el formulario de configuración general con el historial de cambios.
     */
    public function edit()
    {
        $configuracion = Configuracion::actual();
        $configuracion->load(['createdBy', 'updatedBy', 'logs.changedBy']);

        $operador = $this->operador();
        $puedeEditar = $operador ? $operador->esGerenteGeneral() : false;

        return view('configuracion-general.edit', compact('configuracion', 'puedeEditar', 'operador'));
    }

    /**
     * Actualiza la configuración general. Solo permitido para el Gerente General.
     */
    public function update(UpdateConfiguracionRequest $request)
    {
        $operador = $this->operador();

        // Restricción: Solo el Gerente General puede modificar la configuración
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('configuracion-general.edit')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para modificar la configuración general.');
        }

        $configuracion = Configuracion::actual();
        $validated = $request->validated();
        $userId = Auth::id() ?? $operador->id;

        DB::transaction(function () use ($configuracion, $validated, $userId) {
            // 1. Registrar el cambio en el historial ANTES de actualizar
            ConfiguracionLog::create([
                'configuracion_id' => $configuracion->id,
                'fecha_corte' => $validated['fecha_corte'],
                'fecha_limite_pago' => $validated['fecha_limite_pago'],
                'multa_adeudo' => $validated['multa_adeudo'],
                'changed_by_user_id' => $userId,
                'motivo' => $validated['motivo'] ?? null,
                'changed_at' => now(),
            ]);

            // 2. Actualizar la configuración actual
            $data = [
                'fecha_corte' => $validated['fecha_corte'],
                'fecha_limite_pago' => $validated['fecha_limite_pago'],
                'multa_adeudo' => $validated['multa_adeudo'],
                'updated_by_user_id' => $userId,
            ];

            // Si es la primera vez que se guarda, también queda como creador
            if (!$configuracion->created_by_user_id) {
                $data['created_by_user_id'] = $userId;
            }

            $configuracion->update($data);
        });

        return redirect()->route('configuracion-general.edit')
            ->with('success', 'La configuración general fue actualizada correctamente el ' . now()->format('d/m/Y H:i') . '.');
    }
}