@extends('layouts.app')

@section('title', 'Vales de Préstamo - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil con Categoría del Distribuidor y Crédito Disponible -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="flex items-center justify-between gap-2 mb-3">
            <div>
                <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Catálogo de Vales
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">Productos de crédito activos disponibles</p>
            </div>
            
            @auth
                @if(Auth::user()->esDistribuidor())
                    <div class="text-right shrink-0">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 block">
                            Categoría {{ strtoupper(Auth::user()->categoria_distribuidor ?? 'cobre') }}
                        </span>
                        <span class="text-[10px] font-extrabold text-emerald-400 block mt-0.5">
                            {{ number_format(Auth::user()->obtenerPorcentajeGanancia(), 0) }}% Ganancia
                        </span>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Ficha de Crédito Disponible & Tope de Vale -->
        @auth
            @if(Auth::user()->esDistribuidor())
                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800 mb-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium">Crédito Disponible:</span>
                        <span class="font-black text-emerald-400 text-sm">
                            ${{ number_format(Auth::user()->creditoDisponible(), 2) }}
                            <span class="text-[10px] text-slate-500 font-normal">/ ${{ number_format(Auth::user()->limite_credito ?? 20000, 0) }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-800/80">
                        <span class="text-indigo-300 font-medium">Tope por vale (50% + $500):</span>
                        <span class="font-extrabold text-indigo-300">${{ number_format(Auth::user()->montoMaximoPermitidoPorVale(), 2) }}</span>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Badges de Estadísticas Móviles -->
        <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-800 text-center">
            <div class="bg-indigo-500/5 rounded-xl p-2 border border-indigo-500/20">
                <span class="text-[10px] uppercase font-semibold text-indigo-400 block">Vales Disponibles</span>
                <span class="text-lg font-black text-white">{{ $stats['activos'] }}</span>
            </div>
            <div class="bg-emerald-500/5 rounded-xl p-2 border border-emerald-500/20">
                <span class="text-[10px] uppercase font-semibold text-emerald-400 block">Préstamo Promedio</span>
                <span class="text-lg font-black text-emerald-400">${{ number_format($stats['monto_promedio'], 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Buscador Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md">
        <form action="{{ route('producto-vales.index') }}" method="GET" class="flex gap-2">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por clave o nombre..."
                    class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shrink-0">
                Buscar
            </button>
            @if(request('buscar'))
                <a href="{{ route('producto-vales.index') }}" class="px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center">
                    &times;
                </a>
            @endif
        </form>
    </div>

    <!-- Lista Móvil de Vales -->
    <div class="space-y-3">
        @forelse($productos as $producto)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg space-y-3 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-600/5 rounded-full blur-xl pointer-events-none"></div>

                <!-- Encabezado del Vale Móvil -->
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-mono text-[10px] font-bold border border-indigo-500/20 mb-1">
                            {{ $producto->clave }}
                        </span>
                        <h2 class="font-extrabold text-base text-white leading-tight">{{ $producto->nombre }}</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Monto Solicitado</span>
                        <span class="text-lg font-black text-emerald-400">${{ number_format($producto->monto_prestamo, 2) }}</span>
                    </div>
                </div>

                <!-- Grid de Amortización Rápida Móvil -->
                <div class="bg-slate-950/80 rounded-xl p-3 grid grid-cols-2 gap-2 text-xs border border-slate-800/80">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Pago Quincenal:</span>
                        <span class="font-black text-white text-sm">${{ number_format($producto->cuota_quincenal, 2) }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[10px]">Plazo:</span>
                        <span class="font-bold text-slate-200 text-sm">{{ $producto->plazo_quincenas }} quincenas</span>
                    </div>

                    <div class="col-span-2 pt-2 border-t border-slate-800/60 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Total a pagar: <strong class="text-indigo-300">${{ number_format($producto->monto_total_pagar, 2) }}</strong></span>
                        <span class="text-[10px] font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                            Seguro: ${{ number_format($producto->costo_seguro, 0) }}
                        </span>
                    </div>
                </div>

                <!-- Acciones Móviles -->
                <div>
                    <a href="{{ route('producto-vales.show', $producto) }}" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold text-center shadow-lg transition flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Ver Tabla de Amortización Móvil
                    </a>
                </div>

            </div>
        @empty
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 space-y-2">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-400">No hay vales activos disponibles</p>
            </div>
        @endforelse
    </div>

    <!-- Paginación Móvil -->
    @if($productos->hasPages())
        <div class="pt-2">
            {{ $productos->links() }}
        </div>
    @endif

</div>
@endsection
