@extends('layouts.app')

@section('title', 'Buscar Folio - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
    </div>

    <!-- Buscador -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form novalidate action="{{ route('cajero.buscar-folio') }}" method="GET">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Buscar por Referencia de Vale</label>
            <div class="relative">
                <input type="text" name="referencia" value="{{ $referencia ?? '' }}" placeholder="Ej: REF-PREVALE-..." required autofocus
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-4 pr-12 text-white font-mono text-sm uppercase focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg px-3 flex items-center justify-center transition-colors">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    @if(isset($referencia) && $prestamo)
        <div class="bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-1 shadow-2xl mt-4 relative overflow-hidden">
            
            <div class="p-4 relative z-10">
                <div class="flex justify-between items-start border-b border-slate-800 pb-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-[10px] font-black text-indigo-400">{{ $prestamo->referencia }}</span>
                            @if($prestamo->esPrevale())
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 uppercase border border-amber-500/30">Prevale</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-cyan-500/20 text-cyan-300 uppercase border border-cyan-500/30">Vale Digital</span>
                            @endif
                        </div>
                        <h2 class="text-lg font-black text-white">{{ $prestamo->cliente->nombre }}</h2>
                        <div class="text-xs text-slate-400">Distribuidora: <span class="text-slate-300 font-semibold">{{ $prestamo->createdBy->name }}</span></div>
                    </div>
                    
                    <div class="text-right">
                        <div class="text-sm font-black text-emerald-400">${{ number_format($prestamo->monto_prestamo, 2) }}</div>
                        <div class="text-[10px] text-slate-500">{{ $prestamo->pagos_totales }} quincenas</div>
                    </div>
                </div>

                <!-- Estado -->
                <div class="mb-4">
                    @if($prestamo->estaPendienteEntrega())
                        <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-indigo-300">Vale Pendiente de Entrega</span>
                                <span class="block text-[10px] text-slate-400">Listo para verificación y transferencia.</span>
                            </div>
                        </div>
                    @elseif($prestamo->estaEntregado())
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-emerald-400">Vale Ya Entregado</span>
                                <span class="block text-[10px] text-slate-400">Entregado el {{ $prestamo->entregado_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-rose-400">Vale Cancelado</span>
                                <span class="block text-[10px] text-slate-400">Esta referencia no es válida.</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Botón de acción -->
                @if($prestamo->estaPendienteEntrega())
                    @if($prestamo->esPrevale())
                        <a href="{{ route('cajero.prevale.verificar', $prestamo) }}" class="block w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-center font-bold text-sm rounded-xl transition-colors">
                            Iniciar Verificación para Entrega
                        </a>
                    @else
                        <a href="{{ route('cajero.vale.verificar', $prestamo) }}" class="block w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-center font-bold text-sm rounded-xl transition-colors">
                            Iniciar Verificación para Entrega
                        </a>
                    @endif
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
