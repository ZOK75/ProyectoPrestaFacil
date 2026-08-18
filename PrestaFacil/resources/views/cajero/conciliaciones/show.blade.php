@extends('layouts.app')

@section('title', 'Detalle de Conciliación - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.conciliaciones.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a Conciliaciones
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden space-y-4">
        
        @if($conciliacion->estado === 'pendiente')
            <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-500"></div>
        @elseif($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada')
            <div class="absolute top-0 left-0 w-full h-1.5 bg-emerald-500"></div>
        @else
            <div class="absolute top-0 left-0 w-full h-1.5 bg-rose-500"></div>
        @endif

        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
            <div>
                <h1 class="text-sm font-black text-white">Conciliación #{{ substr($conciliacion->id, 0, 8) }}</h1>
                <span class="text-[10px] font-mono text-slate-400">{{ $conciliacion->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            
            @if($conciliacion->estado === 'pendiente')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 uppercase border border-amber-500/30">Pendiente</span>
            @elseif($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-300 uppercase border border-emerald-500/30">Conciliado</span>
            @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-300 uppercase border border-rose-500/30">Rechazada</span>
            @endif
        </div>

        <div class="space-y-3 text-xs">
            @if($conciliacion->distribuidora)
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Distribuidora</span>
                    <span class="text-white font-bold block">{{ $conciliacion->distribuidora->name }}</span>
                    <span class="text-[10px] font-mono text-indigo-400">{{ $conciliacion->distribuidora->referenciaPago() }}</span>
                </div>
            @endif

            @if($conciliacion->prestamo)
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Préstamo / Cliente</span>
                    <span class="font-mono text-indigo-300 font-bold block">{{ $conciliacion->prestamo->referencia }}</span>
                    <span class="text-slate-300">{{ $conciliacion->prestamo->cliente->nombre ?? '' }}</span>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2 bg-slate-950 p-3 rounded-xl border border-slate-800">
                <div>
                    <span class="text-[9px] font-bold text-slate-500 uppercase block">Ref. Errónea (Capturada)</span>
                    <span class="font-mono text-rose-400 font-bold">{{ $conciliacion->referencia_original ?: 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-[9px] font-bold text-slate-500 uppercase block">Ref. Conciliación (Real)</span>
                    <span class="font-mono text-emerald-400 font-bold">{{ $conciliacion->referencia_conciliacion ?: 'N/A' }}</span>
                </div>
            </div>
            
            <div class="flex gap-3 p-3 bg-slate-950/70 rounded-xl border border-slate-800">
                <div class="flex-1">
                    <span class="text-[9px] font-bold text-slate-500 uppercase block">Monto Capturado</span>
                    <span class="font-mono text-rose-400 font-bold line-through">${{ number_format($conciliacion->monto_original, 2) }}</span>
                </div>
                <div class="flex-1">
                    <span class="text-[9px] font-bold text-slate-500 uppercase block">Monto Real Conciliado</span>
                    <span class="font-mono text-emerald-400 font-black text-base">${{ number_format($conciliacion->monto_corregido, 2) }}</span>
                </div>
            </div>

            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Motivo Detallado</span>
                <p class="text-slate-300 bg-slate-950 p-3 rounded-xl border border-slate-800 leading-relaxed">{{ $conciliacion->motivo }}</p>
            </div>
            
            @if($conciliacion->evidencia_path)
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Evidencia / Ficha Adjunta</span>
                    <a href="{{ Storage::url($conciliacion->evidencia_path) }}" target="_blank" class="text-cyan-400 text-xs font-bold hover:underline flex items-center gap-1.5 p-2.5 bg-slate-950 rounded-xl border border-slate-800">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        Ver Documento de Evidencia
                    </a>
                </div>
            @endif

            <!-- Bloque de Auditoría y Resolución -->
            @if($conciliacion->estado !== 'pendiente')
                <div class="mt-4 p-4 rounded-xl {{ ($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada') ? 'bg-emerald-900/20 border border-emerald-500/30' : 'bg-rose-900/20 border border-rose-500/30' }} space-y-1.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 {{ ($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada') ? 'text-emerald-400' : 'text-rose-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-black {{ ($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada') ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ ($conciliacion->estado === 'conciliado' || $conciliacion->estado === 'aprobada') ? 'Conciliación Aplicada y Aprobada' : 'Conciliación Rechazada' }}
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-300">
                        <strong>Aceptado por:</strong> {{ $conciliacion->conciliadoPor->name ?? $conciliacion->autorizador->name ?? 'Usuario Coordinador/Gerente' }} ({{ $conciliacion->autorizador_rol ?? 'Coordinador' }})
                    </div>
                    <div class="text-[10px] font-mono text-slate-400">
                        <strong>Fecha / Timestamp:</strong> {{ $conciliacion->conciliado_at ? $conciliacion->conciliado_at->format('d/m/Y H:i:s') : ($conciliacion->resolved_at ? $conciliacion->resolved_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s')) }}
                    </div>
                    @if($conciliacion->observaciones_resolucion)
                        <div class="text-[11px] text-white pt-1 border-t border-slate-800/80">
                            <strong>Observaciones:</strong> {{ $conciliacion->observaciones_resolucion }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
