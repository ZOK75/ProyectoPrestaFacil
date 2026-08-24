@extends('layouts.app')

@section('title', 'Módulo de Conciliaciones - Cajero')

@section('content')
<div class="max-w-2xl mx-auto space-y-4 pb-8" x-data="{ 
    modalOpen: false,
    searchMonto: '',
    searchFecha: '',
    buscandoPagos: false,
    pagosEncontrados: [],
    pagoSeleccionado: null,
    
    buscarPagos() {
        if (!this.searchMonto && !this.searchFecha) return;
        this.buscandoPagos = true;
        fetch(`{{ route('cajero.conciliaciones.buscar-pagos') }}?monto=${encodeURIComponent(this.searchMonto)}&fecha=${encodeURIComponent(this.searchFecha)}`)
            .then(res => res.json())
            .then(data => {
                this.pagosEncontrados = data;
                this.buscandoPagos = false;
            })
            .catch(() => {
                this.buscandoPagos = false;
            });
    },
    
    seleccionarPago(pago) {
        this.pagoSeleccionado = pago;
        if (this.$refs.montoOriginalInput) this.$refs.montoOriginalInput.value = pago.monto_abonado;
        if (this.$refs.montoCorregidoInput) this.$refs.montoCorregidoInput.value = pago.monto_abonado;
        if (this.$refs.prestamoIdInput) this.$refs.prestamoIdInput.value = pago.prestamo_id;
        if (this.$refs.pagoIdInput) this.$refs.pagoIdInput.value = pago.id;
        if (this.$refs.fechaPagoInput) this.$refs.fechaPagoInput.value = pago.created_at ? pago.created_at.substring(0, 10) : '';
    }
}">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
        <button @click="modalOpen = true" class="bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black uppercase tracking-wider px-3.5 py-2 rounded-xl shadow-lg shadow-cyan-500/20 flex items-center gap-1.5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva Conciliación
        </button>
    </div>

    <!-- Buscador de Conciliaciones -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form novalidate action="{{ route('cajero.conciliaciones.index') }}" method="GET">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Buscar en Conciliaciones</label>
            <div class="relative">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Referencia, Distribuidora o Motivo..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 pl-4 pr-12 text-white font-mono text-sm focus:ring-2 focus:ring-cyan-500">
                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg px-3 flex items-center justify-center transition-colors text-xs font-bold">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Solicitudes de Conciliación -->
    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2">Registro de Conciliaciones</h2>
    
    @if($conciliaciones->isEmpty())
        <div class="p-8 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
            <div class="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p class="text-sm text-slate-400">No se encontraron solicitudes de conciliación registradas.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($conciliaciones as $c)
                <a href="{{ route('cajero.conciliaciones.show', $c) }}" class="block bg-slate-900 border border-slate-800 hover:border-cyan-500/40 rounded-2xl p-4 shadow-xl transition-all space-y-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-slate-400 block mb-0.5">
                                {{ $c->created_at->format('d/m/Y H:i') }} | Solicitado por: {{ $c->solicitante->name ?? 'Cajero' }}
                            </span>
                            <div class="text-xs font-black text-white">
                                @if($c->distribuidora)
                                    Distribuidora: {{ $c->distribuidora->name }}
                                @elseif($c->prestamo)
                                    Vale: {{ $c->prestamo->referencia }} - {{ $c->prestamo->cliente->nombre ?? '' }}
                                @else
                                    Conciliación de Abono
                                @endif
                            </div>
                        </div>

                        @if($c->estado === 'pendiente')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 uppercase border border-amber-500/30">Pendiente</span>
                        @elseif($c->estado === 'conciliado' || $c->estado === 'aprobada')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300 uppercase border border-emerald-500/30">Conciliado</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-rose-500/20 text-rose-300 uppercase border border-rose-500/30">Rechazada</span>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 bg-slate-950/80 p-2 rounded-xl border border-slate-800 text-[11px]">
                        <div>
                            <span class="text-slate-500 block text-[9px] uppercase">Ref. Original</span>
                            <span class="font-mono text-slate-400 font-bold">{{ $c->referencia_original ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[9px] uppercase">Ref. Conciliación</span>
                            <span class="font-mono text-cyan-300 font-bold">{{ $c->referencia_conciliacion ?: 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1">
                        <div>
                            Monto: <span class="text-white font-mono font-bold">${{ number_format($c->monto_corregido, 2) }}</span>
                        </div>
                        @if($c->conciliado_at)
                            <div class="text-emerald-400 font-bold">
                                Conciliado el {{ $c->conciliado_at->format('d/m/Y H:i') }} por {{ $c->conciliadoPor->name ?? $c->autorizador->name ?? 'Autorizador' }}
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
            
            <div class="mt-4">
                {{ $conciliaciones->links() }}
            </div>
        </div>
    @endif

    <!-- Modal Nueva Solicitud de Conciliación -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-cyan-900/50 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden" @click.away="modalOpen = false">
            
            <div class="bg-cyan-700 p-4">
                <h3 class="text-white font-black text-base">Nueva Solicitud de Conciliación de Pago</h3>
                <p class="text-cyan-100 text-[10px] font-medium mt-0.5">Corrige referencias disconformes o montos bancarios erróneos con autorización y timestamp.</p>
            </div>

            <!-- Búsqueda Inteligente por Monto o Fecha -->
            <div class="p-4 bg-slate-950 border-b border-slate-800 space-y-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">1. Búsqueda Rápida de Abono por Monto o Fecha (Opcional)</span>
                <div class="flex gap-2">
                    <input type="number" step="0.01" x-model="searchMonto" placeholder="Monto ($)" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                    <input type="date" x-model="searchFecha" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                    <button type="button" @click="buscarPagos()" class="px-3 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-xs font-bold transition">
                        Buscar
                    </button>
                </div>

                <template x-if="pagosEncontrados.length > 0">
                    <div class="max-h-28 overflow-y-auto space-y-1 mt-2 border border-slate-800 rounded-lg p-1.5 bg-slate-900 text-xs">
                        <template x-for="pago in pagosEncontrados" :key="pago.id">
                            <div @click="seleccionarPago(pago)" class="p-1.5 hover:bg-slate-800 rounded cursor-pointer flex justify-between items-center text-[11px]">
                                <div>
                                    <span class="text-white font-bold" x-text="pago.folio_pago"></span>
                                    <span class="text-slate-400 text-[10px]" x-text="' (' + (pago.prestamo?.cliente?.nombre || 'Cliente') + ')'"></span>
                                </div>
                                <div class="text-emerald-400 font-mono font-bold" x-text="'$' + Number(pago.monto_abonado).toFixed(2)"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <form novalidate action="{{ route('cajero.conciliaciones.store') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-3 max-h-[60vh] overflow-y-auto">
                @csrf
                <input type="hidden" name="prestamo_id" x-ref="prestamoIdInput">
                <input type="hidden" name="pago_prestamo_id" x-ref="pagoIdInput">
                
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Distribuidora Afectada (Opcional)</label>
                    <select name="distribuidora_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-cyan-500">
                        <option value="">Seleccione distribuidora...</option>
                        @foreach($distribuidoras as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->referenciaPago() }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Referencia Errónea / Original</label>
                        <input type="text" name="referencia_original" placeholder="Ej: REF-DIST-ERRONEA"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2.5 text-rose-300 font-mono text-xs mt-1 focus:ring-2 focus:ring-cyan-500 uppercase">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Referencia Correcta (Conciliación)</label>
                        <input type="text" name="referencia_conciliacion" required placeholder="Ej: REF-DIST-00000001"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2.5 text-emerald-300 font-mono text-xs mt-1 focus:ring-2 focus:ring-cyan-500 uppercase font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Fecha Pago</label>
                        <input type="date" name="fecha_pago" x-ref="fechaPagoInput"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-white text-xs mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Original</label>
                        <input type="number" name="monto_original" step="0.01" required x-ref="montoOriginalInput" placeholder="0.00"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-rose-400 font-mono text-xs font-bold mt-1 text-center">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Real</label>
                        <input type="number" name="monto_corregido" step="0.01" required x-ref="montoCorregidoInput" placeholder="0.00"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-emerald-400 font-mono text-xs font-bold mt-1 text-center">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Motivo y Justificación de la Conciliación</label>
                    <textarea name="motivo" rows="2" required placeholder="Explicar por qué no concordaba la referencia o monto..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>
                
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Comprobante Bancario / Evidencia (Max 5MB)</label>
                    <input type="file" name="evidencia" accept="image/*,.pdf"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-1.5 px-3 text-slate-400 text-xs mt-1 focus:ring-2 focus:ring-cyan-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-cyan-600 file:text-white hover:file:bg-cyan-500">
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="modalOpen = false" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-[2] py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-black text-xs rounded-xl transition-colors shadow-lg shadow-cyan-500/20">
                        Enviar Solicitud a Autorización
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
