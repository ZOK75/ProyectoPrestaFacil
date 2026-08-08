<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relación de Cobranza - {{ $operador->name }}</title>
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
                    <span class="text-xl font-extrabold tracking-tight text-slate-900 block">PrestaFácil</span>
                    <span class="text-xs text-slate-500 font-semibold block">Sistema de Vales & Cobranza</span>
                </div>
            </div>

            <!-- Derecha: Datos de la Distribuidora -->
            <div class="text-right text-xs space-y-1">
                <div class="font-bold text-slate-900">
                    Número Distribuidora: <span class="font-mono text-slate-700">DIST-{{ str_pad($operador->id, 8, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Nombre: <span class="font-semibold text-slate-800">{{ $operador->name }}</span>
                </div>
                <div class="text-slate-600 font-medium">
                    Domicilio: {{ $operador->sucursal?->direccion ?? 'Calle las Cruces, Fracc. Villa Jardín #239' }}, {{ $operador->sucursal?->nombre ?? 'Torreón Coahuila' }}
                </div>
                <div class="font-bold text-slate-900">
                    Límite de crédito: <span class="font-bold text-slate-900">${{ number_format($operador->limite_credito ?? 20000, 0) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Crédito disponible: <span class="font-bold text-emerald-700">${{ number_format($operador->creditoDisponible(), 0) }}</span>
                </div>
                <div class="font-bold text-slate-900">
                    Puntos: <span class="font-bold text-indigo-700">{{ $operador->puntos ?? 346 }}</span>
                </div>
                <div class="font-bold text-slate-900 pt-1">
                    Referencia de Pago: <span class="font-mono text-slate-900 font-extrabold">{{ $operador->referenciaPago() }}</span>
                </div>
            </div>
        </div>

        <!-- Banner de Fechas y Total a PAGAR -->
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="space-y-1">
                <div>
                    <span class="font-bold text-slate-900">Fecha límite de pago:</span>
                    <span class="font-semibold text-slate-800 ml-1">
                        {{ $configuracion->fecha_limite_pago ? $configuracion->fecha_limite_pago->format('d \d\e F Y') : now()->addDays(15)->format('d \d\e F Y') }}
                    </span>
                </div>
                <div>
                    <span class="font-bold text-slate-900">Pago anticipado:</span>
                    <span class="text-slate-700 ml-1">13, 14, 15 de febrero {{ date('Y') }}</span>
                </div>
            </div>

            @php
                $totalQuincenalPeriodo = $prestamos->sum('cuota_quincenal');
                $totalRecargosPeriodo = $prestamos->sum('multas');
                $totalPagarGeneral = $totalQuincenalPeriodo + $totalRecargosPeriodo;
                $porcentajeComision = $operador->obtenerPorcentajeGanancia();
                $totalComisiones = $totalQuincenalPeriodo * ($porcentajeComision / 100);
            @endphp

            <div class="text-left sm:text-right border-t sm:border-t-0 sm:border-l border-slate-300 pt-2 sm:pt-0 sm:pl-4">
                <span class="text-[11px] uppercase font-bold text-slate-500 block">Total a PAGAR:</span>
                <span class="text-xl font-black text-slate-900">${{ number_format($totalPagarGeneral, 2) }}</span>
            </div>
        </div>

        <!-- Tabla de Relación de Clientes con Préstamos -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-900">
                <thead class="bg-slate-100 text-slate-900 font-extrabold uppercase border-b border-slate-900">
                    <tr>
                        <th class="border-r border-slate-900 px-2 py-2 text-center w-8">#</th>
                        <th class="border-r border-slate-900 px-3 py-2">Producto</th>
                        <th class="border-r border-slate-900 px-3 py-2">Cliente</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-center">Pagos Realizados</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Comisión</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Pago</th>
                        <th class="border-r border-slate-900 px-3 py-2 text-right">Recargos</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-900">
                    @forelse($prestamos as $index => $p)
                        @php
                            $comisionRow = $p->cuota_quincenal * ($porcentajeComision / 100);
                            $totalRow = $p->cuota_quincenal + $p->multas;
                        @endphp
                        <tr>
                            <td class="border-r border-slate-900 px-2 py-2.5 text-center font-bold">{{ $index + 1 }}</td>
                            <td class="border-r border-slate-900 px-3 py-2.5 font-semibold">
                                {{ $p->productoVale->clave }} ({{ $p->pagos_totales }} 15nas)
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 font-medium">
                                {{ $p->cliente->nombre }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-center font-bold">
                                {{ $p->pagos_realizados }}/{{ $p->pagos_totales }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">
                                ${{ number_format($comisionRow, 2) }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">
                                ${{ number_format($p->cuota_quincenal, 2) }}
                            </td>
                            <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">
                                ${{ number_format($p->multas, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold">
                                ${{ number_format($totalRow, 2) }}
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
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">${{ number_format($totalComisiones, 2) }}</td>
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">${{ number_format($totalQuincenalPeriodo, 2) }}</td>
                        <td class="border-r border-slate-900 px-3 py-2.5 text-right font-mono">${{ number_format($totalRecargosPeriodo, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono text-slate-900 text-sm">${{ number_format($totalPagarGeneral, 2) }}</td>
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
