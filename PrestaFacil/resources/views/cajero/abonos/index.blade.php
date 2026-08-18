@extends('layouts.app')

@section('title', 'Recepción de Abonos por Distribuidora - Cajero')

@section('content')
<div class="max-w-xl mx-auto space-y-4 pb-8" x-data="{ pagoModalOpen: false, distSeleccionada: null }">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
        <a href="{{ route('cajero.conciliaciones.index') }}" class="text-xs font-bold text-cyan-400 hover:text-cyan-300 inline-flex items-center gap-1">
            Módulo de Conciliaciones &rarr;
        </a>
    </div>

    <!-- Buscador de Distribuidoras -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form action="{{ route('cajero.abonos.index') }}" method="GET">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Buscar Distribuidora / Referencia de Pago</label>
            <div class="relative">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Referencia (REF-DIST-...), Nombre o Sucursal..."
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
            No se encontraron distribuidoras activas con ese criterio de búsqueda.
        </div>
    @endif

    <div class="space-y-3">
        @foreach($distribuidoras as $dist)
            @php
                $cuotaQna = $dist->totalCuotaQuincenal();
                $adeudoGlobal = $dist->totalAdeudoGlobal();
                $multasAcum = floatval($dist->multas ?? 0);
            @endphp
            <div class="bg-slate-900 border border-slate-800 hover:border-emerald-500/30 rounded-2xl p-4 shadow-xl transition-all space-y-3">
                <div class="flex justify-between items-start border-b border-slate-800/80 pb-2">
                    <div>
                        <div class="font-mono text-[10px] font-black text-emerald-400 mb-0.5">{{ $dist->referenciaPago() }}</div>
                        <h3 class="text-sm font-black text-white">{{ $dist->name }}</h3>
                        <span class="text-[10px] text-slate-400">{{ $dist->sucursal?->nombre ?? 'Sucursal General' }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300 uppercase border border-emerald-500/30">
                        {{ $dist->prestamos->count() }} Vales Activos
                    </span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 bg-slate-950/80 rounded-xl p-2.5 border border-slate-800 text-center text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Cuota Qna</span>
                        <span class="font-bold text-white font-mono">${{ number_format($cuotaQna, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Multas</span>
                        <span class="font-bold font-mono {{ $multasAcum > 0 ? 'text-rose-400' : 'text-slate-400' }}">${{ number_format($multasAcum, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Adeudo Total</span>
                        <span class="font-bold font-mono text-emerald-400">${{ number_format($adeudoGlobal, 2) }}</span>
                    </div>
                </div>

                <div class="pt-1 flex justify-end gap-2">
                    <button type="button" 
                            @click="distSeleccionada = { id: '{{ $dist->id }}', ref: '{{ $dist->referenciaPago() }}', nombre: '{{ addslashes($dist->name) }}', cuota: {{ $cuotaQna }}, multas: {{ $multasAcum }}, total: {{ $adeudoGlobal }} }; pagoModalOpen = true"
                            class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black px-4 py-2.5 rounded-xl transition-colors shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Registrar Abono
                    </button>
                </div>
            </div>
        @endforeach

        <div class="mt-4">
            {{ $distribuidoras->links() }}
        </div>
    </div>

    <!-- Modal Registro de Pago por Distribuidora -->
    <div x-show="pagoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="pagoModalOpen = false">
            
            <div class="bg-emerald-600 p-4">
                <h3 class="text-white font-black text-lg">Registrar Abono por Distribuidora</h3>
                <p class="text-emerald-100 text-xs font-bold mt-0.5" x-text="distSeleccionada?.nombre"></p>
                <p class="text-emerald-200 text-[11px] font-mono" x-text="'Ref Oficial: ' + distSeleccionada?.ref"></p>
            </div>

            <form :action="'{{ url('cajero/abonos/distribuidora') }}/' + distSeleccionada?.id" method="POST" class="p-4 space-y-3.5">
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
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white font-mono text-sm mt-1 focus:ring-2 focus:ring-emerald-500 uppercase text-center font-bold">
                    <span class="text-[10px] text-slate-500 block mt-0.5">Debe coincidir con la ficha o comprobante del depósito.</span>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required placeholder="0.00"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-3 text-emerald-400 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-center">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500">
                        <option value="transferencia">Transferencia / SPEI Bancario</option>
                        <option value="efectivo">Efectivo en Ventanilla</option>
                        <option value="tarjeta">Tarjeta de Débito / Crédito</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" placeholder="Ficha bancaria, número de guía..." class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="pagoModalOpen = false" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl transition-colors shadow-lg shadow-emerald-500/20">
                        Confirmar Abono
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
