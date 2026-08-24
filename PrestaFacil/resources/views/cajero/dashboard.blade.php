@extends('layouts.app')

@section('title', 'Panel Cajero - PrestaFácil')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <!-- Tarjeta de Perfil Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-violet-600/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 via-purple-600 to-indigo-400 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-violet-600/25 shrink-0">
                    {{ strtoupper(substr($cajera->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-white leading-tight truncate max-w-[190px]">
                        {{ $cajera->name }}
                    </h1>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $cajera->sucursal?->nombre ?? 'Sucursal Central' }}
                    </p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold border uppercase tracking-wider shrink-0 bg-violet-500/20 text-violet-300 border-violet-400/50">
                CAJERO
            </span>
        </div>
    </div>

    <!-- Buscador de Folio Principal -->
    <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 border border-indigo-500/30 rounded-3xl p-5 shadow-2xl relative overflow-hidden">
        <div class="absolute -left-8 -bottom-8 w-36 h-36 bg-indigo-500/15 rounded-full blur-2xl pointer-events-none"></div>
        
        <h2 class="text-white font-bold mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Búsqueda de Vales
        </h2>
        
        <form novalidate action="{{ route('cajero.buscar-folio') }}" method="GET">
            <div class="relative">
                <input type="text" name="referencia" placeholder="Ej: REF-PREVALE-..." required
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3 pl-4 pr-12 text-white font-mono text-sm uppercase focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg px-3 flex items-center justify-center transition-colors">
                    &rarr;
                </button>
            </div>
        </form>
    </div>

    <!-- Módulos Rápidos -->
    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2 pt-2">Accesos Rápidos</h3>
    
    <div class="grid grid-cols-2 gap-3">
        
        <!-- Módulo Abonos -->
        <a href="{{ route('cajero.abonos.index') }}" class="bg-slate-900 border border-slate-800 hover:border-emerald-500/50 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 text-center group transition-all">
            <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-slate-300 group-hover:text-white">Recepción de Abonos</span>
        </a>

        <!-- Módulo Canje -->
        <a href="{{ route('cajero.canje-puntos.index') }}" class="bg-slate-900 border border-slate-800 hover:border-amber-500/50 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 text-center group transition-all">
            <div class="w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-slate-300 group-hover:text-white">Canje de Puntos</span>
        </a>

        <!-- Módulo Conciliaciones -->
        <a href="{{ route('cajero.conciliaciones.index') }}" class="bg-slate-900 border border-slate-800 hover:border-cyan-500/50 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 text-center group transition-all col-span-2">
            <div class="w-10 h-10 rounded-full bg-cyan-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-slate-300 group-hover:text-white">Conciliación Manual</span>
        </a>

    </div>

    <!-- Estadísticas del Día -->
    <div class="bg-slate-950/50 border border-slate-800/60 rounded-xl p-4 mt-2">
        <h3 class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-3">Estadísticas de Hoy ({{ \Carbon\Carbon::now()->format('d M') }})</h3>
        <div class="flex justify-between text-center divide-x divide-slate-800">
            <div class="flex-1">
                <div class="text-lg font-black text-white">{{ $valesEntregadosHoy }}</div>
                <div class="text-[10px] text-slate-400 uppercase">Vales</div>
            </div>
            <div class="flex-1">
                <div class="text-lg font-black text-emerald-400">${{ number_format($abonosRecibidosHoy, 0) }}</div>
                <div class="text-[10px] text-slate-400 uppercase">Abonos</div>
            </div>
            <div class="flex-1">
                <div class="text-lg font-black text-amber-400">{{ $canjesHoy }}</div>
                <div class="text-[10px] text-slate-400 uppercase">Canjes</div>
            </div>
        </div>
    </div>

</div>
@endsection
