<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\Configuracion;
use App\Models\ConfiguracionLog;
use App\Models\User;
use App\Services\CorteCobranzaService;
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
    public function update(UpdateConfiguracionRequest $request, CorteCobranzaService $corteService)
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
            // Asignar los campos temporales para calcular las fechas
            $configuracion->dia_corte = $validated['dia_corte'];
            $configuracion->hora_corte = $validated['hora_corte'];
            $configuracion->dia_limite_pago = $validated['dia_limite_pago'];
            $configuracion->hora_limite_pago = $validated['hora_limite_pago'];

            $fechaCorteCalculada = $configuracion->fechaCorteCalculada();
            $fechaLimiteCalculada = $configuracion->fechaLimitePagoCalculada();

            $multaAdeudo = floatval($validated['multa_adeudo'] ?? $configuracion->multa_adeudo ?? 0.00);

            // 1. Registrar el cambio en el historial ANTES de actualizar
            ConfiguracionLog::create([
                'configuracion_id' => $configuracion->id,
                'fecha_corte' => $fechaCorteCalculada,
                'fecha_limite_pago' => $fechaLimiteCalculada,
                'multa_adeudo' => $multaAdeudo,
                'changed_by_user_id' => $userId,
                'motivo' => $validated['motivo'] ?? "Ajuste de ciclo periódico (Corte: Día {$validated['dia_corte']} a las {$validated['hora_corte']}, Límite: Día {$validated['dia_limite_pago']} a las {$validated['hora_limite_pago']}).",
                'changed_at' => now(),
            ]);

            // 2. Actualizar la configuración actual
            $data = [
                'dia_corte' => $validated['dia_corte'],
                'hora_corte' => $validated['hora_corte'],
                'dia_limite_pago' => $validated['dia_limite_pago'],
                'hora_limite_pago' => $validated['hora_limite_pago'],
                'fecha_corte' => $fechaCorteCalculada,
                'fecha_limite_pago' => $fechaLimiteCalculada,
                'multa_adeudo' => $multaAdeudo,
                'comision_cobre' => $validated['comision_cobre'],
                'comision_plata' => $validated['comision_plata'],
                'comision_oro' => $validated['comision_oro'],
                'monto_base_puntos' => $validated['monto_base_puntos'],
                'puntos_por_monto_base' => $validated['puntos_por_monto_base'],
                'valor_punto' => $validated['valor_punto'],
                'updated_by_user_id' => $userId,
            ];

            // Si es la primera vez que se guarda, también queda como creador
            if (!$configuracion->created_by_user_id) {
                $data['created_by_user_id'] = $userId;
            }

            $configuracion->update($data);
        });

        // Ejecutar verificación de corte inmediatamente con los nuevos parámetros
        $corteService->verificarYProcesarCortesYVencimientos();

        return redirect()->route('configuracion-general.edit')
            ->with('success', 'La configuración periódica de corte (Día ' . $validated['dia_corte'] . ' ' . $validated['hora_corte'] . ') y fecha límite (Día ' . $validated['dia_limite_pago'] . ' ' . $validated['hora_limite_pago'] . ') fueron actualizadas con éxito.');
    }

    /**
     * Simula la ejecución de un corte quincenal completo:
     * - Aplica y ACUMULA las multas moratorias individuales a cada vale activo con adeudo pendiente.
     * - Cada vez que se presione el botón, se suman nuevos cargos moratorios a los vales no liquidados.
     * - Avanza automáticamente 15 días (+15d) el ciclo periódico.
     */
    public function simularCorte(CorteCobranzaService $corteService)
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('configuracion-general.edit')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para simular cortes.');
        }

        $resultados = $corteService->simularSiguienteCorte();
        $config = Configuracion::actual();

        return back()->with('success', "⚡ Corte quincenal simulado con éxito: Se acumularon las multas de los vales vencidos ({$resultados['multas_aplicadas']} multas aplicadas) y se avanzó el ciclo quincenal +15 días (Próximo corte: Día {$config->dia_corte} a las " . substr($config->hora_corte, 0, 5) . " hrs).");
    }
}