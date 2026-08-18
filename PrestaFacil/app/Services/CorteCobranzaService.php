<?php

namespace App\Services;

use App\Models\Conciliacion;
use App\Models\Configuracion;
use App\Models\NotificacionCajero;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\RelacionCobranza;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorteCobranzaService
{
    /**
     * Revisa automáticamente la hora del servidor y procesa:
     * 1. Corte automático, cálculo de puntos por liquidación de la cuota 15nal antes del corte
     *    (considerando pagos normales y conciliaciones con fecha de pago anterior al corte).
     * 2. Vencimiento de fecha límite y aplicación de multas por adeudo quincenal pendiente.
     */
    public function verificarYProcesarCortesYVencimientos(): array
    {
        $config = Configuracion::actual();
        $ahora = now();
        $resultados = [
            'cortes_notificados' => 0,
            'multas_aplicadas' => 0,
            'puntos_otorgados' => 0,
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
            // 1. CORTE AUTOMÁTICO Y CÁLCULO DE PUNTOS (al llegar o superar fecha_corte)
            // ─────────────────────────────────────────────────────────────
            if ($config->fecha_corte && $ahora->greaterThanOrEqualTo($config->fecha_corte)) {
                foreach ($distribuidoras as $dist) {
                    // REGLA 1: Solo procesar cortes ocurridos después de la fecha de alta del distribuidor
                    if ($dist->created_at && $dist->created_at->startOfDay()->greaterThan($config->fecha_corte)) {
                        continue;
                    }

                    $totalProductos = floatval(Prestamo::where('created_by_user_id', $dist->id)->where('estado', 'activo')->sum('monto_prestamo'));
                    $totalQuincenal = floatval(Prestamo::where('created_by_user_id', $dist->id)->where('estado', 'activo')->sum('cuota_quincenal'));
                    $multasDistribuidora = floatval($dist->multas ?? 0.0);
                    $total15nalExigible = $totalQuincenal + $multasDistribuidora;

                    $relacion = RelacionCobranza::where('distribuidora_id', $dist->id)
                        ->whereDate('fecha_corte', $config->fecha_corte->toDateString())
                        ->orderBy('fecha_corte', 'desc')
                        ->first();

                    $montoPagado = $relacion ? floatval($relacion->monto_pagado) : 0.0;

                    // Si no hay monto registrado en la relación, calcular pagos directos de préstamos
                    if ($montoPagado <= 0) {
                        $montoPagado = floatval(PagoPrestamo::whereHas('prestamo', fn($q) => $q->where('created_by_user_id', $dist->id))
                            ->where('created_at', '<=', $config->fecha_corte)
                            ->sum('monto_abonado'));
                    }

                    // Sumar pagos aprobados por conciliación cuya fecha de pago fue anterior a la fecha de corte
                    $conciliacionesAnteriores = Conciliacion::where('distribuidora_id', $dist->id)
                        ->whereIn('estado', ['conciliado', 'aprobada'])
                        ->whereNotNull('fecha_pago')
                        ->get();

                    $montoConciliadoAnteriorAlCorte = 0.0;
                    foreach ($conciliacionesAnteriores as $conc) {
                        $fechaPagoConc = Carbon::parse($conc->fecha_pago);
                        if ($fechaPagoConc->startOfDay()->lessThanOrEqualTo($config->fecha_corte->endOfDay())) {
                            $montoConciliadoAnteriorAlCorte += floatval($conc->monto_corregido);
                        }
                    }

                    $totalAbonadoValidoAntesCorte = $montoPagado + $montoConciliadoAnteriorAlCorte;
                    $adeudo15nalPendiente = max(0.0, $total15nalExigible - $totalAbonadoValidoAntesCorte);

                    // CONDICIÓN: Se liquidó el total 15nal antes de la fecha de corte
                    $fueLiquidado15nalAntesCorte = ($total15nalExigible <= 0) || ($totalAbonadoValidoAntesCorte >= $total15nalExigible) || ($adeudo15nalPendiente <= 0);

                    if ($fueLiquidado15nalAntesCorte) {
                        $puntosGanados = 0;
                        $estadoPago = 'pago_a_tiempo';

                        // Si colocó productos y cubrió su cuota quincenal antes del corte -> Pago Anticipado con PUNTOS
                        if ($totalProductos > 0) {
                            $puntosGanados = $config->calcularPuntosPorMonto($totalProductos);
                            $estadoPago = 'pago_anticipado';

                            // Otorgar puntos si no se habían asignado ya en este corte
                            $puntosYaAsignados = $relacion ? intval($relacion->puntos_ganados) : 0;
                            if ($puntosYaAsignados <= 0 && $puntosGanados > 0) {
                                $dist->increment('puntos', $puntosGanados);
                                $resultados['puntos_otorgados'] += $puntosGanados;

                                NotificacionCajero::create([
                                    'user_id' => $dist->id,
                                    'tipo' => 'pago_anticipado',
                                    'titulo' => '🎉 ¡Puntos Otorgados al Corte!',
                                    'mensaje' => "Liquidaste tu cuota quincenal antes del corte. ¡Has acumulado {$puntosGanados} puntos de bonificación por un total en vales de $" . number_format($totalProductos, 2) . "!",
                                    'data' => [
                                        'puntos' => $puntosGanados,
                                        'total_productos' => $totalProductos,
                                        'total_quincenal' => $total15nalExigible,
                                    ],
                                    'leida' => false,
                                ]);
                            } elseif ($puntosYaAsignados > 0) {
                                $puntosGanados = $puntosYaAsignados;
                            }
                        }

                        if ($relacion) {
                            $relacion->update([
                                'fecha_corte' => $config->fecha_corte,
                                'fecha_limite_pago' => $config->fecha_limite_pago,
                                'monto_total_periodo' => $total15nalExigible,
                                'monto_pagado' => max($totalAbonadoValidoAntesCorte, $total15nalExigible),
                                'adeudo_pendiente' => 0.00,
                                'estado_pago' => $estadoPago,
                                'puntos_ganados' => $puntosGanados,
                                'puntos_descontados' => 0,
                                'liquidado_at' => $relacion->liquidado_at ?? $ahora,
                            ]);
                        } else {
                            $relacion = RelacionCobranza::create([
                                'distribuidora_id' => $dist->id,
                                'fecha_corte' => $config->fecha_corte,
                                'fecha_limite_pago' => $config->fecha_limite_pago,
                                'monto_total_periodo' => $total15nalExigible,
                                'monto_pagado' => max($totalAbonadoValidoAntesCorte, $total15nalExigible),
                                'adeudo_pendiente' => 0.00,
                                'estado_pago' => $estadoPago,
                                'puntos_ganados' => $puntosGanados,
                                'puntos_descontados' => 0,
                                'liquidado_at' => $ahora,
                            ]);
                        }
                        continue;
                    }

                    // Si NO se liquidó la cuota quincenal antes del corte: No gana puntos (0 puntos), queda pendiente
                    if ($relacion) {
                        $relacion->update([
                            'fecha_corte' => $config->fecha_corte,
                            'fecha_limite_pago' => $config->fecha_limite_pago,
                            'monto_total_periodo' => $total15nalExigible,
                            'monto_pagado' => $totalAbonadoValidoAntesCorte,
                            'adeudo_pendiente' => $adeudo15nalPendiente,
                            'estado_pago' => 'pendiente',
                            'puntos_ganados' => 0,
                            'puntos_descontados' => 0,
                        ]);
                    } else {
                        $relacion = RelacionCobranza::create([
                            'distribuidora_id' => $dist->id,
                            'fecha_corte' => $config->fecha_corte,
                            'fecha_limite_pago' => $config->fecha_limite_pago,
                            'monto_total_periodo' => $total15nalExigible,
                            'monto_pagado' => $totalAbonadoValidoAntesCorte,
                            'adeudo_pendiente' => $adeudo15nalPendiente,
                            'estado_pago' => 'pendiente',
                            'puntos_ganados' => 0,
                            'puntos_descontados' => 0,
                        ]);
                    }

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
                                'total_quincenal' => $total15nalExigible,
                                'url' => route('prestamos.relacion-pdf', [], false),
                            ],
                            'leida' => false,
                        ]);

                        $relacion->update([
                            'corte_notificado_at' => $ahora,
                            'adeudo_pendiente' => $adeudo15nalPendiente,
                            'monto_total_periodo' => $total15nalExigible,
                            'monto_pagado' => $totalAbonadoValidoAntesCorte,
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
                    // REGLA 1: No aplicar sanciones por cortes previos a su alta
                    if ($dist->created_at && $dist->created_at->startOfDay()->greaterThan($config->fecha_corte)) {
                        continue;
                    }

                    $totalQuincenal = floatval(Prestamo::where('created_by_user_id', $dist->id)->where('estado', 'activo')->sum('cuota_quincenal'));
                    $multasDistribuidora = floatval($dist->multas ?? 0.0);
                    $total15nalExigible = $totalQuincenal + $multasDistribuidora;

                    $relacion = RelacionCobranza::where('distribuidora_id', $dist->id)
                        ->where('fecha_corte', $config->fecha_corte)
                        ->first();

                    $montoPagado = $relacion ? floatval($relacion->monto_pagado) : 0.0;
                    $adeudo15nalPendiente = $relacion ? floatval($relacion->adeudo_pendiente) : max(0.0, $total15nalExigible - $montoPagado);

                    // Si su cuota quincenal está en 0.00, se toma por pagada/liquidada
                    if ($adeudo15nalPendiente <= 0 || ($relacion && $relacion->monto_pagado >= $relacion->monto_total_periodo)) {
                        if ($relacion && $relacion->estado_pago === 'pendiente') {
                            $relacion->update([
                                'monto_total_periodo' => $total15nalExigible,
                                'monto_pagado' => max($montoPagado, $total15nalExigible),
                                'estado_pago' => 'pago_a_tiempo',
                                'adeudo_pendiente' => 0.00,
                                'liquidado_at' => $ahora,
                            ]);
                        }
                        continue;
                    }

                    // REGLA 3: Las multas son por distribuidora y actualizan la relación
                    if ($relacion && !$relacion->multa_aplicada_at && $relacion->estado_pago === 'pendiente') {
                        $montoMulta = floatval($config->multa_adeudo ?? 300.00);

                        // Multa por distribuidora
                        $dist->increment('multas', $montoMulta);

                        $relacion->update([
                            'monto_total_periodo' => $total15nalExigible,
                            'monto_pagado' => $montoPagado,
                            'multa_aplicada' => $montoMulta,
                            'multa_aplicada_at' => $ahora,
                            'adeudo_pendiente' => $adeudo15nalPendiente + $montoMulta,
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
        });

        return $resultados;
    }

    /**
     * Actualiza la relación de cobranza cuando se registra un abono de la distribuidora.
     * IMPORTANTE: No calcula ni otorga puntos en el momento del abono. Los puntos se
     * procesan exclusivamente al momento del corte oficial.
     */
    public function actualizarRelacionPorAbono(User $distribuidora, float $montoAbonado = 0.0): ?RelacionCobranza
    {
        if (!$distribuidora->esDistribuidor()) {
            return null;
        }

        $config = Configuracion::actual();
        $ahora = now();

        $totalQuincenal = floatval(Prestamo::where('created_by_user_id', $distribuidora->id)
            ->where('estado', 'activo')
            ->sum('cuota_quincenal'));

        $multasRestantes = floatval($distribuidora->multas ?? 0.0);
        $total15nalExigible = $totalQuincenal + $multasRestantes;

        $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->orderBy('fecha_corte', 'desc')
            ->first();

        if (!$relacion) {
            $montoPagado = $montoAbonado;
            $adeudoPendiente15nal = max(0.0, $total15nalExigible - $montoPagado);

            $relacion = RelacionCobranza::create([
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => $config->fecha_corte,
                'fecha_limite_pago' => $config->fecha_limite_pago,
                'monto_total_periodo' => $total15nalExigible,
                'monto_pagado' => $montoPagado,
                'adeudo_pendiente' => $adeudoPendiente15nal,
                'estado_pago' => $adeudoPendiente15nal <= 0 ? 'pago_a_tiempo' : 'pendiente',
                'puntos_ganados' => 0,
                'puntos_descontados' => 0,
                'liquidado_at' => $adeudoPendiente15nal <= 0 ? $ahora : null,
            ]);
        } else {
            $relacion->monto_total_periodo = $total15nalExigible;
            $relacion->monto_pagado = floatval($relacion->monto_pagado) + $montoAbonado;
            $adeudoPendiente15nal = max(0.0, $total15nalExigible - floatval($relacion->monto_pagado));
            $relacion->adeudo_pendiente = $adeudoPendiente15nal;

            if ($adeudoPendiente15nal <= 0 && $relacion->estado_pago === 'pendiente') {
                $relacion->liquidado_at = $ahora;
            }
            $relacion->save();
        }

        return $relacion;
    }
}
