@extends('layouts.app')

@section('title', 'Recepción de Abonos y Vales - Cajero')

@section('content')
<div class="max-w-3xl mx-auto space-y-4 pb-8" x-data="{ 
    pagoModalOpen: false, 
    distSeleccionada: null,
    pagoValeModalOpen: false,
    valeSeleccionado: null
}">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
        <a href="{{ route('cajero.conciliaciones.index') }}" class="text-xs font-bold text-cyan-400 hover:text-cyan-300 inline-flex items-center gap-1">
            Módulo de Conciliaciones &rarr;
        </a>
    </div>

    <!-- Buscador de Distribuidoras y Vales -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form novalidate action="{{ route('cajero.abonos.index') }}" method="GET">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Buscar Distribuidora, Cliente o Folio de Vale</label>
            <div class="relative">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Referencia (REF-DIST-..., VAL-...), Distribuidora o Cliente..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-4 pr-12 text-white font-mono text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg px-4 flex items-center justify-center transition-colors font-bold text-xs">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Alertas de Sesión / Error / Validación -->
    @if (session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center gap-2 text-emerald-400 font-bold text-xs">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex items-center gap-2 text-rose-400 font-bold text-xs">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl space-y-1">
            <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Error al registrar el abono:</span>
            </div>
            <ul class="text-xs text-rose-300 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Resultados -->
    @if(request()->filled('buscar') && $distribuidoras->isEmpty())
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl text-center text-slate-400 text-sm">
            No se encontraron distribuidoras ni vales activos con ese criterio de búsqueda.
        </div>
    @endif

    <div class="space-y-4">
        @foreach($distribuidoras as $dist)
            @php
                $corteService = app(\App\Services\CorteCobranzaService::class);
                $filasDist = $corteService->generarFilasRelacionCobranza($dist);

                $totalComisionesSum = 0;
                $totalPagosSum = 0;
                $totalRecargosSum = 0;
                $filasPorPrestamo = [];
                foreach($filasDist as $f) {
                    $totalComisionesSum += floatval($f['comision']);
                    $totalPagosSum += floatval($f['pago']);
                    $totalRecargosSum += floatval($f['recargos']);
                    $filasPorPrestamo[$f['prestamo_id']][] = $f;
                }

                $totalGeneralSum = 0;
                foreach($filasPorPrestamo as $prestamoId => $filasP) {
                    $ultimaFila = end($filasP);
                    $totalGeneralSum += max(0.0, floatval($ultimaFila['total']));
                }

                $cuotaBruta = $totalPagosSum;
                $comisionDist = $totalComisionesSum;
                $multasAcum = $totalRecargosSum;
                $cuotaNeta = max(0.0, $totalGeneralSum - $multasAcum);
                $totalRelacion = max(0.0, $totalGeneralSum);
                $adeudoGlobal = $dist->totalAdeudoGlobal();
            @endphp
            <div class="bg-slate-900 border border-slate-800 hover:border-emerald-500/30 rounded-2xl p-5 shadow-xl transition-all space-y-4" x-data="{ expanded: true }">
                
                <!-- Encabezado de la Distribuidora -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                    <div>
                        <div class="font-mono text-[10px] font-black text-emerald-400 mb-0.5">{{ $dist->referenciaPago() }}</div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            {{ $dist->name }}
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                Cat. {{ ucfirst($dist->categoria_distribuidor ?? 'Cobre') }} ({{ $dist->obtenerPorcentajeGanancia() }}% Com.)
                            </span>
                        </h3>
                        <span class="text-xs text-slate-400">{{ $dist->sucursal?->nombre ?? 'Sucursal General' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="expanded = !expanded" class="text-xs font-semibold text-slate-400 hover:text-white px-2.5 py-1.5 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 transition">
                            <span x-text="expanded ? 'Ocultar Vales ▲' : 'Ver {{ $dist->prestamos->count() }} Vales ▼'"></span>
                        </button>
                    </div>
                </div>
                
                <!-- Resumen Financiero Distribuidora (Neto de Comisión para coincidir con la Relación) -->
                <div class="grid grid-cols-3 gap-2 bg-slate-950/80 rounded-xl p-3 border border-slate-800 text-center text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Cuota Neta (Abono)</span>
                        <span class="font-bold text-emerald-400 font-mono">${{ number_format($cuotaNeta, 2) }}</span>
                        @if($comisionDist > 0)
                            <span class="text-[9px] text-indigo-400 block font-mono">Comisión: -${{ number_format($comisionDist, 2) }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Multas / Recargos</span>
                        <span class="font-bold font-mono {{ $multasAcum > 0 ? 'text-rose-400' : 'text-slate-400' }}">${{ number_format($multasAcum, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Total a Cobrar Relación</span>
                        <span class="font-bold font-mono text-white">${{ number_format(floor($totalRelacion), 2) }}</span>
                    </div>
                </div>

                <!-- Desglose de Vales Individuales -->
                <div x-show="expanded" class="space-y-2 pt-1" x-transition>
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Vales Activos (Cobro Individual según Relación)</span>
                        <span class="text-slate-500">{{ $dist->prestamos->count() }} vale(s)</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($dist->prestamos as $prestamo)
                            @php
                                $filasPrestamo = array_values(array_filter($filasDist, fn($f) => $f['prestamo_id'] == $prestamo->id));
                                $cuotaBrutaVale = 0;
                                $comisionVale = 0;
                                $multaPrestamo = 0;
                                $totalExigibleVale = 0;

                                if (!empty($filasPrestamo)) {
                                    $ultimaFilaVale = end($filasPrestamo);
                                    $totalExigibleVale = floatval($ultimaFilaVale['total']);

                                    foreach($filasPrestamo as $fp) {
                                        $cuotaBrutaVale += floatval($fp['pago']);
                                        $comisionVale += floatval($fp['comision']);
                                        $multaPrestamo += floatval($fp['recargos']);
                                    }
                                    $cuotaNetaVale = max(0.0, $totalExigibleVale - $multaPrestamo);

                                    $numCortesAtrasados = max(0, count($filasPrestamo) - 1);
                                    $comisionPorQuincena = count($filasPrestamo) > 0 ? ($comisionVale / count($filasPrestamo)) : $prestamo->comisionDistribuidorPorQuincena();
                                    $comisionesPerdidas = ($numCortesAtrasados > 0 && $multaPrestamo > 0) ? ($numCortesAtrasados * $comisionPorQuincena) : 0.0;
                                } else {
                                    $cuotaBrutaVale = floatval($prestamo->cuota_quincenal);
                                    $comisionVale = $prestamo->comisionDistribuidorPorQuincena();
                                    $multaPrestamo = floatval($prestamo->multas ?? 0.0);
                                    $totalExigibleVale = $prestamo->totalExigibleQuincenalNeto();
                                    $cuotaNetaVale = max(0.0, $cuotaBrutaVale - $comisionVale);
                                    $comisionesPerdidas = 0.0;
                                }

                                $saldoCapital = floatval($prestamo->adeudo_pendiente);
                                $saldoTotalPendiente = $saldoCapital + $multaPrestamo + $comisionesPerdidas;
                            @endphp
                            <div class="bg-slate-950 border border-slate-800 hover:border-slate-700 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-indigo-400">{{ $prestamo->referencia }}</span>
                                        <span class="text-xs font-bold text-white">{{ $prestamo->cliente?->nombre_completo ?? $prestamo->cliente?->nombre ?? 'Cliente #'.$prestamo->cliente_id }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Producto: <strong class="text-slate-300">{{ $prestamo->productoVale?->nombre ?? 'Vale Estándar' }}</strong>
                                        @if($multaPrestamo > 0)
                                             &bull; <span class="text-rose-400 font-bold font-mono">Recargos: ${{ number_format($multaPrestamo, 2) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <div class="text-right text-xs">
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Total a Cobrar:</div>
                                        <div class="font-black text-emerald-400 font-mono text-sm">${{ number_format($totalExigibleVale, 2) }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Cuota: ${{ number_format($cuotaNetaVale, 2) }}
                                            @if($multaPrestamo > 0)
                                                <span class="text-rose-400 font-bold"> + ${{ number_format($multaPrestamo, 2) }} recargos</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-mono">Saldo pendiente: ${{ number_format($saldoTotalPendiente, 2) }}</div>
                                    </div>

                                    <button type="button"
                                            @click="valeSeleccionado = {
                                                id: '{{ $prestamo->id }}',
                                                referencia: '{{ $prestamo->referencia }}',
                                                cliente: '{{ addslashes($prestamo->cliente?->nombre_completo ?? $prestamo->cliente?->nombre ?? 'Cliente') }}',
                                                producto: '{{ addslashes($prestamo->productoVale?->nombre ?? 'Vale') }}',
                                                distribuidora: '{{ addslashes($dist->name) }}',
                                                cuotaBruta: {{ $cuotaBrutaVale }},
                                                comision: {{ $comisionVale }},
                                                cuotaNeta: {{ $cuotaNetaVale }},
                                                multas: {{ $multaPrestamo }},
                                                saldo: {{ $saldoTotalPendiente }},
                                                totalExigible: {{ $totalExigibleVale }}
                                            }; pagoValeModalOpen = true"
                                            class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-950/20 transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Abonar a este Vale
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-center text-slate-500 text-xs">
                                No hay vales activos pendientes para esta distribuidora.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-4">
            {{ $distribuidoras->links() }}
        </div>
    </div>

    <!-- Modal Registro de Pago por Vale Individual -->
    <div x-show="pagoValeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.away="pagoValeModalOpen = false">
            
            <div class="bg-emerald-600 p-4">
                <h3 class="text-white font-black text-base">Cobro de Vale Individual</h3>
                <p class="text-emerald-100 text-xs font-bold mt-0.5" x-text="valeSeleccionado?.cliente"></p>
                <div class="flex items-center justify-between text-emerald-200 text-[11px] font-mono mt-0.5">
                    <span x-text="'Vale: ' + valeSeleccionado?.referencia"></span>
                    <span x-text="'Dist: ' + valeSeleccionado?.distribuidora"></span>
                </div>
            </div>

            <form novalidate :action="'{{ url('cajero/abonos') }}/' + valeSeleccionado?.id" method="POST" class="p-4 space-y-3.5 overflow-y-auto flex-1">
                @csrf
                
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cuota Quincenal Neta:</span>
                        <span class="text-slate-200 font-mono font-bold" x-text="'$' + Number(valeSeleccionado?.cuotaNeta || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between" x-show="valeSeleccionado?.multas > 0">
                        <span class="text-rose-400">(+) Multas / Recargos:</span>
                        <span class="text-rose-400 font-mono font-bold" x-text="'$' + Number(valeSeleccionado?.multas || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1.5 font-bold">
                        <span class="text-white">Total a Cobrar (Relación):</span>
                        <span class="text-emerald-400 font-mono font-black text-sm" x-text="'$' + Number(valeSeleccionado?.totalExigible || 0).toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar al Vale ($)</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required placeholder="0.00"
                        :value="Number(valeSeleccionado?.totalExigible || 0).toFixed(2)"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-3 text-emerald-400 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-center">
                    <span class="text-[10px] text-slate-500 block mt-0.5">Monto total sugerido según la Relación de Cobranza.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500">
                        <option value="transferencia">Transferencia / SPEI Bancario</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" placeholder="Ficha bancaria, número de recibo..." class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="pagoValeModalOpen = false" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl transition-colors shadow-lg shadow-emerald-500/20">
                        Cobrar Vale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Registro de Abono Global por Distribuidora -->
    <div x-show="pagoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.away="pagoModalOpen = false">
            
            <div class="bg-indigo-600 p-4">
                <h3 class="text-white font-black text-base">Registrar Abono Global</h3>
                <p class="text-indigo-100 text-xs font-bold mt-0.5" x-text="distSeleccionada?.nombre"></p>
                <p class="text-indigo-200 text-[11px] font-mono" x-text="'Ref Oficial: ' + distSeleccionada?.ref"></p>
            </div>

            <form novalidate :action="'{{ url('cajero/abonos/distribuidora') }}/' + distSeleccionada?.id" method="POST" class="p-4 space-y-3.5 overflow-y-auto flex-1">
                @csrf
                
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cuota Quincenal Neta Total:</span>
                        <span class="text-slate-200 font-mono font-bold" x-text="'$' + Number(distSeleccionada?.cuotaNeta || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between" x-show="distSeleccionada?.multas > 0">
                        <span class="text-rose-400">(+) Multas Acumuladas:</span>
                        <span class="text-rose-400 font-mono font-bold" x-text="'$' + Number(distSeleccionada?.multas || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1.5 font-bold">
                        <span class="text-white">Total a Cobrar (Relación):</span>
                        <span class="text-emerald-400 font-mono font-black text-sm" x-text="'$' + Number(Math.floor(distSeleccionada?.totalRelacion || 0)).toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Referencia de Pago a Verificar</label>
                    <input type="text" name="referencia_pago" :value="distSeleccionada?.ref" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white font-mono text-sm mt-1 focus:ring-2 focus:ring-indigo-500 uppercase text-center font-bold">
                    <span class="text-[10px] text-slate-500 block mt-0.5">Debe coincidir con la referencia bancaria oficial.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar ($)</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required placeholder="0.00"
                        :value="Number(Math.floor(distSeleccionada?.totalRelacion || 0)).toFixed(2)"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-3 text-indigo-300 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center">
                    <span class="text-[10px] text-slate-500 block mt-0.5">Monto total sugerido según la Relación de Cobranza.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-indigo-500">
                        <option value="transferencia">Transferencia / SPEI Bancario</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" placeholder="Ficha bancaria, número de guía..." class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="pagoModalOpen = false" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs rounded-xl transition-colors shadow-lg shadow-indigo-600/20">
                        Confirmar Abono
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

