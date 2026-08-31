@extends('layouts.app')

@section('title', 'Detalle de Conciliación - Gerencia')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" x-data="{ openModalDecidir: false, modalAccion: 'aprobar' }">

    <!-- Header y Regresar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('gerente.conciliaciones.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Regresar a Conciliaciones
        </a>

        <div class="flex items-center gap-2">
            @if(in_array($conciliacion->estado, ['pendiente', 'pendiente_gerencia', 'pendiente_coordinador']))
                <button @click="modalAccion = 'aprobar'; openModalDecidir = true" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition">
                    Aprobar Conciliación
                </button>
                <button @click="modalAccion = 'rechazar'; openModalDecidir = true" 
                        class="px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition">
                    Rechazar Conciliación
                </button>
            @endif
        </div>
    </div>

    <!-- Tarjeta Principal de Información -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                        Conciliación #{{ $conciliacion->id }}
                    </span>
                    @if($conciliacion->estado === 'conciliado')
                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Conciliado / Aprobado
                        </span>
                    @elseif($conciliacion->estado === 'rechazada')
                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200 uppercase">
                            Rechazada
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                            Pendiente de Autorización
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-2 font-mono">
                    {{ $conciliacion->referencia_conciliacion ?: 'Sin referencia' }}
                </h1>
                <p class="text-slate-500 text-xs mt-0.5">Registrada el {{ $conciliacion->created_at->format('d/m/Y H:i:s') }}</p>
            </div>

            <div class="text-right shrink-0 bg-emerald-50/60 p-4 rounded-2xl border border-emerald-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Monto a Conciliar</span>
                <span class="text-2xl font-black text-emerald-600 font-mono">${{ number_format($conciliacion->monto_corregido, 2) }}</span>
            </div>
        </div>

        <!-- Cuadrícula de Datos Clave -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span class="text-slate-500 font-bold uppercase tracking-wider block">Cajero Solicitante</span>
                <p class="font-extrabold text-slate-900 text-sm">{{ $conciliacion->solicitante?->name ?? 'Cajero' }}</p>
                <p class="text-slate-500">{{ $conciliacion->solicitante?->sucursal?->nombre ?? 'Sucursal' }}</p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span class="text-slate-500 font-bold uppercase tracking-wider block">Distribuidora</span>
                <p class="font-extrabold text-slate-900 text-sm">{{ $conciliacion->distribuidora?->name ?? 'N/A' }}</p>
                <p class="font-mono text-slate-500">Ref: {{ $conciliacion->distribuidora?->referenciaPago() ?? 'N/A' }}</p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span class="text-slate-500 font-bold uppercase tracking-wider block">Fecha Pago Bancario</span>
                <p class="font-mono font-black text-slate-900 text-sm">{{ $conciliacion->fecha_pago ? $conciliacion->fecha_pago->format('d/m/Y') : 'N/A' }}</p>
                <p class="uppercase font-bold text-slate-500">{{ $conciliacion->metodo_pago }}</p>
            </div>
        </div>

        <!-- Desglose de Vales Ligados por Folio -->
        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Vales Asociados a la Conciliación</span>
            
            @if(!empty($conciliacion->prestamos_asignados))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($conciliacion->prestamos_asignados as $item)
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex justify-between items-center">
                            <div>
                                <span class="font-mono font-bold text-slate-900 text-xs block">{{ $item['folio'] ?? 'Folio Vale' }}</span>
                                <span class="text-slate-500 text-[11px]">{{ $item['cliente'] ?? 'Cliente' }}</span>
                            </div>
                            <span class="font-mono font-black text-emerald-600 text-xs">${{ number_format($item['monto'] ?? 0, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @elseif($conciliacion->prestamo)
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex justify-between items-center">
                    <div>
                        <span class="font-mono font-bold text-slate-900 text-xs block">{{ $conciliacion->prestamo->referencia }}</span>
                        <span class="text-slate-500 text-[11px]">{{ $conciliacion->prestamo->cliente?->nombre_completo ?? '' }}</span>
                    </div>
                    <span class="font-mono font-black text-emerald-600 text-xs">${{ number_format($conciliacion->monto_corregido, 2) }}</span>
                </div>
            @else
                <p class="text-slate-400 text-xs italic">Abono general a cuenta de distribuidora sin vales específicos desglosados.</p>
            @endif
        </div>

        <!-- Motivo y Observaciones de Resolución -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-1">
                <span class="text-slate-500 font-bold uppercase tracking-wider block">Motivo Solicitado</span>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-slate-700 leading-relaxed min-h-[80px]">
                    "{{ $conciliacion->motivo }}"
                </div>
            </div>

            <div class="space-y-1">
                <span class="text-slate-500 font-bold uppercase tracking-wider block">Resolución de Gerencia</span>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-slate-700 leading-relaxed min-h-[80px]">
                    @if($conciliacion->observaciones_resolucion || $conciliacion->notas_resolucion)
                        <p class="font-semibold text-slate-900">{{ $conciliacion->observaciones_resolucion ?: $conciliacion->notas_resolucion }}</p>
                        @if($conciliacion->autorizador)
                            <p class="text-[11px] text-slate-500 mt-1">Dictaminado por {{ $conciliacion->autorizador->name }} el {{ $conciliacion->resolved_at?->format('d/m/Y H:i') }}</p>
                        @endif
                    @else
                        <span class="text-slate-400 italic">Pendiente de resolución gerencial.</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Comprobante / Evidencia Adjunta -->
        @if($conciliacion->evidencia_path || $conciliacion->comprobante_path)
            <div class="space-y-2 pt-4 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Comprobante de Pago Adjunto</span>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 inline-flex items-center gap-3">
                    <svg class="w-8 h-8 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Ficha / Comprobante Bancario</span>
                        <a href="{{ route('conciliaciones.archivo', $conciliacion) }}" target="_blank" class="text-xs text-emerald-600 font-bold hover:underline">
                            Ver Archivo Original &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Bitácora de Auditoría del Proceso -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Trazabilidad y Logs de Auditoría
        </h2>

        @if($logs->isEmpty())
            <p class="text-xs text-slate-400">No hay registros de auditoría asociados.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($logs as $log)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] {{ $log->tipo_operacion === 'CONCILIACION_VALIDADA' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $log->tipo_operacion }}
                                </span>
                                <span class="font-bold text-slate-900">{{ $log->descripcion }}</span>
                            </div>
                            <div class="text-slate-400 text-[11px] mt-0.5">
                                Usuario: {{ $log->usuario?->name ?? 'Sistema' }} ({{ $log->user_rol }}) &bull; IP: {{ $log->ip_address }}
                            </div>
                        </div>
                        <span class="text-slate-400 font-mono text-[11px] shrink-0">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal para Decidir Conciliación -->
    <div x-show="openModalDecidir" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition>
        
        <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 text-left space-y-4"
             @click.outside="openModalDecidir = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900" x-text="modalAccion === 'aprobar' ? 'Aprobar Conciliación' : 'Rechazar Conciliación'"></h3>
                <button @click="openModalDecidir = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form novalidate action="{{ route('gerente.conciliaciones.decidir', $conciliacion) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="accion" :value="modalAccion">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" x-text="modalAccion === 'aprobar' ? 'Observaciones / Notas (Opcional)' : 'Motivo del Rechazo (Obligatorio)'"></label>
                    <textarea name="observaciones" 
                              rows="3" 
                              :required="modalAccion === 'rechazar'"
                              :placeholder="modalAccion === 'aprobar' ? 'Escribe notas de la aprobación...' : 'Indica la razón del rechazo...'"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="openModalDecidir = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :class="modalAccion === 'aprobar' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'"
                            class="px-5 py-2 rounded-xl text-xs font-bold shadow-md transition"
                            x-text="modalAccion === 'aprobar' ? 'Confirmar Aprobación' : 'Confirmar Rechazo'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
