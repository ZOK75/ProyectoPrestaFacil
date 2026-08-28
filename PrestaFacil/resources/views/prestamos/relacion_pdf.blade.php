<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relación de Cobranza - {{ $distribuidora->name }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .print-container { shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 text-slate-900 font-sans">

    <!-- Barra Superior de Acciones (Oculta al Imprimir) -->
    <div class="max-w-4xl mx-auto mb-4 px-4 flex items-center justify-between no-print">
        <a href="{{ route('prestamos.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-slate-600 hover:text-slate-900 transition">
            &larr; Volver al panel de préstamos
        </a>

        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / Descargar PDF
            </button>
        </div>
    </div>

    <!-- Documento Oficial (Réplica Exacta del PDF de la Empresa) -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 shadow-2xl p-8 sm:p-12 rounded-2xl print-container space-y-6">

        <!-- Encabezado: Logo Izquierda y Ficha de Distribuidor Derecha -->
        <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-6">
            <!-- Izquierda: Logo Oficial -->
            <div class="flex items-center gap-3">
                <div class="w-24 h-24 rounded-full border-2 border-slate-900 flex flex-col items-center justify-center p-2 relative">
                    <div class="w-20 h-20 rounded-full border border-slate-800 flex items-center justify-center font-black text-slate-900 text-lg">
                        LOGO
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-extrabold tracking-tight text-slate-900 block">PrestaFácil</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 border border-indigo-300">
                            Corte #{{ $numeroCorte ?? 1 }}
                        </span>
                    </div>
                    <span class="text-xs text-slate-500 font-semibold block">Relación de Cobranza Oficial</span>
                </div>
            </div>

            <!-- Derecha: Datos de la Distribuidora -->
            <div class="text-right text-xs space-y-1">
                <div class="font-bold text-slate-900">
                    Número Distribuidora: <span class="font-mono text-slate-700">DIST-{{ str_pad($distribuidora->id, 8, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Nombre: <span class="font-semibold text-slate-800">{{ $distribuidora->name }}</span>
                </div>
                <div class="text-slate-600 font-medium">
                    Domicilio: {{ $distribuidora->sucursal?->direccion ?? 'Calle las Cruces, Fracc. Villa Jardín #239' }}, {{ $distribuidora->sucursal?->nombre ?? 'Torreón Coahuila' }}
                </div>
                <div class="font-bold text-slate-900">
                    Límite de crédito: <span class="font-bold text-slate-900">${{ number_format($distribuidora->limite_credito ?? 20000, 0) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Crédito disponible: <span class="font-bold text-emerald-700">${{ number_format($distribuidora->creditoDisponible(), 0) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Puntos: <span class="font-bold text-indigo-700">{{ $distribuidora->puntos ?? $distribuidora->puntosAcumulados() }}</span>
                </div>
                <div class="font-bold text-slate-900 pt-1">
                    Referencia de Pago: <span class="font-mono text-slate-900 font-extrabold">{{ $distribuidora->referenciaPago() }}</span>
                </div>
            </div>
        </div>

        @php
            $porcentajeComision = $distribuidora->obtenerPorcentajeGanancia();
            
            // Verificar si la distribuidora o sus préstamos tienen multas o retrasos activos
            $tieneMultasPendientes = (floatval($distribuidora->multas ?? 0.0) > 0);
            foreach($prestamos as $p) {
                if (floatval($p->multas ?? 0.0) > 0) {
                    $tieneMultasPendientes = true;
                    break;
                }
            }

            // Determinar si el periodo actual ya está 100% liquidado sin multas moratorias
            $periodoLiquidado = (!$tieneMultasPendientes && $relacion && ($relacion->adeudo_pendiente <= 0 || floatval($relacion->monto_pagado) >= floatval($relacion->monto_total_periodo)) && in_array($relacion->estado_pago, ['pago_anticipado', 'pago_a_tiempo', 'liquidado']));

            $montoAbonadoPeriodo = floatval($relacion ? $relacion->monto_pagado : 0.0);
            $esAbonoParcial = (!$periodoLiquidado && $montoAbonadoPeriodo > 0);

            // 1. Calcular valores base por cada fila
            $filasCalculadas = [];
            $totalComisionesSum = 0;
            $totalPagosSum = 0;
            $totalRecargosSum = 0;
            $totalGeneralSum = 0;
            $exigibleTotalBase = 0;

            foreach($prestamos as $index => $p) {
                $totalPagos = max(1, intval($p->pagos_totales));
                $comisionRow = (($porcentajeComision / 100) * floatval($p->monto_prestamo)) / $totalPagos;
                $pagoRow = floatval($p->cuota_quincenal);
                $multaRow = floatval($p->multas ?? 0.0);
                $tieneRetraso = ($multaRow > 0);
                $multaValeRow = $p->multaConfigurada() > 0 ? $p->multaConfigurada() : 300.00;
                $cortesVencidosRow = ($tieneRetraso && $multaValeRow > 0) ? max(1, intval(round($multaRow / $multaValeRow))) : ($tieneRetraso ? 1 : 0);

                $recargosRow = $multaRow;
                $atrasosRow = $tieneRetraso ? ($cortesVencidosRow * $pagoRow) : 0.00;

                // Formato número de pago: 1/8, 2/8...
                if ($periodoLiquidado) {
                    $numPago = max(1, intval($p->pagos_realizados));
                } else {
                    $numPago = min($totalPagos, max(1, intval($p->pagos_realizados) + 1));
                }
                $formatoPago = "{$numPago}/{$totalPagos}";

                // Base exigible de este vale en este corte
                $exigibleFila = $pagoRow + $comisionRow + $recargosRow + $atrasosRow;

                $filasCalculadas[] = [
                    'prestamo' => $p,
                    'producto' => $p->productoVale->nombre ?? $p->productoVale->clave ?? ('Vale ' . ($index + 1)),
                    'cliente' => $p->cliente->nombre_completo ?? $p->cliente->nombre,
                    'formato_pago' => $formatoPago,
                    'comision' => $comisionRow,
                    'pago' => $pagoRow,
                    'recargos' => $recargosRow,
                    'exigible_fila' => $exigibleFila,
                ];

                $totalComisionesSum += $comisionRow;
                $totalPagosSum += $pagoRow;
                $totalRecargosSum += $recargosRow;
                $exigibleTotalBase += $exigibleFila;
            }

            // 2. Distribuir abonos o liquidaciones entre las filas para obtener el total restante
            $montoAbonadoRestante = $montoAbonadoPeriodo;
            $filasFinales = [];

            foreach($filasCalculadas as $idx => $f) {
                if ($periodoLiquidado) {
                    // Si el periodo actual fue liquidado en su totalidad:
                    $totalFila = 0.00;
                } elseif ($montoAbonadoPeriodo > 0) {
                    if ($idx === count($filasCalculadas) - 1) {
                        // En la última fila, aplicar todo el saldo restante del abono (incluso si produce saldo negativo/excedente)
                        $totalFila = $f['exigible_fila'] - $montoAbonadoRestante;
                    } else {
                        $abonoFila = min($montoAbonadoRestante, $f['exigible_fila']);
                        $montoAbonadoRestante -= $abonoFila;
                        $totalFila = $f['exigible_fila'] - $abonoFila;
                    }
                } else {
                    $totalFila = $f['exigible_fila'];
                }

                $totalGeneralSum += $totalFila;
                $f['total'] = $totalFila;
                $filasFinales[] = $f;
            }
        @endphp

        <!-- Banner de Fechas y Total a PAGAR -->
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="space-y-1">
                <div>
                    <span class="font-bold text-slate-900">Fecha límite de pago:</span>
                    <span class="font-semibold text-slate-800 ml-1">
                        @if($periodoLiquidado && $relacion && $relacion->fecha_limite_pago)
                            {{ $relacion->fecha_limite_pago->copy()->addDays(15)->format('d \d\e F Y H:i') }}
                        @else
                            {{ ($relacion ? $relacion->fecha_limite_pago : $configuracion->fecha_limite_pago) ? ($relacion ? $relacion->fecha_limite_pago : $configuracion->fecha_limite_pago)->format('d \d\e F Y H:i') : now()->addDays(15)->format('d \d\e F Y') }}
                        @endif
                    </span>
                </div>
                <div>
                    <span class="font-bold text-slate-900">Fecha de corte:</span>
                    <span class="text-slate-700 ml-1">
                        @if($periodoLiquidado && $relacion && $relacion->fecha_corte)
                            {{ $relacion->fecha_corte->copy()->addDays(15)->format('d \d\e F Y H:i') }}
                        @else
                            {{ ($relacion ? $relacion->fecha_corte : $configuracion->fecha_corte) ? ($relacion ? $relacion->fecha_corte : $configuracion->fecha_corte)->format('d \d\e F Y H:i') : '13 de febrero 2026' }}
                        @endif
                    </span>
                </div>
                @if(isset($relacion))
                    <div class="pt-1">
                        <span class="font-bold text-slate-900">Estado:</span>
                        @if($periodoLiquidado)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                Liquidado (Al Corriente) @if($relacion->puntos_ganados > 0) (+{{ $relacion->puntos_ganados }} pts) @endif
                            </span>
                        @elseif($relacion->esPagoAtrasado())
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 uppercase">
                                Pago Atrasado (-{{ $relacion->puntos_descontados }} pts)
                            </span>
                        @elseif($esAbonoParcial)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300 uppercase">
                                Abono Parcial (${{ number_format($montoAbonadoPeriodo, 2) }}) - Saldo Pendiente
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-slate-200 text-slate-800 border border-slate-300 uppercase">
                                Pendiente
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="text-left sm:text-right border-t sm:border-t-0 sm:border-l border-slate-300 pt-2 sm:pt-0 sm:pl-4 space-y-0.5">
                <span class="text-[11px] uppercase font-bold text-slate-500 block">Subtotal Pagos (Cuotas Base): ${{ number_format($totalPagosSum, 2) }}</span>
                <span class="text-[11px] uppercase font-bold text-indigo-600 block">Total Comisiones (Cat. {{ ucfirst($distribuidora->categoria_distribuidor ?? 'Cobre') }} {{ $porcentajeComision }}%): ${{ number_format($totalComisionesSum, 2) }}</span>
                @if($totalRecargosSum > 0)
                    <span class="text-[11px] uppercase font-bold text-rose-600 block">Recargos por Retraso: +${{ number_format($totalRecargosSum, 2) }}</span>
                @endif
                @if($montoAbonadoPeriodo > 0)
                    <span class="text-[11px] uppercase font-bold text-emerald-600 block">(-) Abonos Realizados en el Periodo: -${{ number_format($montoAbonadoPeriodo, 2) }}</span>
                @endif
                <span class="text-xl font-black text-slate-900 block">Total a PAGAR: ${{ number_format($totalGeneralSum, 2) }}</span>
            </div>
        </div>

        <!-- Tabla de Conciliación / Relación de Cobranza -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-900">
                <thead class="bg-slate-100 text-slate-900 font-extrabold uppercase border-b border-slate-900 text-[11px]">
                    <tr>
                        <th class="border-r border-slate-900 px-2 py-2 text-center w-8">#</th>
                        <th class="border-r border-slate-900 px-3 py-2">Producto</th>
                        <th class="border-r border-slate-900 px-3 py-2">Cliente</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-center">Número de Pago</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Comisión</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Pago</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Recargos</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-900">
                    @forelse($filasFinales as $index => $fila)
                        <tr>
                            <td class="border-r border-slate-900 px-2 py-2.5 text-center font-bold">{{ $index + 1 }}</td>
                            <td class="border-r border-slate-900 px-3 py-2.5 font-semibold">
                                {{ $fila['producto'] }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 font-medium">
                                {{ $fila['cliente'] }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-center font-bold font-mono">
                                {{ $fila['formato_pago'] }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono text-indigo-900 font-semibold">
                                ${{ number_format($fila['comision'], 2) }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono font-bold">
                                ${{ number_format($fila['pago'], 2) }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono font-bold {{ $fila['recargos'] > 0 ? 'text-rose-700' : 'text-slate-500' }}">
                                ${{ number_format($fila['recargos'], 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-black {{ $fila['total'] < 0 ? 'text-emerald-700' : ($fila['total'] > 0 ? 'text-slate-950' : 'text-slate-500') }}">
                                ${{ number_format($fila['total'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500 font-medium italic">
                                No se encontraron clientes con préstamos activos para este periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100 font-extrabold border-t-2 border-slate-900">
                    <tr>
                        <td colspan="4" class="border-r border-slate-900 px-3 py-2.5 text-right uppercase">Totales</td>
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono text-indigo-900">${{ number_format($totalComisionesSum, 2) }}</td>
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono text-slate-900">${{ number_format($totalPagosSum, 2) }}</td>
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono text-rose-700">${{ number_format($totalRecargosSum, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono text-slate-950 text-sm">${{ number_format($totalGeneralSum, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pie de Página: Datos Bancarios Oficiales -->
        <div class="pt-4 border-t border-slate-200 space-y-3">
            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">
                Nombre de la Empresa: <span class="text-indigo-700">Prestamo Fácil SA</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tarjeta BBVA -->
                <div class="border border-slate-300 rounded-xl p-4 bg-slate-50 flex items-center justify-between">
                    <div>
                        <div class="text-blue-900 font-black text-base tracking-tighter">BBVA</div>
                        <div class="text-xs text-slate-700 mt-1 font-medium">
                            Convenio: <strong class="font-mono text-slate-900">1628789</strong>
                        </div>
                        <div class="text-xs text-slate-700 font-medium">
                            CLABE: <strong class="font-mono text-slate-900">0021150160032411</strong>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta BANORTE -->
                <div class="border border-slate-300 rounded-xl p-4 bg-slate-50 flex items-center justify-between">
                    <div>
                        <div class="text-rose-700 font-black text-base tracking-tighter">BANORTE</div>
                        <div class="text-xs text-slate-700 mt-1 font-medium">
                            Convenio: <strong class="font-mono text-slate-900">57148</strong>
                        </div>
                        <div class="text-xs text-slate-700 font-medium">
                            CLABE: <strong class="font-mono text-slate-900">1478789419623710</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
