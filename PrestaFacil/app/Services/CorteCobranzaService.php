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

                    $montoPagadoEnRelacion = $relacion ? floatval($relacion->monto_pagado) : 0.0;

                    // Sumar pagos directos en ventanilla previos o iguales a la fecha de corte
                    $prestamosDistIds = Prestamo::where('created_by_user_id', $dist->id)->pluck('id');
                    $montoPagadoDirecto = floatval(PagoPrestamo::whereIn('prestamo_id', $prestamosDistIds)->where('created_at', '<=', $config->fecha_corte->endOfDay())->sum('monto_abonado'));
                    $montoPagado = max($montoPagadoEnRelacion, $montoPagadoDirecto);

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

        $totalQuincenal = $distribuidora->totalCuotaQuincenalNeta();

        $multasRestantes = floatval($distribuidora->multas ?? 0.0);
        $total15nalExigible = $totalQuincenal + $multasRestantes;

        $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->latest('fecha_corte')
            ->first();

        if ($relacion) {
            $relacion->monto_total_periodo = $total15nalExigible;
            $relacion->monto_pagado = floatval($relacion->monto_pagado) + $montoAbonado;
            $estaLiquidado = floor(floatval($relacion->monto_pagado)) >= floor($total15nalExigible) || abs(floatval($relacion->monto_pagado) - $total15nalExigible) < 0.99;
            $adeudoPendiente15nal = $estaLiquidado ? 0.00 : max(0.0, $total15nalExigible - floatval($relacion->monto_pagado));
            $relacion->adeudo_pendiente = $adeudoPendiente15nal;

            $esPagoAntesDelCorte = false;
            if ($relacion->fecha_corte) {
                $esPagoAntesDelCorte = $ahora->lessThanOrEqualTo($relacion->fecha_corte);
            } elseif ($config->fecha_corte) {
                $esPagoAntesDelCorte = $ahora->lessThanOrEqualTo($config->fecha_corte);
            }

            $tieneMultas = floatval($distribuidora->multas ?? 0.0) > 0 || floatval($relacion->multa_aplicada ?? 0.0) > 0;

            if ($estaLiquidado) {
                $relacion->estado_pago = ($esPagoAntesDelCorte && !$tieneMultas) ? 'pago_anticipado' : 'pago_a_tiempo';
                $relacion->liquidado_at = $ahora;
            }
            $relacion->save();
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

        $fechaCorteProcesada = $ahora;
        $fechaLimiteProcesada = $config->fecha_limite_pago ?? $ahora->copy()->addDays(5);

        $resultados = [
            'multas_aplicadas' => 0,
            'cortes_procesados' => 0,
            'puntos_otorgados' => 0,
            'fecha_corte_procesada' => $fechaCorteProcesada,
            'fecha_limite_procesada' => $fechaLimiteProcesada,
            'proxima_fecha_corte' => null,
            'proxima_fecha_limite' => null,
        ];

        DB::transaction(function () use ($config, $ahora, $fechaCorteProcesada, $fechaLimiteProcesada, &$resultados) {
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

                // 1. Verificar si la distribuidora ya liquidó el corte previo que se está cerrando
                $relacionPrevia = RelacionCobranza::where('distribuidora_id', $dist->id)
                    ->whereNotNull('fecha_corte')
                    ->where('fecha_corte', '<=', $ahora)
                    ->latest('fecha_corte')
                    ->first();

                $fueLiquidadoCortePrevio = ($relacionPrevia && ($relacionPrevia->adeudo_pendiente <= 0 || floor(floatval($relacionPrevia->monto_pagado)) >= floor(floatval($relacionPrevia->monto_total_periodo)) || abs(floatval($relacionPrevia->monto_pagado) - floatval($relacionPrevia->monto_total_periodo)) < 0.99));

                $multaTotalEsteCiclo = 0.0;

                // Solo aplicar multas si NO se liquidó este corte
                if (!$fueLiquidadoCortePrevio) {
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
                                ->whereNotNull('fecha_corte')
                                ->where('fecha_corte', '<=', $ahora)
                                ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                                ->count();
                        }

                        // Solo aplicar multas si el préstamo ya cuenta con al menos un corte previo transcurrido sin liquidar
                        if ($cortesPrevios >= 1) {
                            $totalAbonadoAlVale = floatval($prestamo->pagos()->sum('monto_abonado'));
                            $montoEsperadoAlCorriente = $cortesPrevios * $cuotaNeta;

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

                // Otorgar puntos si la relación previa se liquidó como pago anticipado y sin multas
                if ($relacionPrevia && intval($relacionPrevia->puntos_ganados) <= 0 && $relacionPrevia->adeudo_pendiente <= 0 && $relacionPrevia->estado_pago === 'pago_anticipado' && $multasAcumuladasDistribuidora <= 0) {
                    $totalProductos = floatval(Prestamo::where('created_by_user_id', $dist->id)
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
                            $dist->increment('puntos', $puntosGanados);
                            $dist->refresh();
                            $resultados['puntos_otorgados'] += $puntosGanados;

                            $relacionPrevia->update([
                                'puntos_ganados' => $puntosGanados,
                            ]);

                            NotificacionCajero::create([
                                'user_id' => $dist->id,
                                'tipo' => 'pago_anticipado',
                                'titulo' => '🎉 ¡Puntos Otorgados al Corte!',
                                'mensaje' => "Liquidaste tu cuota quincenal antes del corte. ¡Has acumulado {$puntosGanados} puntos de bonificación por un total en vales de $" . number_format($totalProductos, 2) . "!",
                                'data' => [
                                    'puntos' => $puntosGanados,
                                    'total_productos' => $totalProductos,
                                    'total_quincenal' => $relacionPrevia->monto_total_periodo,
                                ],
                                'leida' => false,
                            ]);
                        }
                    }
                }

                // Obtener todos los préstamos de la distribuidora
                $prestamosDist = Prestamo::where('created_by_user_id', $dist->id)->get();
                $prestamosIds = $prestamosDist->pluck('id');

                // Total abonado a los préstamos de la distribuidora hasta este momento
                $totalAbonadoHastaAhora = floatval(PagoPrestamo::whereIn('prestamo_id', $prestamosIds)->where('created_at', '<=', $ahora)->sum('monto_abonado'));

                // Total consumido en cortes previos
                $cortesPreviosRecords = RelacionCobranza::where('distribuidora_id', $dist->id)->whereNotNull('fecha_corte')->where('fecha_corte', '<', $ahora)->get();
                $totalPagadoEnCortesPrevios = floatval($cortesPreviosRecords->sum('monto_pagado'));

                $abonoDisponibleEsteCorte = max(0.0, $totalAbonadoHastaAhora - $totalPagadoEnCortesPrevios);
                $estaCubiertoEsteCorte = ($total15nalExigible <= 0) || ($abonoDisponibleEsteCorte >= ($total15nalExigible - 0.99)) || (floor($abonoDisponibleEsteCorte) >= floor($total15nalExigible));
                $montoPagadoEsteCorte = min($total15nalExigible, $abonoDisponibleEsteCorte);
                $adeudoPendienteEsteCorte = $estaCubiertoEsteCorte ? 0.00 : max(0.0, $total15nalExigible - $montoPagadoEsteCorte);

                $puntosGanadosEsteCorte = 0;
                $tieneMultasDistribuidora = ($multasAcumuladasDistribuidora > 0);

                // Si es el primer corte y se pagó anticipadamente (o abono previo para este corte sin relación previa)
                if (!$relacionPrevia && $estaCubiertoEsteCorte && !$tieneMultasDistribuidora && $montoPagadoEsteCorte > 0) {
                    $totalProductos = floatval(Prestamo::where('created_by_user_id', $dist->id)
                        ->where(function($q) {
                            $q->where('estado', 'activo')
                              ->orWhere(function($q2) {
                                  $q2->where('estado', 'finalizado')
                                     ->where('updated_at', '>=', now()->subDays(30));
                              });
                        })
                        ->sum('monto_prestamo'));

                    if ($totalProductos > 0) {
                        $puntosGanadosEsteCorte = $config->calcularPuntosPorMonto($totalProductos);
                        $dist->increment('puntos', $puntosGanadosEsteCorte);
                        $dist->refresh();
                        $resultados['puntos_otorgados'] += $puntosGanadosEsteCorte;

                        NotificacionCajero::create([
                            'user_id' => $dist->id,
                            'tipo' => 'pago_anticipado',
                            'titulo' => '🎉 ¡Puntos Otorgados al Corte!',
                            'mensaje' => "Liquidaste tu cuota quincenal antes del corte. ¡Has acumulado {$puntosGanadosEsteCorte} puntos de bonificación por un total en vales de $" . number_format($totalProductos, 2) . "!",
                            'data' => [
                                'puntos' => $puntosGanadosEsteCorte,
                                'total_productos' => $totalProductos,
                                'total_quincenal' => $total15nalExigible,
                            ],
                            'leida' => false,
                        ]);
                    }
                }

                // 2. Registrar siempre la nueva relación de cobranza para este corte
                $datosCorte = [
                    'distribuidora_id' => $dist->id,
                    'fecha_corte' => $fechaCorteProcesada,
                    'fecha_limite_pago' => $fechaLimiteProcesada,
                    'monto_total_periodo' => $total15nalExigible,
                    'monto_pagado' => $montoPagadoEsteCorte,
                    'adeudo_pendiente' => $adeudoPendienteEsteCorte,
                    'multa_aplicada' => $multaTotalEsteCiclo,
                    'multa_aplicada_at' => ($multaTotalEsteCiclo > 0 ? $ahora : null),
                    'estado_pago' => ($multaTotalEsteCiclo > 0 ? 'pago_atrasado' : ($estaCubiertoEsteCorte ? 'pago_anticipado' : 'pendiente')),
                    'puntos_ganados' => $puntosGanadosEsteCorte,
                    'puntos_descontados' => 0,
                    'liquidado_at' => $estaCubiertoEsteCorte ? $ahora : null,
                    'corte_notificado_at' => $ahora,
                ];

                RelacionCobranza::create($datosCorte);

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

                $resultados['cortes_procesados']++;
            }

            // 3. AVANZAR EL CICLO QUINCENAL (+15 DÍAS A PARTIR DEL CORTE SIMULADO)
            $fechaBase = $ahora;
            $nuevaFechaCorte = $fechaBase->copy()->addDays(15);
            $nuevaFechaLimite = $nuevaFechaCorte->copy()->addDays(5);

            $config->update([
                'dia_corte' => $nuevaFechaCorte->day,
                'dia_limite_pago' => $nuevaFechaLimite->day,
                'fecha_corte' => $nuevaFechaCorte,
                'fecha_limite_pago' => $nuevaFechaLimite,
            ]);

            $resultados['proxima_fecha_corte'] = $nuevaFechaCorte;
            $resultados['proxima_fecha_limite'] = $nuevaFechaLimite;
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
     * Procesa y aplica una conciliación aprobada por la coordinación/gerencia,
     * distribuyendo el pago bancario a los préstamos ligados por su folio y
     * aplicando el efecto retroactivo de limpieza de multas, preservación de comisión
     * y otorgamiento de puntos si la fecha de pago fue previa a un corte ya realizado.
     */
    public function aplicarConciliacionAprobada(Conciliacion $conciliacion, User $autorizador): void
    {
        $fechaPago = $conciliacion->fecha_pago ? \Carbon\Carbon::parse($conciliacion->fecha_pago) : now();
        $config = Configuracion::actual();

        // 1. Obtener los préstamos asignados a la conciliación
        $prestamosAsignados = $conciliacion->prestamos_asignados ?: [];
        if (empty($prestamosAsignados) && $conciliacion->prestamo_id) {
            $prestamosAsignados = [[
                'prestamo_id' => $conciliacion->prestamo_id,
                'folio' => $conciliacion->prestamo?->referencia,
                'monto' => floatval($conciliacion->monto_corregido),
            ]];
        }

        foreach ($prestamosAsignados as $item) {
            $prestamoId = $item['prestamo_id'] ?? null;
            if (!$prestamoId && !empty($item['folio'])) {
                $p = Prestamo::where('referencia', $item['folio'])->first();
                $prestamoId = $p?->id;
            }

            if (!$prestamoId) {
                continue;
            }

            $prestamo = Prestamo::find($prestamoId);
            if (!$prestamo) {
                continue;
            }

            $distribuidora = $prestamo->createdBy ?? ($conciliacion->distribuidora_id ? User::find($conciliacion->distribuidora_id) : null);
            $montoAbonado = floatval($item['monto'] ?? $conciliacion->monto_corregido);
            if ($montoAbonado <= 0) {
                continue;
            }

            // 2. Evaluar efecto retroactivo frente a cortes históricos cerrados
            if ($distribuidora) {
                $relacionesHistoricas = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
                    ->whereNotNull('fecha_corte')
                    ->where('fecha_corte', '>=', $fechaPago->copy()->startOfDay())
                    ->get();

                $porcentajeComision = floatval($distribuidora->obtenerPorcentajeGanancia() ?? 0.0);
                $totalQuincenas = max(1, intval($prestamo->pagos_totales ?: ($prestamo->productoVale?->plazo_quincenas ?: 8)));
                $cuotaBruta = floatval($prestamo->cuota_quincenal ?: ($prestamo->monto_prestamo / $totalQuincenas));
                $comision = (($porcentajeComision / 100) * floatval($prestamo->monto_prestamo)) / $totalQuincenas;
                $cuotaNeta = $cuotaBruta - $comision;

                $cubreCuotaNeta = (floor($montoAbonado) >= floor($cuotaNeta) || abs($montoAbonado - $cuotaNeta) < 0.99);

                foreach ($relacionesHistoricas as $relHistorica) {
                    $fechaLimiteCorte = $relHistorica->fecha_limite_pago ?? $relHistorica->fecha_corte->copy()->addDays(5);
                    $esFechaPreviaAlCorte = $fechaPago->lte($fechaLimiteCorte->copy()->endOfDay());

                    if ($esFechaPreviaAlCorte && $cubreCuotaNeta) {
                        // Revertir multas generadas indebidamente
                        $multaVale = floatval($prestamo->multaConfigurada() ?: 300.00);
                        $multaRevertir = 0.0;
                        if ($prestamo->multas > 0) {
                            $multaRevertir = min(floatval($prestamo->multas), $multaVale);
                            $prestamo->decrement('multas', $multaRevertir);

                            if ($distribuidora->multas > 0) {
                                $distribuidora->decrement('multas', min($multaRevertir, floatval($distribuidora->multas)));
                            }
                            if ($distribuidora->conteo_retrasos > 0) {
                                $distribuidora->decrement('conteo_retrasos');
                            }
                        }

                        // Sellar la relación histórica como pago a tiempo
                        $relHistorica->monto_pagado = floatval($relHistorica->monto_pagado) + $montoAbonado;
                        if ($multaRevertir > 0) {
                            $relHistorica->monto_total_periodo = max(0.0, floatval($relHistorica->monto_total_periodo) - $multaRevertir);
                        }
                        if ($relHistorica->multa_aplicada > 0) {
                            $relHistorica->decrement('multa_aplicada', min(floatval($relHistorica->multa_aplicada), $multaRevertir > 0 ? $multaRevertir : $multaVale));
                        }
                        $relHistorica->adeudo_pendiente = max(0.0, floatval($relHistorica->monto_total_periodo) - floatval($relHistorica->monto_pagado));
                        
                        if (floor($relHistorica->monto_pagado) >= floor($relHistorica->monto_total_periodo) || abs($relHistorica->monto_pagado - $relHistorica->monto_total_periodo) < 0.99) {
                            $relHistorica->adeudo_pendiente = 0.00;
                            $relHistorica->estado_pago = 'pago_a_tiempo';
                            $relHistorica->liquidado_at = $fechaPago;
                        }
                        $relHistorica->save();

                        // Otorgar puntos ganados si no se habían otorgado
                        if (intval($relHistorica->puntos_ganados) <= 0) {
                            $totalProductos = floatval(Prestamo::where('created_by_user_id', $distribuidora->id)
                                ->whereIn('estado', ['activo', 'finalizado'])
                                ->sum('monto_prestamo'));
                            $puntosGanados = $config->calcularPuntosPorMonto($totalProductos);
                            if ($puntosGanados > 0) {
                                $distribuidora->increment('puntos', $puntosGanados);
                                $relHistorica->update(['puntos_ganados' => $puntosGanados]);
                            }
                        }
                    }
                }
            }

            // 3. Crear el PagoPrestamo
            $pago = PagoPrestamo::create([
                'prestamo_id' => $prestamo->id,
                'folio_pago' => 'CONC-' . strtoupper(uniqid()),
                'numero_quincena' => min(intval($prestamo->pagos_totales ?: 8), $prestamo->pagos_realizados + 1),
                'monto_abonado' => $montoAbonado,
                'metodo_pago' => $conciliacion->metodo_pago ?? 'transferencia',
                'observaciones' => "Conciliación #{$conciliacion->id}: " . ($conciliacion->motivo ?? 'Abono conciliado'),
                'registrado_por_user_id' => $conciliacion->solicitante_id ?? $autorizador->id,
                'created_at' => $fechaPago,
                'updated_at' => now(),
            ]);

            // 4. Amortizar préstamo
            $pagoCapital = min($montoAbonado, floatval($prestamo->adeudo_pendiente));
            $prestamo->increment('pagos_recibidos', $pagoCapital);
            $prestamo->decrement('adeudo_pendiente', $pagoCapital);
            $prestamo->increment('pagos_realizados');

            if ($prestamo->adeudo_pendiente <= 0 && ($prestamo->multas ?? 0) <= 0) {
                $prestamo->update(['estado' => 'finalizado']);
            }

            // Si es para el corte actual, actualizar relación activa
            if ($distribuidora) {
                $this->actualizarRelacionPorAbono($distribuidora, 0.0);
            }

            AuditService::registrar('CONCILIACION_APLICADA', "Conciliación #{$conciliacion->id} aplicada a vale {$prestamo->referencia} por \${$montoAbonado}", [
                'conciliacion_id' => $conciliacion->id,
                'prestamo_id' => $prestamo->id,
                'monto' => $montoAbonado,
                'fecha_pago' => $fechaPago->toDateString(),
            ]);
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
                ->where('fecha_corte', '<=', now())
                ->orderBy('fecha_corte', 'desc')
                ->first();
        }

        $esConsultaEspecifica = ($relacion !== null);
        $fechaCorteRef = $relacion?->fecha_corte ?? $config->fecha_corte ?? now();
        $fechaLimiteQuery = $esConsultaEspecifica
            ? ($fechaCorteRef ? $fechaCorteRef->copy()->endOfDay() : now()->copy()->endOfDay())
            : min($fechaCorteRef->copy()->endOfDay(), now()->copy()->endOfDay());

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
            ->where('fecha_corte', '<=', $fechaLimiteQuery)
            ->count();
        if ($totalCortesPrevios <= 0) {
            $totalCortesPrevios = 1;
        }

        $montoAbonadoPeriodo = floatval($relacion ? $relacion->monto_pagado : 0.0);
        $tieneMultasDistribuidora = (floatval($distribuidora->multas ?? 0.0) > 0);

        foreach ($prestamos as $p) {
            $totalQuincenas = max(1, intval($p->pagos_totales ?: ($p->productoVale?->plazo_quincenas ?: 8)));
            $cuotaBruta = floatval($p->cuota_quincenal ?: ($p->monto_prestamo / $totalQuincenas));
            $comision = (($porcentajeComision / 100) * floatval($p->monto_prestamo)) / $totalQuincenas;
            $cuotaNeta = $cuotaBruta - $comision;
            $totalNetoExigiblePrestamo = $totalQuincenas * $cuotaNeta;

            $multaVale = $p->multaConfigurada();
            $multasAcumuladas = floatval($p->multas ?? 0.0);
            $tieneRetraso = ($multasAcumuladas > 0);

            // Cortes transcurridos para este préstamo específico (solo cortes procesados a partir de su entrega)
            $fechaInicioPrestamo = $p->entregado_at ?? $p->created_at;
            $relacionesCerradas = collect();
            if ($fechaInicioPrestamo) {
                $relacionesCerradas = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
                    ->whereNotNull('fecha_corte')
                    ->where('fecha_corte', '<=', $fechaLimiteQuery)
                    ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                    ->orderBy('fecha_corte', 'asc')
                    ->get();
            }

            $cortesTranscurridos = $relacionesCerradas->count();

            $pagosRealizados = intval($p->pagos_realizados);
            $adeudoArrastre = 0.0;
            $contadorFilaCliente = 1;
            $pagosPrestamo = $p->pagos->sortBy('created_at');
            $abonosConsumidos = 0.0;

            $filasEstePrestamo = [];
            $corteDondeSeLiquido = null;

            for ($corteNum = 1; $corteNum <= $cortesTranscurridos; $corteNum++) {
                // Si ya se liquidó en un corte anterior, no generar filas vacías o excedentes posteriores
                if ($corteDondeSeLiquido !== null && $corteNum > $corteDondeSeLiquido) {
                    break;
                }

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
                        $relSiguiente = $relacionesCerradas[$corteNum] ?? null;
                        $fechaLimiteCorte = $relSiguiente ? $relSiguiente->fecha_corte->copy()->endOfDay() : ($fechaCortePasada ? $fechaCortePasada->copy()->addDays(15)->endOfDay() : null);

                        $pagosHistoricos = $pagosPrestamo->filter(function($pago) use ($fechaLimiteCorte, $corteNum, $cuotaBrutaFila) {
                            $sinQuincena = (intval($pago->numero_quincena) === 0 || $pago->numero_quincena === null);
                            $coincideFecha = $fechaLimiteCorte && $pago->created_at <= $fechaLimiteCorte;
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
                        $exigibleCorte = ($adeudoArrastre > 0) ? ($adeudoArrastre + $cuotaNetaFila + $recargosFila) : ($cuotaNetaFila + $recargosFila);
                        $pagoIgualado = ($abonoEsteCorte > 0 && $exigibleCorte > 0) && (floor($abonoEsteCorte) === floor($exigibleCorte) || abs($abonoEsteCorte - $exigibleCorte) < 0.99);
                        if ($pagoIgualado) {
                            $totalFila = ($adeudoArrastre <= 0) ? $cuotaNetaFila : 0.00;
                            $adeudoArrastre = 0.00;
                        } elseif (floor($abonoEsteCorte) > floor($exigibleCorte) && $exigibleCorte > 0) {
                            $excedente = floor($abonoEsteCorte) - floor($exigibleCorte);
                            $totalFila = -$excedente;
                            $adeudoArrastre = -$excedente;
                        } elseif ($abonoEsteCorte > 0) {
                            if ($adeudoArrastre > 0 && $abonoEsteCorte >= ($adeudoArrastre + $recargosFila)) {
                                $excedente = round($abonoEsteCorte - ($adeudoArrastre + $recargosFila), 2);
                                $totalFila = -$excedente;
                                $adeudoArrastre = -$excedente;
                            } else {
                                $faltante = max(0.0, round($exigibleCorte - $abonoEsteCorte, 2));
                                $totalFila = $faltante;
                                $adeudoArrastre = $faltante + $comisionFila;
                            }
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
                    } else {
                        $recargosFila = 0.00;
                    }

                    if ($corteNum === 1) {
                        $exigibleCorte = $cuotaNetaFila;
                    } else {
                        if ($adeudoArrastre > 0) {
                            $exigibleCorte = $adeudoArrastre + $cuotaNetaFila + $recargosFila;
                        } else {
                            // Saldo al corriente o con saldo a favor arrastrado de cortes previos
                            $exigibleCorte = max(0.0, round($cuotaNetaFila + $adeudoArrastre, 2));
                        }
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
                        if ($adeudoArrastre > 0 && $abonoEsteCorte >= ($adeudoArrastre + $recargosFila)) {
                            $excedente = round($abonoEsteCorte - ($adeudoArrastre + $recargosFila), 2);
                            $totalFila = -$excedente;
                            $adeudoArrastre = -$excedente;
                        } else {
                            $diferencia = round($exigibleCorte - $abonoEsteCorte, 2);
                            $totalFila = $diferencia;
                            $adeudoArrastre = $diferencia;
                        }
                    } else {
                        $totalFila = $exigibleCorte;
                        $adeudoArrastre = $exigibleCorte;
                    }
                }

                $totalFila = round($totalFila, 2);

                // Evaluar si en este corte el préstamo queda completamente liquidado en su totalidad
                $totalAbonadoHastaAqui = ($corteNum < $cortesTranscurridos) ? $abonosConsumidos : ($abonosConsumidos + $abonoEsteCorte);
                $esLiquidadoTotal = ($corteNum >= $totalQuincenas && $adeudoArrastre <= 0)
                    || ($totalNetoExigiblePrestamo > 0 && ($totalAbonadoHastaAqui >= ($totalNetoExigiblePrestamo - 0.99) || floor($totalAbonadoHastaAqui) >= floor($totalNetoExigiblePrestamo)) && $adeudoArrastre <= 0);

                if ($esLiquidadoTotal && $corteDondeSeLiquido === null) {
                    $corteDondeSeLiquido = $corteNum;
                }

                // Agregar fila al reporte con numeración por cliente
                $filasEstePrestamo[] = [
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

            // REGLA: Si el adeudo total se liquidó en un corte previo ($corteDondeSeLiquido < $cortesTranscurridos),
            // en el siguiente corte el préstamo desaparece por completo de la relación.
            if ($corteDondeSeLiquido !== null && $cortesTranscurridos > $corteDondeSeLiquido) {
                if ($p->estado !== 'finalizado') {
                    $p->update([
                        'estado' => 'finalizado',
                        'adeudo_pendiente' => 0.00,
                    ]);
                }
                continue;
            }

            // Agregar las filas generadas de este préstamo
            foreach ($filasEstePrestamo as $f) {
                $filas[] = $f;
            }
        }

        return $filas;
    }
}
