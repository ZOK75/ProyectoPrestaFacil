@extends('layouts.app')

@section('title', 'Módulo de Conciliaciones - Cajero')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 pb-8" x-data="{ 
    tabActiva: 'pagos',
    modalOpen: false,
    distribuidoraSeleccionadaId: '',
    searchMonto: '',
    searchFecha: '',
    buscandoPagos: false,
    pagosEncontrados: [],
    pagoSeleccionado: null,
    
    // Préstamos asignados en la conciliación
    prestamosAsignados: [],
    prestamoSeleccionadoId: '',
    montoAsignarTemp: '',
    
    // Lista de préstamos disponibles
    prestamosDisponibles: {{ Js::from($prestamosActivos->map(fn($p) => [
        'id' => $p->id,
        'referencia' => $p->referencia,
        'cliente' => $p->cliente?->nombre_completo ?? $p->cliente?->nombre ?? 'Cliente',
        'distribuidora_id' => $p->created_by_user_id,
        'cuota_neta' => floatval($p->cuota_quincenal - $p->comisionDistribuidorPorQuincena()),
        'adeudo' => floatval($p->adeudo_pendiente + $p->multas)
    ])) }},

    get prestamosFiltrados() {
        if (!this.distribuidoraSeleccionadaId) return this.prestamosDisponibles;
        return this.prestamosDisponibles.filter(p => p.distribuidora_id == this.distribuidoraSeleccionadaId);
    },

    get totalAsignadoVales() {
        return this.prestamosAsignados.reduce((sum, item) => sum + (parseFloat(item.monto) || 0), 0);
    },

    agregarPrestamo(prestamo) {
        if (!prestamo) return;
        if (this.prestamosAsignados.some(item => item.prestamo_id === prestamo.id)) return;
        
        this.prestamosAsignados.push({
            prestamo_id: prestamo.id,
            folio: prestamo.referencia,
            cliente: prestamo.cliente,
            cuota_neta: prestamo.cuota_neta,
            monto: prestamo.cuota_neta > 0 ? prestamo.cuota_neta : prestamo.adeudo
        });

        if (this.$refs.montoCorregidoInput) {
            this.$refs.montoCorregidoInput.value = this.totalAsignadoVales.toFixed(2);
        }
    },

    quitarPrestamo(index) {
        this.prestamosAsignados.splice(index, 1);
        if (this.$refs.montoCorregidoInput) {
            this.$refs.montoCorregidoInput.value = this.totalAsignadoVales.toFixed(2);
        }
    },
    
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
        if (this.$refs.pagoIdInput) this.$refs.pagoIdInput.value = pago.id;
        if (this.$refs.fechaPagoInput) this.$refs.fechaPagoInput.value = pago.created_at ? pago.created_at.substring(0, 10) : '';
        if (this.$refs.refOriginalInput) this.$refs.refOriginalInput.value = pago.folio_pago;

        if (pago.prestamo) {
            this.prestamosAsignados = [{
                prestamo_id: pago.prestamo.id,
                folio: pago.prestamo.referencia,
                cliente: pago.prestamo.cliente?.nombre || 'Cliente',
                cuota_neta: Number(pago.monto_abonado),
                monto: Number(pago.monto_abonado)
            }];
            if (pago.prestamo.created_by_user_id) {
                this.distribuidoraSeleccionadaId = pago.prestamo.created_by_user_id;
            }
        }
    },

    abrirModalNuevo() {
        this.pagoSeleccionado = null;
        this.prestamosAsignados = [];
        this.modalOpen = true;
    }
}">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
        <button @click="abrirModalNuevo()" class="bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black uppercase tracking-wider px-3.5 py-2 rounded-xl shadow-lg shadow-cyan-500/20 flex items-center gap-1.5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva Conciliación
        </button>
    </div>

    <!-- Selector de Pestañas: Pagos Registrados / Conciliaciones -->
    <div class="flex border-b border-slate-800 gap-2">
        <button type="button" @click="tabActiva = 'pagos'"
            :class="tabActiva === 'pagos' ? 'border-cyan-500 text-cyan-400 font-black' : 'border-transparent text-slate-400 hover:text-slate-200 font-semibold'"
            class="pb-2.5 px-3 border-b-2 text-xs transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Pagos Registrados ({{ $pagosRecientes->total() }})
        </button>
        <button type="button" @click="tabActiva = 'conciliaciones'"
            :class="tabActiva === 'conciliaciones' ? 'border-cyan-500 text-cyan-400 font-black' : 'border-transparent text-slate-400 hover:text-slate-200 font-semibold'"
            class="pb-2.5 px-3 border-b-2 text-xs transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Solicitudes de Conciliación ({{ $conciliaciones->total() }})
        </button>
    </div>

    <!-- PESTAÑA 1: PAGOS REGISTRADOS -->
    <div x-show="tabActiva === 'pagos'" class="space-y-3">
        <!-- Buscador de Pagos -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-xl">
            <form novalidate action="{{ route('cajero.conciliaciones.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="buscar_pago" value="{{ request('buscar_pago') }}" placeholder="Folio de recibo, cliente o folio de vale..."
                    class="flex-1 bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white font-mono text-xs focus:ring-2 focus:ring-cyan-500">
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl px-4 text-xs font-bold transition">
                    Buscar
                </button>
            </form>
        </div>

        @if($pagosRecientes->isEmpty())
            <div class="p-8 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
                <p class="text-sm text-slate-400">No se encontraron pagos registrados.</p>
            </div>
        @else
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-3">Folio Pago</th>
                                <th class="p-3">Fecha / Hora</th>
                                <th class="p-3">Cliente / Vale</th>
                                <th class="p-3 text-right">Monto</th>
                                <th class="p-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
                            @foreach($pagosRecientes as $pago)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="p-3 font-bold text-white">
                                        {{ $pago->folio_pago }}
                                        <span class="block text-[9px] text-slate-500 font-sans">Q{{ $pago->numero_quincena }} • {{ ucfirst($pago->metodo_pago) }}</span>
                                    </td>
                                    <td class="p-3 text-slate-400 font-sans text-[11px]">
                                        {{ $pago->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="p-3 font-sans">
                                        <span class="font-bold text-slate-200 block">{{ $pago->prestamo?->cliente?->nombre_completo ?? $pago->prestamo?->cliente?->nombre ?? 'N/A' }}</span>
                                        <span class="font-mono text-[10px] text-indigo-400">{{ $pago->prestamo?->referencia ?? 'Sin Vale' }}</span>
                                    </td>
                                    <td class="p-3 text-right font-black text-emerald-400 text-sm">
                                        ${{ number_format($pago->monto_abonado, 2) }}
                                    </td>
                                    <td class="p-3 text-center font-sans">
                                        <button type="button" @click="seleccionarPago({{ Js::from($pago) }}); modalOpen = true"
                                            class="px-2.5 py-1 rounded-lg bg-cyan-600/20 hover:bg-cyan-600/40 text-cyan-300 border border-cyan-500/30 text-[10px] font-bold transition">
                                            Conciliar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $pagosRecientes->links() }}
            </div>
        @endif
    </div>

    <!-- PESTAÑA 2: REGISTRO DE CONCILIACIONES -->
    <div x-show="tabActiva === 'conciliaciones'" class="space-y-3">
        <!-- Buscador de Conciliaciones -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-xl">
            <form novalidate action="{{ route('cajero.conciliaciones.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Referencia, Distribuidora o Motivo..."
                    class="flex-1 bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white font-mono text-xs focus:ring-2 focus:ring-cyan-500">
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl px-4 text-xs font-bold transition">
                    Buscar
                </button>
            </form>
        </div>

        @if($conciliaciones->isEmpty())
            <div class="p-8 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
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

                            @if(in_array($c->estado, ['pendiente', 'pendiente_coordinador', 'pendiente_gerencia']))
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

                        <!-- Vales vinculados -->
                        @if(!empty($c->prestamos_asignados))
                            <div class="bg-slate-950 p-2 rounded-xl border border-slate-800/80 text-[10px]">
                                <span class="text-slate-500 block text-[9px] uppercase font-bold mb-1">Vales Ligados:</span>
                                <div class="space-y-1">
                                    @foreach($c->prestamos_asignados as $item)
                                        <div class="flex justify-between text-slate-300 font-mono">
                                            <span>{{ $item['folio'] ?? 'Vale' }} ({{ $item['cliente'] ?? 'Cliente' }})</span>
                                            <span class="text-emerald-400 font-bold">${{ number_format($item['monto'] ?? 0, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1">
                            <div>
                                Monto Conciliado: <span class="text-white font-mono font-bold">${{ number_format($c->monto_corregido, 2) }}</span>
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
    </div>

    <!-- Modal Nueva Solicitud de Conciliación -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-cyan-900/50 rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col" @click.away="modalOpen = false">
            
            <div class="bg-cyan-700 p-4 shrink-0">
                <h3 class="text-white font-black text-base">Nueva Solicitud de Conciliación de Pago</h3>
                <p class="text-cyan-100 text-[10px] font-medium mt-0.5">Liga un pago a uno o varios préstamos por su folio. Si la fecha es previa al corte, remueve multas, preserva comisiones y otorga puntos.</p>
            </div>

            <form novalidate action="{{ route('cajero.conciliaciones.store') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-3.5 overflow-y-auto flex-1 text-xs">
                @csrf
                <input type="hidden" name="pago_prestamo_id" x-ref="pagoIdInput">
                <input type="hidden" name="prestamos_asignados" :value="JSON.stringify(prestamosAsignados)">

                <!-- 1. Distribuidora -->
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block">1. Distribuidora Asociada</label>
                    <select name="distribuidora_id" x-model="distribuidoraSeleccionadaId" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-cyan-500">
                        <option value="">Seleccione distribuidora (o libre)...</option>
                        @foreach($distribuidoras as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->referenciaPago() }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Ligar Vales / Préstamos por Folio -->
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-slate-300 uppercase block">2. Vales / Préstamos a Conciliar</span>
                        <span class="text-[10px] text-cyan-400 font-mono font-bold" x-text="'Total Asignado: $' + totalAsignadoVales.toFixed(2)"></span>
                    </div>

                    <div class="flex gap-2">
                        <select x-model="prestamoSeleccionadoId" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                            <option value="">Seleccionar vale activo...</option>
                            <template x-for="p in prestamosFiltrados" :key="p.id">
                                <option :value="p.id" x-text="p.referencia + ' - ' + p.cliente + ' (Cuota: $' + p.cuota_neta.toFixed(2) + ')'"></option>
                            </template>
                        </select>
                        <button type="button" @click="agregarPrestamo(prestamosDisponibles.find(p => p.id == prestamoSeleccionadoId))"
                            class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Agregar Vale
                        </button>
                    </div>

                    <!-- Lista de Vales Agregados -->
                    <template x-if="prestamosAsignados.length > 0">
                        <div class="space-y-1.5 mt-2 border-t border-slate-800 pt-2">
                            <template x-for="(item, index) in prestamosAsignados" :key="item.prestamo_id">
                                <div class="bg-slate-900 p-2 rounded-lg border border-slate-800 flex items-center justify-between gap-2">
                                    <div class="flex-1">
                                        <span class="font-mono font-bold text-white block text-[11px]" x-text="item.folio"></span>
                                        <span class="text-slate-400 text-[10px]" x-text="item.cliente"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] text-slate-400 font-bold">$</span>
                                        <input type="number" step="0.01" x-model.number="item.monto" placeholder="0.00"
                                            class="w-24 bg-slate-950 border border-slate-700 rounded p-1 text-emerald-400 font-mono text-xs font-bold text-right">
                                        <button type="button" @click="quitarPrestamo(index)" class="text-rose-400 hover:text-rose-300 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- 3. Datos del Comprobante Bancario -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Referencia Original / Capturada</label>
                        <input type="text" name="referencia_original" x-ref="refOriginalInput" placeholder="Ej: REF-DIST-ERRONEA"
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
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Fecha Pago Real</label>
                        <input type="date" name="fecha_pago" required x-ref="fechaPagoInput"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-white text-xs mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Original</label>
                        <input type="number" name="monto_original" step="0.01" required x-ref="montoOriginalInput" placeholder="0.00"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-rose-400 font-mono text-xs font-bold mt-1 text-center">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Real Total</label>
                        <input type="number" name="monto_corregido" step="0.01" required x-ref="montoCorregidoInput" placeholder="0.00"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-2 text-emerald-400 font-mono text-xs font-bold mt-1 text-center">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Motivo y Justificación</label>
                    <textarea name="motivo" rows="2" required placeholder="Justificar el enlace de préstamos y comprobante..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>
                
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Comprobante Bancario / Ficha (Opcional)</label>
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
