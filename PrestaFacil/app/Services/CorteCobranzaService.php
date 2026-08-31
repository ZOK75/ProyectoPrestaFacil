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
            ->whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']))
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

                    $totalProductos = floatval(Prestamo::where('created_by_user_id', $dist->id)
                        ->where(function($q) {
                            $q->where('estado', 'activo')
                              ->orWhere(function($q2) {
                                  $q2->where('estado', 'finalizado')
                                     ->where('updated_at', '>=', now()->subDays(30));
                              });
                        })
                        ->sum('monto_prestamo'));
                    $totalQuincenal = $dist->totalCuotaQuincenalNeta();
                    $multasDistribuidora = floatval($dist->multas ?? 0.0);
                    $total15nalExigible = $totalQuincenal + $multasDistribuidora;

                    $relacion = RelacionCobranza::where('distribuidora_id', $dist->id)
                        ->whereDate('fecha_corte', $config->fecha_corte->toDateString())
                        ->first();

                    $montoPagado = $relacion ? floatval($relacion->monto_pagado) : 0.0;

                    // Sumar pagos aprobados por conciliación cuya fecha de pago fue anterior o igual a la fecha de corte
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

                    $totalQuincenal = $dist->totalCuotaQuincenalNeta();
                    $multasDistribuidora = floatval($dist->multas ?? 0.0);
                    $total15nalExigible = $totalQuincenal + $multasDistribuidora;

                    $relacion = RelacionCobranza::where('distribuidora_id', $dist->id)
                        ->whereDate('fecha_corte', $config->fecha_corte->toDateString())
                        ->orderBy('fecha_corte', 'desc')
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

                    // REGLA: Las multas se calculan y aplican por cada vale individual
                    if ($relacion && !$relacion->multa_aplicada_at && $relacion->estado_pago === 'pendiente') {
                        $prestamosActivos = Prestamo::where('created_by_user_id', $dist->id)
                            ->where('estado', 'activo')
                            ->where('adeudo_pendiente', '>', 0)
                            ->where(function ($q) use ($config) {
                                $q->where('created_at', '<=', $config->fecha_corte->copy()->endOfDay())
                                  ->orWhere('entregado_at', '<=', $config->fecha_corte->copy()->endOfDay());
                            })
                            ->with('productoVale')
                            ->get();

                        $porcentajeComision = floatval($dist->obtenerPorcentajeGanancia() ?? 0.0);
                        $multaTotalPeriodo = 0.0;
                        foreach ($prestamosActivos as $prestamo) {
                            $totalQuincenas = max(1, intval($prestamo->pagos_totales ?: ($prestamo->productoVale?->plazo_quincenas ?: 8)));
                            $cuotaBruta = floatval($prestamo->cuota_quincenal ?: ($prestamo->monto_prestamo / $totalQuincenas));
                            $comision = (($porcentajeComision / 100) * floatval($prestamo->monto_prestamo)) / $totalQuincenas;
                            $cuotaNeta = $cuotaBruta - $comision;

                            $fechaInicioPrestamo = $prestamo->entregado_at ?? $prestamo->created_at;
                            $cortesPrevios = 0;
                            if ($fechaInicioPrestamo) {
                                $cortesPrevios = RelacionCobranza::where('distribuidora_id', $dist->id)
                                    ->when($relacion?->id, function ($q) use ($relacion) {
                                        $q->where('id', '!=', $relacion->id);
                                    })
                                    ->whereNotNull('fecha_corte')
                                    ->where('fecha_corte', '<=', $ahora)
                                    ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                                    ->count();
                            }
                            $cortesHastaEste = $cortesPrevios + 1;

                            $totalAbonadoAlVale = floatval($prestamo->pagos()->sum('monto_abonado'));
                            $montoEsperadoAlCorriente = $cortesHastaEste * $cuotaNeta;

                            $estaAlCorrienteEsteVale = $prestamo->estaPagado() || (floor($totalAbonadoAlVale) >= floor($montoEsperadoAlCorriente)) || ($totalAbonadoAlVale >= ($montoEsperadoAlCorriente - 0.99));

                            if (!$estaAlCorrienteEsteVale) {
                                $multaVale = $prestamo->multaConfigurada();
                                if ($multaVale > 0) {
                                    $prestamo->increment('multas', $multaVale);
                                    $multaTotalPeriodo += $multaVale;
                                }
                            }
                        }

                        $relacion->update([
                            'monto_total_periodo' => $total15nalExigible,
                            'monto_pagado' => $montoPagado,
                            'multa_aplicada' => $multaTotalPeriodo,
                            'multa_aplicada_at' => $ahora,
                            'estado_pago' => ($multaTotalPeriodo > 0 ? 'pago_atrasado' : ($adeudo15nalPendiente <= 0 ? 'pago_a_tiempo' : 'pendiente')),
                            'adeudo_pendiente' => $adeudo15nalPendiente + $multaTotalPeriodo,
                        ]);

                        if ($multaTotalPeriodo > 0) {
                            $dist->increment('multas', $multaTotalPeriodo);
                            $dist->increment('conteo_retrasos');
                            $dist->refresh();

                            if ($dist->conteo_retrasos >= 3 && !$dist->es_morosa) {
                                $this->notificarGerentesTercerRetraso($dist);
                            }

                            NotificacionCajero::create([
                                'user_id' => $dist->id,
                                'tipo' => 'multa_adeudo_aplicada',
                                'titulo' => '⚠️ Multas Aplicadas por Vales Vencidos',
                                'mensaje' => 'La fecha límite de pago (' . $config->fecha_limite_pago->format('d/m/Y H:i') . ') ha vencido con saldo pendiente. Se han aplicado los cargos moratorios correspondientes por cada vale con adeudo (Total multas: $' . number_format($multaTotalPeriodo, 2) . ').',
                                'data' => [
                                    'multa_total' => $multaTotalPeriodo,
                                    'fecha_limite' => $config->fecha_limite_pago->toIso8601String(),
                                ],
                                'leida' => false,
                            ]);

                            $resultados['multas_aplicadas']++;
                        }
                    }
                }

                // AVANCE AUTOMÁTICO DEL CICLO QUINCENAL (+15 días)
                // Una vez superada la fecha límite de pago del periodo, se programa el siguiente corte 15 días después
                $config->avanzarCicloQuincenal();
            }
        });

        return $resultados;
    }

    /**
     * Actualiza la relación de cobranza cuando se registra un abono de la distribuidora.
     * Si se liquida el total antes o al momento del corte, calcula los puntos y los suma al distribuidor.
     */
    public function actualizarRelacionPorAbono(User $distribuidora, float $montoAbonado = 0.0): ?RelacionCobranza
    {
        if (!$distribuidora->esDistribuidor()) {
            return null;
        }

        $config = Configuracion::actual();
        $ahora = now();
        $fechaCorte = $config->fecha_corte ?? $ahora;
        $fechaLimite = $config->fecha_limite_pago ?? $ahora->copy()->addDays(5);

        $totalQuincenal = $distribuidora->totalCuotaQuincenalNeta();

        $multasRestantes = floatval($distribuidora->multas ?? 0.0);
        $total15nalExigible = $totalQuincenal + $multasRestantes;

        $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where(function($q) use ($fechaCorte) {
                $q->whereDate('fecha_corte', $fechaCorte->toDateString())
                  ->orWhereNull('fecha_corte');
            })
            ->first();

        if (!$relacion) {
            $montoPagado = $montoAbonado;
            $estaLiquidado = floor($montoPagado) >= floor($total15nalExigible) || abs($montoPagado - $total15nalExigible) < 0.99;
            $adeudoPendiente15nal = $estaLiquidado ? 0.00 : max(0.0, $total15nalExigible - $montoPagado);

            $relacion = RelacionCobranza::create([
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => $fechaCorte,
                'fecha_limite_pago' => $fechaLimite,
                'monto_total_periodo' => $total15nalExigible,
                'monto_pagado' => $montoPagado,
                'adeudo_pendiente' => $adeudoPendiente15nal,
                'estado_pago' => $estaLiquidado ? 'pago_anticipado' : 'pendiente',
                'puntos_ganados' => 0,
                'puntos_descontados' => 0,
                'liquidado_at' => $estaLiquidado ? $ahora : null,
            ]);
        } else {
            if (!$relacion->fecha_corte) {
                $relacion->fecha_corte = $fechaCorte;
                $relacion->fecha_limite_pago = $fechaLimite;
            }
            $relacion->monto_total_periodo = $total15nalExigible;
            $relacion->monto_pagado = floatval($relacion->monto_pagado) + $montoAbonado;
            $estaLiquidado = floor(floatval($relacion->monto_pagado)) >= floor($total15nalExigible) || abs(floatval($relacion->monto_pagado) - $total15nalExigible) < 0.99;
            $adeudoPendiente15nal = $estaLiquidado ? 0.00 : max(0.0, $total15nalExigible - floatval($relacion->monto_pagado));
            $relacion->adeudo_pendiente = $adeudoPendiente15nal;

            if ($estaLiquidado && $relacion->estado_pago === 'pendiente') {
                $relacion->estado_pago = 'pago_anticipado';
                $relacion->liquidado_at = $ahora;
            }
            $relacion->save();
        }

        // Si se liquidó por completo el total antes o al corte: CALCULAR Y SUMAR PUNTOS AL DISTRIBUIDOR
        if ($adeudoPendiente15nal <= 0 && intval($relacion->puntos_ganados) <= 0) {
            $totalProductos = floatval(Prestamo::where('created_by_user_id', $distribuidora->id)
                ->where(function($q) {
                    $q->where('estado', 'activo')
                      ->orWhere(function($q2) {
                          $q2->where('estado', 'finalizado')
                             ->where('updated_at', '>=', now()->subDays(30));
                      });
                })
                ->sum('monto_prestamo'));

            if ($totalProductos > 0) {
                $puntosGanados = $config->calcularPuntosPorMonto($totalProductos);
                if ($puntosGanados > 0) {
                    $distribuidora->increment('puntos', $puntosGanados);
                    $distribuidora->refresh();

                    $relacion->update([
                        'puntos_ganados' => $puntosGanados,
                        'estado_pago' => 'pago_anticipado',
                    ]);

                    NotificacionCajero::create([
                        'user_id' => $distribuidora->id,
                        'tipo' => 'pago_anticipado',
                        'titulo' => '🎉 ¡Puntos Ganados por Liquidación!',
                        'mensaje' => "Has liquidado tu cuota quincenal antes o al corte. ¡Se han sumado {$puntosGanados} puntos de bonificación a tu cuenta!",
                        'data' => [
                            'puntos' => $puntosGanados,
                            'total_productos' => $totalProductos,
                            'total_quincenal' => $total15nalExigible,
                        ],
                        'leida' => false,
                    ]);
                }
            }
        }

        // Si la distribuidora liquidó por completo su adeudo exigible y deudas moratorias, se limpia automáticamente el estado de morosidad y retrasos
        if ($distribuidora->totalAdeudoGlobal() <= 0) {
            if ($distribuidora->es_morosa || $distribuidora->conteo_retrasos > 0) {
                $distribuidora->desmarcarMorosidad();
            }
        }

        return $relacion;
    }

    /**
     * Evalúa la liquidación de la relación de cobranza de una distribuidora.
     */
    public function evaluarLiquidacionRelacion(User $distribuidora): ?RelacionCobranza
    {
        return $this->actualizarRelacionPorAbono($distribuidora, 0.0);
    }

    /**
     * Simula el avance forzado de un ciclo quincenal completo:
     * - Aplica y ACUMULA multas moratorias individuales a cada vale activo con adeudo pendiente.
     * - Incrementa las multas acumuladas de la distribuidora en cada ejecución sucesiva.
     * - Genera o actualiza la relación de cobranza con los nuevos saldos acumulados.
     * - Avanza automáticamente la fecha de corte y la fecha límite 15 días (+15d) por cada vez que se invoque.
     */
    public function simularSiguienteCorte(): array
    {
        $config = Configuracion::actual();
        $ahora = now();

        $resultados = [
            'multas_aplicadas' => 0,
            'cortes_procesados' => 0,
        ];

        DB::transaction(function () use ($config, $ahora, &$resultados) {
            $distribuidoras = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']))
                ->where('activo', true)
                ->get();

            foreach ($distribuidoras as $dist) {
                // Préstamos activos de la distribuidora con saldo pendiente que fueron cobrados/entregados PREVIO a este corte
                $prestamosActivos = Prestamo::where('created_by_user_id', $dist->id)
                    ->where('estado', 'activo')
                    ->where('adeudo_pendiente', '>', 0)
                    ->where(function ($q) use ($ahora) {
                        $q->where('entregado_at', '<=', $ahora)
                          ->orWhere(function ($sub) use ($ahora) {
                              $sub->whereNull('entregado_at')
                                  ->where('created_at', '<=', $ahora);
                          });
                    })
                    ->with('productoVale')
                    ->get();

                $totalQuincenal = $dist->totalCuotaQuincenalNeta();

                // 1. Verificar si la distribuidora ya liquidó el corte actual que se está cerrando
                $relacionActual = RelacionCobranza::where('distribuidora_id', $dist->id)
                    ->where(function($q) use ($config, $ahora) {
                        $q->whereNull('fecha_corte')
                          ->orWhere('fecha_corte', '<=', $config->fecha_corte ?? $ahora)
                          ->orWhere('fecha_corte', '<=', $ahora->copy()->endOfDay());
                    })
                    ->orderBy('fecha_corte', 'desc')
                    ->first();

                $fueLiquidadoEsteCorte = ($relacionActual && ($relacionActual->adeudo_pendiente <= 0 || floor(floatval($relacionActual->monto_pagado)) >= floor(floatval($relacionActual->monto_total_periodo)) || abs(floatval($relacionActual->monto_pagado) - floatval($relacionActual->monto_total_periodo)) < 0.99) && in_array($relacionActual->estado_pago, ['pago_anticipado', 'pago_a_tiempo', 'liquidado']));

                $multaTotalEsteCiclo = 0.0;

                // Solo aplicar multas si NO se liquidó este corte
                if (!$fueLiquidadoEsteCorte) {
                    $porcentajeComision = floatval($dist->obtenerPorcentajeGanancia() ?? 0.0);
                    foreach ($prestamosActivos as $prestamo) {
                        $totalQuincenas = max(1, intval($prestamo->pagos_totales ?: ($prestamo->productoVale?->plazo_quincenas ?: 8)));
                        $cuotaBruta = floatval($prestamo->cuota_quincenal ?: ($prestamo->monto_prestamo / $totalQuincenas));
                        $comision = (($porcentajeComision / 100) * floatval($prestamo->monto_prestamo)) / $totalQuincenas;
                        $cuotaNeta = $cuotaBruta - $comision;

                        $fechaInicioPrestamo = $prestamo->entregado_at ?? $prestamo->created_at;
                        $cortesPrevios = 0;
                        if ($fechaInicioPrestamo) {
                            $cortesPrevios = RelacionCobranza::where('distribuidora_id', $dist->id)
                                ->when($relacionActual?->id, function ($q) use ($relacionActual) {
                                    $q->where('id', '!=', $relacionActual->id);
                                })
                                ->whereNotNull('fecha_corte')
                                ->where('fecha_corte', '<=', $ahora)
                                ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                                ->count();
                        }
                        $cortesHastaEste = $cortesPrevios + 1;

                        $totalAbonadoAlVale = floatval($prestamo->pagos()->sum('monto_abonado'));
                        $montoEsperadoAlCorriente = $cortesHastaEste * $cuotaNeta;

                        $estaAlCorrienteEsteVale = $prestamo->estaPagado() || (floor($totalAbonadoAlVale) >= floor($montoEsperadoAlCorriente)) || ($totalAbonadoAlVale >= ($montoEsperadoAlCorriente - 0.99));

                        if (!$estaAlCorrienteEsteVale) {
                            $multaVale = $prestamo->multaConfigurada();
                            if ($multaVale > 0) {
                                $prestamo->increment('multas', $multaVale);
                                $multaTotalEsteCiclo += $multaVale;
                                $resultados['multas_aplicadas']++;
                            }
                        }
                    }

                    if ($multaTotalEsteCiclo > 0) {
                        $dist->increment('multas', $multaTotalEsteCiclo);
                        $dist->increment('conteo_retrasos');
                        $dist->refresh();

                        // Verificar si alcanzó 3 retrasos para alertar a los gerentes
                        if ($dist->conteo_retrasos >= 3 && !$dist->es_morosa) {
                            $this->notificarGerentesTercerRetraso($dist);
                        }
                    }
                }

                $dist->refresh();
                $multasAcumuladasDistribuidora = floatval($dist->multas ?? 0.0);
                $total15nalExigible = $totalQuincenal + $multasAcumuladasDistribuidora;

                // 2. Registrar o actualizar la relación de cobranza para este corte con fecha, hora, minuto y segundo exactos
                if (!$fueLiquidadoEsteCorte) {
                    $datosCorte = [
                        'fecha_corte' => $ahora,
                        'fecha_limite_pago' => $config->fecha_limite_pago ?? $ahora->copy()->addDays(5),
                        'monto_total_periodo' => $total15nalExigible,
                        'monto_pagado' => $relacionActual ? floatval($relacionActual->monto_pagado) : 0.00,
                        'adeudo_pendiente' => max(0.0, $total15nalExigible - ($relacionActual ? floatval($relacionActual->monto_pagado) : 0.00)),
                        'multa_aplicada' => $multaTotalEsteCiclo,
                        'multa_aplicada_at' => $ahora,
                        'estado_pago' => ($relacionActual && $relacionActual->monto_pagado > 0) ? 'pendiente' : 'pago_atrasado',
                        'puntos_ganados' => 0,
                        'puntos_descontados' => 0,
                        'corte_notificado_at' => $ahora,
                    ];

                    if ($relacionActual) {
                        $relacionActual->update($datosCorte);
                    } else {
                        RelacionCobranza::create(array_merge(['distribuidora_id' => $dist->id], $datosCorte));
                    }

                    if ($multaTotalEsteCiclo > 0) {
                        NotificacionCajero::create([
                            'user_id' => $dist->id,
                            'tipo' => 'multa_adeudo_aplicada',
                            'titulo' => '⚠️ Multas Aplicadas al Corte (' . $ahora->format('d/m/Y H:i:s') . ')',
                            'mensaje' => 'Se ha procesado el corte oficial con fecha y hora ' . $ahora->format('d/m/Y H:i:s') . '. Se aplicaron cargos moratorios de $' . number_format($multaTotalEsteCiclo, 2) . ' a los vales cobrados previos al corte con adeudo pendiente.',
                            'data' => [
                                'multa_ciclo' => $multaTotalEsteCiclo,
                                'multas_acumuladas' => $multasAcumuladasDistribuidora,
                                'total_adeudo_global' => $dist->totalAdeudoGlobal(),
                            ],
                            'leida' => false,
                        ]);
                    }
                } else {
                    if ($relacionActual) {
                        $relacionActual->update([
                            'fecha_corte' => $ahora,
                            'corte_notificado_at' => $ahora,
                        ]);
                    }
                }

                $resultados['cortes_procesados']++;
            }

            // 3. AVANZAR EL CICLO QUINCENAL (+15 DÍAS A PARTIR DEL CORTE SIMULADO)
            $fechaBase = ($config->fecha_corte && $config->fecha_corte->greaterThan($ahora)) ? $config->fecha_corte : $ahora;
            $nuevaFechaCorte = $fechaBase->copy()->addDays(15);
            $nuevaFechaLimite = $nuevaFechaCorte->copy()->addDays(5);

            $config->update([
                'dia_corte' => $nuevaFechaCorte->day,
                'dia_limite_pago' => $nuevaFechaLimite->day,
                'fecha_corte' => $nuevaFechaCorte,
                'fecha_limite_pago' => $nuevaFechaLimite,
            ]);

            // 4. Inicializar la relación de cobranza limpia para el nuevo ciclo quincenal
            foreach ($distribuidoras as $dist) {
                $multasRestantes = floatval($dist->multas ?? 0.0);
                $total15nalNuevo = $dist->totalCuotaQuincenalNeta() + $multasRestantes;

                RelacionCobranza::updateOrCreate(
                    [
                        'distribuidora_id' => $dist->id,
                        'fecha_corte' => $nuevaFechaCorte,
                    ],
                    [
                        'fecha_limite_pago' => $nuevaFechaLimite,
                        'monto_total_periodo' => $total15nalNuevo,
                        'monto_pagado' => 0.00,
                        'adeudo_pendiente' => $total15nalNuevo,
                        'multa_aplicada' => 0.00,
                        'multa_aplicada_at' => null,
                        'estado_pago' => 'pendiente',
                        'puntos_ganados' => 0,
                        'puntos_descontados' => 0,
                        'corte_notificado_at' => $ahora,
                    ]
                );
            }
        });

        return $resultados;
    }

    /**
     * Notifica a la Gerencia General y al Gerente de Sucursal cuando una distribuidora
     * acumula 3 retrasos en sus cortes de cobranza para evaluar su morosidad.
     */
    public function notificarGerentesTercerRetraso(User $distribuidora): void
    {
        $titulo = "🚨 Alerta: 3er Retraso de Pago - Distribuidora {$distribuidora->name}";
        $mensaje = "La distribuidora {$distribuidora->name} ha acumulado {$distribuidora->conteo_retrasos} retrasos consecutivos en sus cortes de cobranza. Como Gerente, evalúa la situación y decide si marcarla como Morosa para suspender la colocación de vales.";

        $data = [
            'tipo_alerta' => 'tercer_retraso_morosidad',
            'distribuidora_id' => $distribuidora->id,
            'distribuidora_nombre' => $distribuidora->name,
            'conteo_retrasos' => $distribuidora->conteo_retrasos,
            'total_adeudo' => $distribuidora->totalAdeudoGlobal(),
        ];

        // 1. Notificar a todos los Gerentes Generales y Administradores
        $gerentesGenerales = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Gerente General', 'Administrador']))
            ->where('activo', true)
            ->get();

        foreach ($gerentesGenerales as $gg) {
            NotificacionCajero::enviar(
                $gg->id,
                'alerta_morosidad_3er_retraso',
                $titulo,
                $mensaje,
                array_merge($data, ['url' => route('gerente-general.dashboard', [], false)])
            );
        }

        // 2. Notificar al Gerente de Sucursal de la distribuidora
        if ($distribuidora->sucursal_id) {
            $gerentesSucursal = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
                ->where('sucursal_id', $distribuidora->sucursal_id)
                ->where('activo', true)
                ->get();

            foreach ($gerentesSucursal as $gs) {
                NotificacionCajero::enviar(
                    $gs->id,
                    'alerta_morosidad_3er_retraso',
                    $titulo,
                    $mensaje,
                    array_merge($data, ['url' => route('gerente-sucursal.dashboard', [], false)])
                );
            }
        }
    }

    /**
     * Genera las filas de la Relación de Cobranza agrupadas en orden por cliente,
     * mostrando la progresión quincenal (1/N a N/N), comisiones, recargos y saldos.
     *
     * @param User $distribuidora
     * @param RelacionCobranza|null $relacion
     * @param Configuracion|null $configuracion
     * @return array
     */
    public function generarFilasRelacionCobranza(User $distribuidora, ?RelacionCobranza $relacion = null, ?Configuracion $configuracion = null): array
    {
        $config = $configuracion ?? Configuracion::actual();
        $porcentajeComision = $distribuidora->obtenerPorcentajeGanancia();

        if (!$relacion) {
            $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
                ->orderBy('fecha_corte', 'desc')
                ->first();
        }

        $fechaCorteRef = $relacion?->fecha_corte ?? $config->fecha_corte ?? now();

        // 1. Obtener todos los préstamos de la distribuidora activos (entregados/cobrados en caja)
        $prestamos = Prestamo::with(['cliente', 'productoVale', 'pagos'])
            ->where('created_by_user_id', $distribuidora->id)
            ->where('estado', 'activo')
            ->get();

        // Ordenar alfabéticamente por cliente
        $prestamos = $prestamos->sortBy(function ($p) {
            return strtolower($p->cliente?->nombre_completo ?? $p->cliente?->nombre ?? '');
        })->values();

        $filas = [];
        $contadorFila = 1;

        // Determinar número correlativo de cortes transcurridos para esta distribuidora
        $totalCortesPrevios = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('fecha_corte', '<=', $fechaCorteRef->copy()->endOfDay())
            ->count();
        if ($totalCortesPrevios <= 0) {
            $totalCortesPrevios = 1;
        }

        $montoAbonadoPeriodo = floatval($relacion ? $relacion->monto_pagado : 0.0);
        $tieneMultasDistribuidora = (floatval($distribuidora->multas ?? 0.0) > 0);

        foreach ($prestamos as $p) {
            // Cuando un pago se liquide se eliminará de la relación
            if ($p->estaPagado() && floatval($p->adeudo_pendiente) <= 0) {
                continue;
            }

            $totalQuincenas = max(1, intval($p->pagos_totales ?: ($p->productoVale?->plazo_quincenas ?: 8)));
            $cuotaBruta = floatval($p->cuota_quincenal ?: ($p->monto_prestamo / $totalQuincenas));
            $comision = (($porcentajeComision / 100) * floatval($p->monto_prestamo)) / $totalQuincenas;
            $cuotaNeta = $cuotaBruta - $comision;

            $multaVale = $p->multaConfigurada();
            $multasAcumuladas = floatval($p->multas ?? 0.0);
            $tieneRetraso = ($multasAcumuladas > 0);

            // Cortes transcurridos para este préstamo específico (solo cortes cerrados posteriores a su entrega/activación + el corte activo)
            $fechaInicioPrestamo = $p->entregado_at ?? $p->created_at;
            $relacionActualId = $relacion?->id;
            $relacionesCerradas = collect();
            if ($fechaInicioPrestamo) {
                $relacionesCerradas = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
                    ->when($relacionActualId, function ($q) use ($relacionActualId) {
                        $q->where('id', '!=', $relacionActualId);
                    })
                    ->whereNotNull('fecha_corte')
                    ->where('fecha_corte', '<=', $fechaCorteRef)
                    ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                    ->orderBy('fecha_corte', 'asc')
                    ->get();
            }

            $cortesCerradosPosteriores = $relacionesCerradas->count();
            $cortesTranscurridos = $cortesCerradosPosteriores + 1;

            $pagosRealizados = intval($p->pagos_realizados);
            $adeudoArrastre = 0.0;
            $contadorFilaCliente = 1;
            $pagosPrestamo = $p->pagos->sortBy('created_at');
            $abonosConsumidos = 0.0;

            for ($corteNum = 1; $corteNum <= $cortesTranscurridos; $corteNum++) {
                $esExcedidoPlazo = ($corteNum > $totalQuincenas);
                $pagoDisplayNum = min($corteNum, $totalQuincenas);
                $numeroPagoTexto = "{$pagoDisplayNum}/{$totalQuincenas}";

                $cuotaBrutaFila = $esExcedidoPlazo ? 0.00 : $cuotaBruta;
                $comisionFila = $esExcedidoPlazo ? 0.00 : $comision;
                $cuotaNetaFila = $esExcedidoPlazo ? 0.00 : $cuotaNeta;

                $totalFila = 0.00;
                $abonoEsteCorte = 0.00;
                $recargosFila = 0.00;

                if ($corteNum < $cortesTranscurridos) {
                    // CORTES HISTÓRICOS CERRADOS PREVIOS
                    $relCerrada = $relacionesCerradas[$corteNum - 1] ?? null;
                    $fechaCortePasada = $relCerrada ? $relCerrada->fecha_corte : null;

                    // Pagos realizados durante o asociados a este corte histórico
                    $pagosHistoricos = $pagosPrestamo->filter(function($pago) use ($corteNum) {
                        return intval($pago->numero_quincena) === $corteNum;
                    });
                    if ($pagosHistoricos->isEmpty()) {
                        $pagosHistoricos = $pagosPrestamo->filter(function($pago) use ($fechaCortePasada, $corteNum, $cuotaBrutaFila) {
                            $sinQuincena = (intval($pago->numero_quincena) === 0 || $pago->numero_quincena === null);
                            $coincideFecha = $fechaCortePasada && $pago->created_at <= $fechaCortePasada->copy()->endOfDay();
                            return $sinQuincena && $coincideFecha;
                        });
                    }

                    $abonoEsteCorte = floatval($pagosHistoricos->sum('monto_abonado'));
                    $abonosConsumidos += $abonoEsteCorte;

                    if ($corteNum > 1 && $adeudoArrastre > 0) {
                        $recargosFila = ($multasAcumuladas > 0) ? min($multasAcumuladas, $multaVale) : $multaVale;
                        if ($recargosFila <= 0) {
                            $recargosFila = 300.00;
                        }
                    }

                    if ($corteNum === 1) {
                        $pagoIgualado = ($abonoEsteCorte > 0 && $cuotaNetaFila > 0) && (floor($abonoEsteCorte) === floor($cuotaNetaFila) || abs($abonoEsteCorte - $cuotaNetaFila) < 0.99);
                        if ($pagoIgualado) {
                            $totalFila = $cuotaNetaFila;
                            $adeudoArrastre = 0.00;
                        } elseif (floor($abonoEsteCorte) > floor($cuotaNetaFila) && $cuotaNetaFila > 0) {
                            $excedente = floor($abonoEsteCorte) - floor($cuotaNetaFila);
                            $totalFila = -$excedente;
                            $adeudoArrastre = -$excedente;
                        } elseif ($abonoEsteCorte > 0) {
                            $faltante = $cuotaBrutaFila - $abonoEsteCorte;
                            $totalFila = $cuotaBrutaFila;
                            $adeudoArrastre = $faltante;
                        } else {
                            $totalFila = $cuotaBrutaFila;
                            $adeudoArrastre = $cuotaBrutaFila;
                        }
                    } else {
                        $exigibleCorte = $adeudoArrastre + $cuotaNetaFila + $recargosFila;
                        $pagoIgualado = ($abonoEsteCorte > 0 && $exigibleCorte > 0) && (floor($abonoEsteCorte) === floor($exigibleCorte) || abs($abonoEsteCorte - $exigibleCorte) < 0.99);
                        if ($pagoIgualado) {
                            $totalFila = ($adeudoArrastre == 0) ? $cuotaNetaFila : 0.00;
                            $adeudoArrastre = 0.00;
                        } elseif (floor($abonoEsteCorte) > floor($exigibleCorte) && $exigibleCorte > 0) {
                            $excedente = floor($abonoEsteCorte) - floor($exigibleCorte);
                            $totalFila = -$excedente;
                            $adeudoArrastre = -$excedente;
                        } elseif ($abonoEsteCorte > 0) {
                            $faltante = max(0.0, round($exigibleCorte - $abonoEsteCorte, 2));
                            $totalFila = $faltante;
                            $adeudoArrastre = $faltante + $comisionFila;
                        } else {
                            $totalFila = $exigibleCorte;
                            // Al vencerse sin pagar, se pierde el beneficio de comision en el arrastre
                            $adeudoArrastre = $exigibleCorte + $comisionFila;
                        }
                    }
                } else {
                    // CORTE ACTIVO ACTUAL
                    // Todos los abonos disponibles no consumidos en cortes históricos previos se aplican en este corte
                    $totalAbonadoAlPrestamo = floatval($pagosPrestamo->sum('monto_abonado'));
                    $abonoEsteCorte = max(0.0, $totalAbonadoAlPrestamo - $abonosConsumidos);

                    if ($corteNum > 1 && $adeudoArrastre > 0) {
                        $recargosFila = ($multasAcumuladas > 0) ? min($multasAcumuladas, $multaVale) : $multaVale;
                        if ($recargosFila <= 0) {
                            $recargosFila = 300.00;
                        }
                    }

                    if ($corteNum === 1) {
                        $exigibleCorte = $cuotaNetaFila;
                    } else {
                        $exigibleCorte = $adeudoArrastre + $cuotaNetaFila + $recargosFila;
                    }

                    $pagoIgualado = ($abonoEsteCorte > 0 && $exigibleCorte > 0) && (floor($abonoEsteCorte) === floor($exigibleCorte) || abs($abonoEsteCorte - $exigibleCorte) < 0.99);

                    if ($pagoIgualado) {
                        $totalFila = 0.00;
                        $adeudoArrastre = 0.00;
                    } elseif (floor($abonoEsteCorte) > floor($exigibleCorte) && $exigibleCorte > 0) {
                        $excedente = floor($abonoEsteCorte) - floor($exigibleCorte);
                        $totalFila = -$excedente;
                        $adeudoArrastre = -$excedente;
                    } elseif ($abonoEsteCorte > 0) {
                        $diferencia = round($exigibleCorte - $abonoEsteCorte, 2);
                        $totalFila = $diferencia;
                        $adeudoArrastre = $diferencia;
                    } else {
                        $totalFila = $exigibleCorte;
                        $adeudoArrastre = $exigibleCorte;
                    }
                }

                $totalFila = round($totalFila, 2);

                // Agregar fila al reporte con numeración por cliente
                $filas[] = [
                    'numero' => $contadorFilaCliente++,
                    'prestamo_id' => $p->id,
                    'referencia' => $p->referencia,
                    'producto' => $p->productoVale?->nombre ?: ($p->productoVale?->clave ?: 'Vale 1'),
                    'cliente' => $p->cliente?->nombre_completo ?: ($p->cliente?->nombre ?: 'Sin Cliente'),
                    'numero_pago' => $numeroPagoTexto,
                    'corte_num' => $corteNum,
                    'total_quincenas' => $totalQuincenas,
                    'comision' => $comisionFila,
                    'pago' => $cuotaBrutaFila,
                    'cuota_neta' => $cuotaNetaFila,
                    'recargos' => $recargosFila,
                    'abono' => $abonoEsteCorte,
                    'total' => $totalFila,
                ];
            }
        }

        return $filas;
    }
}
