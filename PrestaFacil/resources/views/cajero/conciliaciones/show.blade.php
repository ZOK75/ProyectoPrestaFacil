@extends('layouts.app')

@section('title', 'Detalle de Conciliación - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.conciliaciones.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
        
        @if($conciliacion->estado === 'pendiente')
            <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
        @elseif($conciliacion->estado === 'aprobada')
            <div class="absolute top-0 left-0 w-full h-1 bg-emerald-500"></div>
        @else
            <div class="absolute top-0 left-0 w-full h-1 bg-rose-500"></div>
        @endif

        <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-800">
            <h1 class="text-sm font-black text-white">Detalle de Solicitud #{{ $conciliacion->id }}</h1>
            
            @if($conciliacion->estado === 'pendiente')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-400 uppercase border border-amber-500/30">En Revisión</span>
            @elseif($conciliacion->estado === 'aprobada')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 uppercase border border-emerald-500/30">Aprobada</span>
            @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 uppercase border border-rose-500/30">Rechazada</span>
            @endif
        </div>

        <div class="space-y-4 text-sm">
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block">Préstamo Afectado</span>
                <span class="font-mono text-indigo-300 font-bold block">{{ $conciliacion->prestamo->referencia }}</span>
                <span class="text-slate-300 text-xs">{{ $conciliacion->prestamo->cliente->nombre }}</span>
            </div>
            
            <div class="flex gap-4 p-3 bg-slate-950/50 rounded-xl border border-slate-800/60">
                <div class="flex-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Capturado (Error)</span>
                    <span class="font-mono text-rose-400 font-bold line-through">${{ number_format($conciliacion->monto_original, 2) }}</span>
                </div>
                <div class="flex-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Debe Ser (Real)</span>
                    <span class="font-mono text-emerald-400 font-bold text-lg">${{ number_format($conciliacion->monto_corregido, 2) }}</span>
                </div>
            </div>

            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Motivo Explicado</span>
                <p class="text-slate-300 bg-slate-950 p-3 rounded-xl text-xs">{{ $conciliacion->motivo }}</p>
            </div>
            
            @if($conciliacion->evidencia_path)
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Evidencia Adjunta</span>
                    <a href="{{ Storage::url($conciliacion->evidencia_path) }}" target="_blank" class="text-cyan-400 text-xs hover:underline flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        Ver imagen de evidencia
                    </a>
                </div>
            @endif
            
            <div class="pt-4 border-t border-slate-800">
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Fecha de Solicitud</span>
                <span class="text-slate-300 text-xs">{{ $conciliacion->created_at->format('d/m/Y H:i:s') }}</span>
            </div>

            @if($conciliacion->estado !== 'pendiente')
                <div class="mt-4 p-4 rounded-xl {{ $conciliacion->estado === 'aprobada' ? 'bg-emerald-900/20 border border-emerald-500/30' : 'bg-rose-900/20 border border-rose-500/30' }}">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Resolución por: {{ $conciliacion->autorizador->name ?? 'Sistema' }} ({{ $conciliacion->autorizador_rol }})</span>
                    @if($conciliacion->observaciones_resolucion)
                        <p class="text-white text-xs mt-1 font-bold">{{ $conciliacion->observaciones_resolucion }}</p>
                    @endif
                    <span class="text-slate-500 text-[10px] block mt-2">Resuelto el {{ $conciliacion->resolved_at->format('d/m/Y H:i:s') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
