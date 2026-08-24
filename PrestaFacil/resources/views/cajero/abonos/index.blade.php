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

    <!-- Alertas de Error / Validación -->
    @if ($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl space-y-1">
            <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Error al registrar el abono:
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
                $cuotaQna = $dist->totalCuotaQuincenal();
                $adeudoGlobal = $dist->totalAdeudoGlobal();
                $multasAcum = floatval($dist->multas ?? 0);
            @endphp
            <div class="bg-slate-900 border border-slate-800 hover:border-emerald-500/30 rounded-2xl p-5 shadow-xl transition-all space-y-4" x-data="{ expanded: true }">
                
                <!-- Encabezado de la Distribuidora -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                    <div>
                        <div class="font-mono text-[10px] font-black text-emerald-400 mb-0.5">{{ $dist->referenciaPago() }}</div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            {{ $dist->name }}
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                Cat. {{ $dist->categoria_distribuidor ?? 'Estándar' }}
                            </span>
                        </h3>
                        <span class="text-xs text-slate-400">{{ $dist->sucursal?->nombre ?? 'Sucursal General' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="expanded = !expanded" class="text-xs font-semibold text-slate-400 hover:text-white px-2 py-1 rounded bg-slate-800 border border-slate-700">
                            <span x-text="expanded ? 'Ocultar Vales ▲' : 'Ver {{ $dist->prestamos->count() }} Vales ▼'"></span>
                        </button>
                        <button type="button" 
                                @click="distSeleccionada = { id: '{{ $dist->id }}', ref: '{{ $dist->referenciaPago() }}', nombre: '{{ addslashes($dist->name) }}', cuota: {{ $cuotaQna }}, multas: {{ $multasAcum }}, total: {{ $adeudoGlobal }} }; pagoModalOpen = true"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition-colors shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Abono Global
                        </button>
                    </div>
                </div>
                
                <!-- Resumen Financiero Distribuidora -->
                <div class="grid grid-cols-3 gap-2 bg-slate-950/80 rounded-xl p-3 border border-slate-800 text-center text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Cuota Quincenal</span>
                        <span class="font-bold text-white font-mono">${{ number_format($cuotaQna, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Multas Totales</span>
                        <span class="font-bold font-mono {{ $multasAcum > 0 ? 'text-rose-400' : 'text-slate-400' }}">${{ number_format($multasAcum, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Adeudo Total</span>
                        <span class="font-bold font-mono text-emerald-400">${{ number_format($adeudoGlobal, 2) }}</span>
                    </div>
                </div>

                <!-- Desglose de Vales Individuales -->
                <div x-show="expanded" class="space-y-2 pt-1" x-transition>
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Vales Activos (Cobro Individual)</span>
                        <span class="text-slate-500">{{ $dist->prestamos->count() }} vale(s)</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($dist->prestamos as $prestamo)
                            @php
                                $multaPrestamo = floatval($prestamo->multas ?? 0.0);
                                $totalExigibleVale = floatval($prestamo->adeudo_pendiente) + $multaPrestamo;
                                $cuotaVale = floatval($prestamo->cuota_quincenal);
                            @endphp
                            <div class="bg-slate-950 border border-slate-800 hover:border-slate-700 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-indigo-400">{{ $prestamo->referencia }}</span>
                                        <span class="text-xs font-bold text-white">{{ $prestamo->cliente?->nombre_completo ?? 'Cliente #'.$prestamo->cliente_id }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Producto: <strong class="text-slate-300">{{ $prestamo->productoVale?->nombre ?? 'Vale Estándar' }}</strong>
                                        @if($prestamo->productoVale?->multa > 0)
                                            &bull; <span class="text-slate-500">Multa config: ${{ number_format($prestamo->productoVale->multa, 2) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <div class="text-right text-xs">
                                        <div class="font-bold text-white font-mono">Cuota: ${{ number_format($cuotaVale, 2) }}</div>
                                        @if($multaPrestamo > 0)
                                            <div class="text-[11px] font-bold text-rose-400 font-mono">Multa: +${{ number_format($multaPrestamo, 2) }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-400 font-mono">Saldo: ${{ number_format($prestamo->adeudo_pendiente, 2) }}</div>
                                    </div>

                                    <button type="button"
                                            @click="valeSeleccionado = {
                                                id: '{{ $prestamo->id }}',
                                                referencia: '{{ $prestamo->referencia }}',
                                                cliente: '{{ addslashes($prestamo->cliente?->nombre_completo ?? '') }}',
                                                producto: '{{ addslashes($prestamo->productoVale?->nombre ?? 'Vale') }}',
                                                distribuidora: '{{ addslashes($dist->name) }}',
                                                cuota: {{ $cuotaVale }},
                                                multas: {{ $multaPrestamo }},
                                                saldo: {{ floatval($prestamo->adeudo_pendiente) }},
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
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="pagoValeModalOpen = false">
            
            <div class="bg-emerald-600 p-4">
                <h3 class="text-white font-black text-base">Cobro de Vale Individual</h3>
                <p class="text-emerald-100 text-xs font-bold mt-0.5" x-text="valeSeleccionado?.cliente"></p>
                <div class="flex items-center justify-between text-emerald-200 text-[11px] font-mono mt-0.5">
                    <span x-text="'Vale: ' + valeSeleccionado?.referencia"></span>
                    <span x-text="'Dist: ' + valeSeleccionado?.distribuidora"></span>
                </div>
            </div>

            <form novalidate :action="'{{ url('cajero/abonos') }}/' + valeSeleccionado?.id" method="POST" class="p-4 space-y-3.5">
                @csrf
                
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cuota Quincenal:</span>
                        <span class="text-white font-mono font-bold" x-text="'$' + Number(valeSeleccionado?.cuota).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Multas del Vale:</span>
                        <span class="text-rose-400 font-mono font-bold" x-text="'$' + Number(valeSeleccionado?.multas).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Saldo Capital Restante:</span>
                        <span class="text-slate-300 font-mono font-bold" x-text="'$' + Number(valeSeleccionado?.saldo).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1.5 font-bold">
                        <span class="text-slate-200">Total a Liquidar Vale:</span>
                        <span class="text-emerald-400 font-mono font-black" x-text="'$' + Number(valeSeleccionado?.totalExigible).toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar al Vale ($)</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required placeholder="0.00"
                        :value="valeSeleccionado?.cuota"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-3 text-emerald-400 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-center">
                    <span class="text-[10px] text-slate-500 block mt-0.5">El abono cubre primero multas del vale y luego reduce el saldo deudor.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500">
                        <option value="transferencia">Transferencia / SPEI Bancario</option>
                        <option value="tarjeta">Tarjeta de Débito / Crédito</option>
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
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="pagoModalOpen = false">
            
            <div class="bg-indigo-600 p-4">
                <h3 class="text-white font-black text-base">Registrar Abono Global</h3>
                <p class="text-indigo-100 text-xs font-bold mt-0.5" x-text="distSeleccionada?.nombre"></p>
                <p class="text-indigo-200 text-[11px] font-mono" x-text="'Ref Oficial: ' + distSeleccionada?.ref"></p>
            </div>

            <form novalidate :action="'{{ url('cajero/abonos/distribuidora') }}/' + distSeleccionada?.id" method="POST" class="p-4 space-y-3.5">
                @csrf
                
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cuota Quincenal:</span>
                        <span class="text-white font-mono font-bold" x-text="'$' + Number(distSeleccionada?.cuota).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Multas:</span>
                        <span class="text-rose-400 font-mono font-bold" x-text="'$' + Number(distSeleccionada?.multas).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1 font-bold">
                        <span class="text-slate-300">Adeudo Total:</span>
                        <span class="text-emerald-400 font-mono" x-text="'$' + Number(distSeleccionada?.total).toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Referencia de Pago a Verificar</label>
                    <input type="text" name="referencia_pago" :value="distSeleccionada?.ref" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white font-mono text-sm mt-1 focus:ring-2 focus:ring-indigo-500 uppercase text-center font-bold">
                    <span class="text-[10px] text-slate-500 block mt-0.5">Debe coincidir con la referencia bancaria oficial.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required placeholder="0.00"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-3 text-indigo-300 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-indigo-500">
                        <option value="transferencia">Transferencia / SPEI Bancario</option>
                        <option value="tarjeta">Tarjeta de Débito / Crédito</option>
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

