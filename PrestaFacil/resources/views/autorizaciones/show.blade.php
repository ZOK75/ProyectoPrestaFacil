@extends('layouts.app')

@section('title', 'Detalle de Solicitud')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8" x-data="{ rechazarModalOpen: false, aprobarModalOpen: false }">

    <div class="flex items-center justify-between">
        <a href="{{ route('autorizaciones.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a la Bandeja
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
        
        @if($solicitud->estado === 'pendiente')
            <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
        @elseif($solicitud->estado === 'aprobada')
            <div class="absolute top-0 left-0 w-full h-1 bg-emerald-500"></div>
        @else
            <div class="absolute top-0 left-0 w-full h-1 bg-rose-500"></div>
        @endif

        <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-800">
            <div>
                <h1 class="text-sm font-black text-white flex items-center gap-2">
                    Solicitud #{{ $solicitud->id }}
                </h1>
                <span class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">{{ str_replace('_', ' ', $solicitud->tipo) }}</span>
            </div>
            
            @if($solicitud->estado === 'pendiente')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-400 uppercase border border-amber-500/30">Pendiente</span>
            @elseif($solicitud->estado === 'aprobada')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 uppercase border border-emerald-500/30">Aprobada</span>
            @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 uppercase border border-rose-500/30">Rechazada</span>
            @endif
        </div>

        <div class="space-y-4 text-sm">
            
            <!-- Datos de Quien Solicita -->
            <div class="flex items-center gap-3 bg-slate-950/50 p-3 rounded-xl border border-slate-800/60">
                <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-300 font-bold text-xs">
                    {{ strtoupper(substr($solicitud->solicitante->name, 0, 2)) }}
                </div>
                <div>
                    <span class="block text-xs font-bold text-white">{{ $solicitud->solicitante->name }}</span>
                    <span class="block text-[10px] text-slate-400">{{ $solicitud->sucursal->nombre ?? 'Sucursal Central' }} - {{ $solicitud->solicitante->rol->nombre }}</span>
                </div>
                <span class="ml-auto text-[9px] font-mono text-slate-500">{{ $solicitud->created_at->format('d/m H:i') }}</span>
            </div>

            <!-- Motivo de la Solicitud -->
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Motivo Explicado</span>
                <p class="text-slate-300 bg-slate-950 p-3 rounded-xl text-xs">{{ $solicitud->motivo }}</p>
            </div>
            
            <!-- Detalle de Cambios Propuestos -->
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-2">Detalle de Cambios</span>
                
                @if($solicitud->tipo === 'conciliacion_manual')
                    <div class="flex gap-4 p-3 bg-slate-950/50 rounded-xl border border-slate-800/60">
                        <div class="flex-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">Capturado (Error)</span>
                            <span class="font-mono text-rose-400 font-bold line-through">${{ number_format($solicitud->datos_originales['monto_original'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">Debe Ser (Real)</span>
                            <span class="font-mono text-emerald-400 font-bold text-lg">${{ number_format($solicitud->datos_propuestos['monto_corregido'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                @elseif($solicitud->tipo === 'modificacion_datos')
                    <div class="bg-slate-950/50 rounded-xl border border-slate-800/60 overflow-hidden text-xs">
                        @foreach($solicitud->datos_propuestos as $campo => $nuevoValor)
                            @if(isset($solicitud->datos_originales[$campo]) && $solicitud->datos_originales[$campo] != $nuevoValor)
                                <div class="p-2 border-b border-slate-800 last:border-0 grid grid-cols-3 gap-2 items-center">
                                    <div class="font-bold text-[10px] text-slate-500 uppercase col-span-3 pb-1 border-b border-slate-800/50">{{ str_replace('_', ' ', $campo) }}</div>
                                    <div class="text-rose-400 line-through truncate" title="{{ $solicitud->datos_originales[$campo] }}">{{ $solicitud->datos_originales[$campo] }}</div>
                                    <div class="text-center text-slate-600">&rarr;</div>
                                    <div class="text-emerald-400 font-bold truncate" title="{{ $nuevoValor }}">{{ $nuevoValor }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @if($solicitud->evidencia_path)
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Evidencia Adjunta</span>
                    <a href="{{ Storage::url($solicitud->evidencia_path) }}" target="_blank" class="text-indigo-400 text-xs hover:underline flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        Ver evidencia adjunta
                    </a>
                </div>
            @endif
            
            @if($solicitud->estado !== 'pendiente')
                <div class="mt-4 p-4 rounded-xl {{ $solicitud->estado === 'aprobada' ? 'bg-emerald-900/20 border border-emerald-500/30' : 'bg-rose-900/20 border border-rose-500/30' }}">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Resolución por: {{ $solicitud->autorizador->name ?? 'Sistema' }} ({{ $solicitud->autorizador_rol }})</span>
                    @if($solicitud->observaciones_resolucion)
                        <p class="text-white text-xs mt-1 font-bold">{{ $solicitud->observaciones_resolucion }}</p>
                    @endif
                    <span class="text-slate-500 text-[10px] block mt-2">Resuelto el {{ $solicitud->resolved_at->format('d/m/Y H:i:s') }}</span>
                </div>
            @endif
        </div>

        @if($solicitud->estado === 'pendiente')
            <div class="grid grid-cols-2 gap-3 mt-6 pt-4 border-t border-slate-800">
                <button @click="rechazarModalOpen = true" class="py-3 bg-rose-600 hover:bg-rose-500 text-white font-black text-sm rounded-xl shadow-lg shadow-rose-600/20 transition-colors">
                    Rechazar
                </button>
                <button @click="aprobarModalOpen = true" class="py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-600/20 transition-colors">
                    Aprobar
                </button>
            </div>
        @endif
    </div>

    <!-- Modal Rechazar -->
    <div x-show="rechazarModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-rose-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="rechazarModalOpen = false">
            <div class="bg-rose-600 p-4"><h3 class="text-white font-black text-lg">Rechazar Solicitud</h3></div>
            <form novalidate action="{{ route('autorizaciones.rechazar', $solicitud) }}" method="POST" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Motivo del Rechazo (Obligatorio)</label>
                    <textarea name="motivo" required rows="3" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-sm focus:ring-2 focus:ring-rose-500"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="rechazarModalOpen = false" class="flex-1 py-3 bg-slate-800 text-white font-bold text-sm rounded-xl">Cancelar</button>
                    <button type="submit" class="flex-1 py-3 bg-rose-600 text-white font-black text-sm rounded-xl">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Aprobar -->
    <div x-show="aprobarModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="aprobarModalOpen = false">
            <div class="bg-emerald-600 p-4"><h3 class="text-white font-black text-lg">Aprobar Solicitud</h3></div>
            <form novalidate action="{{ route('autorizaciones.aprobar', $solicitud) }}" method="POST" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
                <p class="text-[10px] text-slate-400 text-center">Al aprobar, los cambios se aplicarán automáticamente en el sistema y se notificará a la cajera.</p>
                <div class="flex gap-2">
                    <button type="button" @click="aprobarModalOpen = false" class="flex-1 py-3 bg-slate-800 text-white font-bold text-sm rounded-xl">Cancelar</button>
                    <button type="submit" class="flex-[2] py-3 bg-emerald-600 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-500/20">Aprobar y Aplicar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
