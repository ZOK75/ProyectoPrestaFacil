<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\Configuracion;
use App\Models\ConfiguracionLog;
use App\Models\User;
use App\Services\AuditService;
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
            abort(403, 'Acceso denegado: Únicamente el Gerente General tiene autorización para modificar la configuración general.');
        }

        $configuracion = Configuracion::actual();
        $validated = $request->validated();
        $userId = Auth::id() ?? $operador->id;

        DB::transaction(function () use ($configuracion, $validated, $userId, $operador) {
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
                'porcentaje_regla_prevale' => $validated['porcentaje_regla_prevale'],
                'tolerancia_regla_prevale' => $validated['tolerancia_regla_prevale'],
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

            AuditService::registrar(
                'ACTUALIZACION_CONFIGURACION',
                "Configuración general del sistema actualizada por " . ($operador?->name ?? 'Gerente General'),
                [
                    'entidad_tipo' => 'configuraciones',
                    'entidad_id' => $configuracion->id,
                    'user_id' => $userId,
                    'user_rol' => $operador?->rol?->nombre,
                    'sucursal_id' => $operador?->sucursal_id,
                    'despues' => $data,
                ]
            );
        });

        // Ejecutar verificación de corte inmediatamente con los nuevos parámetros
        $corteService->verificarYProcesarCortesYVencimientos();

        return redirect()->route('configuracion-general.edit')
            ->with('success', 'Configuración general actualizada y ciclo recalculado correctamente.');
    }

    /**
     * Simulación manual del siguiente corte quincenal (Solo Gerente General).
     */
    public function simularCorte(CorteCobranzaService $corteService)
    {
        $operador = $this->operador();

        if (!$operador || !$operador->esGerenteGeneral()) {
            abort(403, 'Acceso denegado: Únicamente el Gerente General tiene autorización para simular cortes.');
        }

        $resultados = $corteService->simularSiguienteCorte();
        $config = Configuracion::actual();

        $fechaCorteProcesada = $resultados['fecha_corte_procesada'] ?? now();
        $fechaLimiteProcesada = $resultados['fecha_limite_procesada'] ?? $fechaCorteProcesada->copy()->addDays(5);
        $proxFechaCorte = $resultados['proxima_fecha_corte'] ?? $config->fecha_corte;
        $proxFechaLimite = $resultados['proxima_fecha_limite'] ?? $config->fecha_limite_pago;

        $corteStr = $fechaCorteProcesada->format('d/m/Y H:i:s');
        $limiteStr = $fechaLimiteProcesada ? $fechaLimiteProcesada->format('d/m/Y') : 'N/A';
        $proxCorteStr = $proxFechaCorte ? $proxFechaCorte->format('d/m/Y H:i:s') : 'N/A';
        $proxLimiteStr = $proxFechaLimite ? $proxFechaLimite->format('d/m/Y') : 'N/A';

        AuditService::registrar(
            'SIMULACION_CORTE',
            "Simulación de corte quincenal ejecutada por {$operador->name} (Fecha de Corte: {$corteStr} | Fecha Límite de Pago: {$limiteStr}) - {$resultados['multas_aplicadas']} multas aplicadas | Próximo Ciclo: Corte {$proxCorteStr}, Límite {$proxLimiteStr}",
            [
                'entidad_tipo' => 'configuraciones',
                'user_id' => $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $operador->sucursal_id,
                'fecha_corte' => $corteStr,
                'fecha_limite_pago' => $limiteStr,
                'proxima_fecha_corte' => $proxCorteStr,
                'proxima_fecha_limite' => $proxLimiteStr,
                'multas_aplicadas' => $resultados['multas_aplicadas'],
                'cortes_procesados' => $resultados['cortes_procesados'],
                'despues' => [
                    'fecha_corte' => $corteStr,
                    'fecha_limite_pago' => $limiteStr,
                    'proxima_fecha_corte' => $proxCorteStr,
                    'proxima_fecha_limite' => $proxLimiteStr,
                    'multas_aplicadas' => $resultados['multas_aplicadas'],
                    'cortes_procesados' => $resultados['cortes_procesados'],
                ],
            ]
        );

        return back()->with('success', "Corte quincenal procesado con éxito: Fecha de Corte ({$corteStr}), Fecha Límite de Pago ({$limiteStr}). Se acumularon las multas de los vales vencidos ({$resultados['multas_aplicadas']} multas aplicadas) y se avanzó el ciclo quincenal +15 días (Próximo corte: {$proxCorteStr}).");
    }
}