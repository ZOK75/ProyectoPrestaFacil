<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\NotificacionCajero;
use App\Models\Prestamo;
use App\Models\RelacionCobranza;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorteCobranzaService
{
    /**
     * Revisa automáticamente la hora del servidor y procesa:
     * 1. Corte automático y notificación a todas las distribuidoras.
     * 2. Vencimiento de fecha límite y aplicación de multas por adeudo.
     */
    public function verificarYProcesarCortesYVencimientos(): array
    {
        $config = Configuracion::actual();
        $ahora = now();
        $resultados = [
            'cortes_notificados' => 0,
            'multas_aplicadas' => 0,
        ];

        // Obtener todas las distribuidoras activas
        $distribuidoras = User::where('activo', true)
            ->whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->get();

        if ($distribuidoras->isEmpty()) {
            return $resultados;
        }

        DB::transaction(function () use ($config, $ahora, $distribuidoras, &$resultados) {

            // ─────────────────────────────────────────────────────────────
            // 1. CORTE AUTOMÁTICO (al llegar o superar fecha_corte)
            // ─────────────────────────────────────────────────────────────
            if ($config->fecha_corte && $ahora->greaterThanOrEqualTo($config->fecha_corte)) {
                foreach ($distribuidoras as $dist) {
                    $totalProductos = Prestamo::where('created_by_user_id', $dist->id)->sum('monto_prestamo');
                    $adeudoTotal = Prestamo::where('created_by_user_id', $dist->id)->where('estado', 'activo')->sum('adeudo_pendiente');

                    $relacion = RelacionCobranza::firstOrCreate(
                        [
                            'distribuidora_id' => $dist->id,
                            'fecha_corte' => $config->fecha_corte,
                        ],
                        [
                            'fecha_limite_pago' => $config->fecha_limite_pago,
                            'monto_total_periodo' => $totalProductos,
                            'adeudo_pendiente' => $adeudoTotal,
                            'estado_pago' => 'pendiente',
                        ]
                    );

                    // Si no se ha notificado este corte a la distribuidora
                    if (!$relacion->corte_notificado_at) {
                        NotificacionCajero::create([
                            'user_id' => $dist->id,
                            'tipo' => 'corte_generado',
                            'titulo' => '🔔 Corte de Cobranza Generado',
                            'mensaje' => 'Se ha procesado el corte oficial con fecha ' . $config->fecha_corte->format('d/m/Y H:i') . '. Tu Relación de Cobranza ya está lista para descargar en PDF.',
                            'data' => [
                                'fecha_corte' => $config->fecha_corte->toIso8601String(),
                                'fecha_limite' => $config->fecha_limite_pago ? $config->fecha_limite_pago->toIso8601String() : null,
                                'url' => route('prestamos.relacion-pdf', [], false),
                            ],
                            'leida' => false,
                        ]);

                        $relacion->update([
                            'corte_notificado_at' => $ahora,
                            'adeudo_pendiente' => $adeudoTotal,
                            'monto_total_periodo' => $totalProductos,
                        ]);

                        $resultados['cortes_notificados']++;
                    }
                }
            }

            // ─────────────────────────────────────────────────────────────
            // 2. VENCIMIENTO Y MULTAS POR ADEUDO (al superar fecha_limite_pago)
            // ─────────────────────────────────────────────────────────────
            if ($config->fecha_limite_pago && $ahora->greaterThanOrEqualTo($config->fecha_limite_pago)) {
                foreach ($distribuidoras as $dist) {
                    $relacion = RelacionCobranza::where('distribuidora_id', $dist->id)
                        ->where('fecha_corte', $config->fecha_corte)
                        ->first();

                    if ($relacion && !$relacion->multa_aplicada_at && $relacion->estado_pago === 'pendiente') {
                        $prestamosConAdeudo = Prestamo::where('created_by_user_id', $dist->id)
                            ->where('estado', 'activo')
                            ->where('adeudo_pendiente', '>', 0)
                            ->get();

                        if ($prestamosConAdeudo->isNotEmpty()) {
                            $montoMulta = floatval($config->multa_adeudo ?? 300.00);

                            // Aplicar la multa al primer préstamo activo con adeudo
                            $primerPrestamo = $prestamosConAdeudo->first();
                            $primerPrestamo->update([
                                'multas' => $primerPrestamo->multas + $montoMulta,
                                'adeudo_pendiente' => $primerPrestamo->adeudo_pendiente + $montoMulta,
                            ]);

                            $relacion->update([
                                'multa_aplicada' => $montoMulta,
                                'multa_aplicada_at' => $ahora,
                                'adeudo_pendiente' => $relacion->adeudo_pendiente + $montoMulta,
                            ]);

                            NotificacionCajero::create([
                                'user_id' => $dist->id,
                                'tipo' => 'multa_adeudo_aplicada',
                                'titulo' => '⚠️ Multa por Adeudo Vencido',
                                'mensaje' => 'La fecha límite de pago (' . $config->fecha_limite_pago->format('d/m/Y H:i') . ') ha vencido con saldo pendiente. Se ha aplicado una multa por adeudo de $' . number_format($montoMulta, 2) . ' a tu cuenta.',
                                'data' => [
                                    'multa' => $montoMulta,
                                    'fecha_limite' => $config->fecha_limite_pago->toIso8601String(),
                                ],
                                'leida' => false,
                            ]);

                            $resultados['multas_aplicadas']++;
                        }
                    }
                }
            }
        });

        return $resultados;
    }

    /**
     * Evalúa la liquidación de la relación de la distribuidora y aplica las reglas de puntos:
     * 1. Pago Anticipado (< fecha_corte): Acumula puntos según fórmula floor(Total/MontoBase)*PuntosBase.
     * 2. Pago a Tiempo (>= fecha_corte y <= fecha_limite): 0 puntos.
     * 3. Pago Atrasado (> fecha_limite): Pierde el 20% de los puntos que ya tenga acumulados.
     */
    public function evaluarLiquidacionRelacion(User $distribuidora): ?RelacionCobranza
    {
        if (!$distribuidora->esDistribuidor()) {
            return null;
        }

        $config = Configuracion::actual();
        $ahora = now();

        // Verificar si la distribuidora ya tiene todas sus cuentas en adeudo 0
        $adeudoRestante = Prestamo::where('created_by_user_id', $distribuidora->id)
            ->where('estado', 'activo')
            ->where('adeudo_pendiente', '>', 0)
            ->sum('adeudo_pendiente');

        if ($adeudoRestante > 0) {
            return null; // Aún mantiene adeudo pendiente
        }

        $relacion = RelacionCobranza::firstOrCreate(
            [
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => $config->fecha_corte,
            ],
            [
                'fecha_limite_pago' => $config->fecha_limite_pago,
                'monto_total_periodo' => Prestamo::where('created_by_user_id', $distribuidora->id)->sum('monto_prestamo'),
                'adeudo_pendiente' => 0,
                'estado_pago' => 'pendiente',
            ]
        );

        if ($relacion->estado_pago !== 'pendiente') {
            return $relacion; // Ya fue evaluada y liquidada
        }

        // ─────────────────────────────────────────────────────────────
        // CASO 1: Pago Anticipado (antes del corte)
        // ─────────────────────────────────────────────────────────────
        if ($config->fecha_corte && $ahora->lessThan($config->fecha_corte)) {
            $totalProductos = floatval(Prestamo::where('created_by_user_id', $distribuidora->id)->sum('monto_prestamo'));
            $puntosGanados = $config->calcularPuntosPorMonto($totalProductos);

            $distribuidora->puntos = intval($distribuidora->puntos ?? 0) + $puntosGanados;
            $distribuidora->save();

            $relacion->update([
                'estado_pago' => 'pago_anticipado',
                'puntos_ganados' => $puntosGanados,
                'puntos_descontados' => 0,
                'adeudo_pendiente' => 0,
                'liquidado_at' => $ahora,
            ]);

            NotificacionCajero::create([
                'user_id' => $distribuidora->id,
                'tipo' => 'pago_anticipado',
                'titulo' => '🎉 ¡Pago Anticipado Registrado!',
                'mensaje' => "Tu relación de cobranza fue liquidada antes del corte. ¡Has acumulado {$puntosGanados} puntos de bonificación!",
                'data' => ['puntos' => $puntosGanados],
                'leida' => false,
            ]);

            return $relacion;
        }

        // ─────────────────────────────────────────────────────────────
        // CASO 2: Pago a Tiempo (entre corte y fecha límite)
        // ─────────────────────────────────────────────────────────────
        if ($config->fecha_limite_pago && $ahora->lessThanOrEqualTo($config->fecha_limite_pago)) {
            $relacion->update([
                'estado_pago' => 'pago_a_tiempo',
                'puntos_ganados' => 0,
                'puntos_descontados' => 0,
                'adeudo_pendiente' => 0,
                'liquidado_at' => $ahora,
            ]);

            NotificacionCajero::create([
                'user_id' => $distribuidora->id,
                'tipo' => 'pago_a_tiempo',
                'titulo' => '✅ Pago a Tiempo Registrado',
                'mensaje' => 'Tu relación de cobranza ha sido liquidada con éxito dentro de la fecha límite establecida (sin acumulación de puntos).',
                'data' => ['puntos' => 0],
                'leida' => false,
            ]);

            return $relacion;
        }

        // ─────────────────────────────────────────────────────────────
        // CASO 3: Pago Atrasado (después de la fecha límite)
        // ─────────────────────────────────────────────────────────────
        $puntosActuales = intval($distribuidora->puntos ?? 0);
        $puntosDescontados = intval(ceil($puntosActuales * 0.20)); // Pierde el 20% de sus puntos actuales
        $nuevosPuntos = max(0, $puntosActuales - $puntosDescontados);

        $distribuidora->puntos = $nuevosPuntos;
        $distribuidora->save();

        $relacion->update([
            'estado_pago' => 'pago_atrasado',
            'puntos_ganados' => 0,
            'puntos_descontados' => $puntosDescontados,
            'adeudo_pendiente' => 0,
            'liquidado_at' => $ahora,
        ]);

        NotificacionCajero::create([
            'user_id' => $distribuidora->id,
            'tipo' => 'pago_atrasado',
            'titulo' => '⚠️ Pago Atrasado Registrado',
            'mensaje' => "Tu relación de cobranza fue liquidada posterior a la fecha límite. Se ha aplicado el recargo correspondiente y se descontó el 20% de tus puntos acumulados ({$puntosDescontados} pts).",
            'data' => ['puntos_descontados' => $puntosDescontados, 'puntos_restantes' => $nuevosPuntos],
            'leida' => false,
        ]);

        return $relacion;
    }
}
