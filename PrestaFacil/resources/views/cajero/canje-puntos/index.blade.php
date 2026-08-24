@extends('layouts.app')

@section('title', 'Canje de Puntos - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8" x-data="{ modalOpen: false, puntosCanjear: {{ $config->obtenerMultiploCanje() }}, valorPunto: {{ $config->obtenerValorPunto() }} }">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
    </div>

    <!-- Buscador de Distribuidora -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
            Canje de Puntos a Dinero
        </h2>
        
        <form novalidate action="{{ route('cajero.canje-puntos.index') }}" method="GET">
            <div class="relative">
                <select name="distribuidora_id" onchange="this.form.submit()" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-4 pr-10 text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all appearance-none">
                    <option value="">Seleccione una distribuidora...</option>
                    @foreach($distribuidoras as $d)
                        <option value="{{ $d->id }}" {{ (isset($distribuidora) && $distribuidora->id == $d->id) ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Estado de la Distribuidora y Realizar Canje -->
    @if(isset($distribuidora))
        <div class="bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-5 shadow-2xl mt-2 text-center">
            
            <div class="w-16 h-16 rounded-3xl bg-amber-500/10 flex items-center justify-center mx-auto mb-3 text-amber-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            </div>
            
            <h3 class="text-white font-black text-lg mb-1">{{ $distribuidora->name }}</h3>
            
            <div class="mt-4 p-4 bg-slate-950 rounded-xl border border-slate-800 flex justify-between items-center">
                <div class="text-left">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Puntos Acumulados</span>
                    <span class="block text-3xl font-black text-amber-400">{{ number_format($distribuidora->puntos) }} <span class="text-sm font-bold">pts</span></span>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Equivalente</span>
                    <span class="block text-2xl font-black text-emerald-400">${{ number_format($distribuidora->puntos * $config->obtenerValorPunto(), 2) }}</span>
                </div>
            </div>
            
            <div class="mt-3 text-[10px] text-slate-400 text-left px-2">
                <strong>Regla Actual:</strong> Cada {{ $config->obtenerMultiploCanje() }} puntos se pueden canjear. Valor del punto: ${{ number_format($config->obtenerValorPunto(), 2) }} MXN.
            </div>

            @if($distribuidora->puntos >= $config->obtenerMultiploCanje())
                <button @click="modalOpen = true" class="mt-6 w-full py-4 bg-amber-600 hover:bg-amber-500 text-white font-black text-sm rounded-xl shadow-lg shadow-amber-600/30 transition-colors uppercase tracking-wider">
                    Iniciar Canje de Puntos
                </button>
            @else
                <div class="mt-6 w-full py-3 bg-slate-800 text-slate-500 text-center font-bold text-xs rounded-xl cursor-not-allowed">
                    Puntos insuficientes para canjear (Mínimo: {{ $config->obtenerMultiploCanje() }})
                </div>
            @endif

        </div>

        <!-- Modal Confirmación Canje -->
        <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
            <div class="bg-slate-900 border border-amber-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="modalOpen = false">
                
                <div class="bg-amber-600 p-4 text-center">
                    <h3 class="text-white font-black text-lg">Canjear Puntos</h3>
                </div>

                <form novalidate action="{{ route('cajero.canje-puntos.store') }}" method="POST" class="p-5 space-y-4 text-center">
                    @csrf
                    <input type="hidden" name="distribuidora_id" value="{{ $distribuidora->id }}">
                    
                    <p class="text-xs text-slate-300">¿Cuántos puntos desea canjear {{ $distribuidora->name }}?</p>
                    
                    <div>
                        <input type="number" name="puntos_canjear" x-model="puntosCanjear" 
                            step="{{ $config->obtenerMultiploCanje() }}" 
                            min="{{ $config->obtenerMultiploCanje() }}" 
                            max="{{ floor($distribuidora->puntos / $config->obtenerMultiploCanje()) * $config->obtenerMultiploCanje() }}" 
                            required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-3 text-amber-400 font-mono text-2xl font-black mt-1 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-center">
                        <div class="text-[10px] text-amber-500/70 mt-1 font-bold">Debe ser múltiplo de {{ $config->obtenerMultiploCanje() }}</div>
                    </div>

                    <div class="bg-amber-950/30 border border-amber-900/50 rounded-xl p-3 mt-4">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Monto a Canjear</span>
                        <span class="block text-2xl font-black text-amber-400" x-text="'$' + (puntosCanjear * valorPunto).toFixed(2)"></span>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button type="button" @click="modalOpen = false" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 py-3 bg-amber-600 hover:bg-amber-500 text-white font-black text-sm rounded-xl transition-colors shadow-lg shadow-amber-500/20">
                            Cobrar Puntos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
