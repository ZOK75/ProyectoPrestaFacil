@extends('layouts.app')

@section('title', 'Bandeja de Autorizaciones')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <h1 class="text-lg font-black text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Bandeja de Autorizaciones
        </h1>
        <p class="text-xs text-slate-400 mt-1">Autorización de conciliaciones, modificaciones y bloqueos por morosidad.</p>
    </div>

    @if($solicitudes->isEmpty())
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
            <div class="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <p class="text-sm text-slate-400">No hay solicitudes pendientes por el momento.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($solicitudes as $s)
                <a href="{{ route('autorizaciones.show', $s) }}" class="block bg-slate-900 border {{ $s->esPendiente() ? 'border-amber-500/30' : 'border-slate-800' }} hover:border-indigo-500/50 rounded-2xl p-4 shadow-xl transition-all">
                    
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            @if($s->tipo === 'conciliacion_manual')
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-cyan-500/20 text-cyan-400 uppercase border border-cyan-500/30">Conciliación</span>
                            @elseif($s->tipo === 'modificacion_datos')
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-purple-500/20 text-purple-400 uppercase border border-purple-500/30">Modificar Datos</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-slate-500/20 text-slate-400 uppercase border border-slate-500/30">{{ $s->tipo }}</span>
                            @endif
                        </div>
                        
                        @if($s->estado === 'pendiente')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-400 uppercase border border-amber-500/30 animate-pulse">Pendiente</span>
                        @elseif($s->estado === 'aprobada')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-400 uppercase border border-emerald-500/30">Aprobada</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-rose-500/20 text-rose-400 uppercase border border-rose-500/30">Rechazada</span>
                        @endif
                    </div>

                    <p class="text-xs font-bold text-white mb-1">{{ Str::limit($s->motivo, 60) }}</p>
                    
                    <div class="flex justify-between items-end mt-3">
                        <div class="text-[10px] text-slate-500">
                            Por: <span class="font-bold text-slate-400">{{ $s->solicitante->name }}</span><br>
                            Sucursal: {{ $s->sucursal->nombre ?? 'N/A' }}
                        </div>
                        <span class="text-[9px] font-mono text-slate-500">{{ $s->created_at->diffForHumans() }}</span>
                    </div>
                </a>
            @endforeach
            
            <div class="mt-4">
                {{ $solicitudes->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
