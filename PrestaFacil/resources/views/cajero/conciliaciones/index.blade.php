@extends('layouts.app')

@section('title', 'Conciliación Manual - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8" x-data="{ modalOpen: false }">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
        <button @click="modalOpen = true" class="bg-cyan-600 hover:bg-cyan-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg shadow-lg shadow-cyan-500/20">
            + Nueva Solicitud
        </button>
    </div>

    <!-- Lista de Solicitudes Anteriores -->
    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2">Tus Solicitudes de Conciliación</h2>
    
    @if($conciliaciones->isEmpty())
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
            <div class="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p class="text-sm text-slate-400">No has enviado ninguna solicitud de conciliación manual.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($conciliaciones as $c)
                <a href="{{ route('cajero.conciliaciones.show', $c) }}" class="block bg-slate-900 border border-slate-800 hover:border-cyan-500/30 rounded-2xl p-4 shadow-xl transition-all">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-[9px] font-mono font-bold text-slate-400 block mb-0.5">{{ $c->created_at->format('d M, Y H:i') }}</span>
                            <span class="text-xs font-bold text-white">{{ $c->prestamo->referencia }}</span>
                        </div>
                        @if($c->estado === 'pendiente')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-400 uppercase border border-amber-500/30">Pendiente</span>
                        @elseif($c->estado === 'aprobada')
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-400 uppercase border border-emerald-500/30">Aprobada</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-rose-500/20 text-rose-400 uppercase border border-rose-500/30">Rechazada</span>
                        @endif
                    </div>
                    
                    <div class="flex gap-4 text-[10px] text-slate-300">
                        <div>Original: <span class="line-through text-slate-500">${{ number_format($c->monto_original, 2) }}</span></div>
                        <div>Corregido: <span class="font-bold text-cyan-400">${{ number_format($c->monto_corregido, 2) }}</span></div>
                    </div>
                </a>
            @endforeach
            
            <div class="mt-4">
                {{ $conciliaciones->links() }}
            </div>
        </div>
    @endif

    <!-- Modal Nueva Solicitud -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-cyan-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="modalOpen = false">
            
            <div class="bg-cyan-700 p-4">
                <h3 class="text-white font-black text-lg">Solicitar Conciliación Manual</h3>
                <p class="text-cyan-100 text-[10px] font-medium mt-1">Requiere autorización de coordinación para corregir errores de captura.</p>
            </div>

            <form action="{{ route('cajero.conciliaciones.store') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
                @csrf
                
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Préstamo / Vale afectado</label>
                    <select name="prestamo_id" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-sm mt-1 focus:ring-2 focus:ring-cyan-500">
                        <option value="">Seleccione el vale...</option>
                        @foreach($prestamosActivos as $p)
                            <option value="{{ $p->id }}">{{ $p->referencia }} - {{ $p->cliente->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Original (Erróneo)</label>
                        <input type="number" name="monto_original" step="0.01" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-rose-400 font-mono text-sm font-black mt-1 focus:ring-2 focus:ring-cyan-500 text-center">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto Corregido (Real)</label>
                        <input type="number" name="monto_corregido" step="0.01" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-emerald-400 font-mono text-sm font-black mt-1 focus:ring-2 focus:ring-cyan-500 text-center">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Motivo Detallado del Error</label>
                    <textarea name="motivo" rows="3" required placeholder="Ej: Se capturaron $500 pero el cliente pagó $5000, un cero de menos..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>
                
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Evidencia Fotográfica (Opcional, Max 5MB)</label>
                    <input type="file" name="evidencia" accept="image/*"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-slate-400 text-xs mt-1 focus:ring-2 focus:ring-cyan-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-cyan-600 file:text-white hover:file:bg-cyan-500 transition-all">
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="modalOpen = false" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-[2] py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-black text-sm rounded-xl transition-colors shadow-lg shadow-cyan-500/20">
                        Enviar a Autorización
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
