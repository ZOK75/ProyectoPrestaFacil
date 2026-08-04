@extends('layouts.app')

@section('title', 'Catálogo de Vales de Préstamo - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Vales de Préstamo por Transferencia</h1>
            <p class="text-slate-400 text-sm mt-1">Administra los productos de crédito, montos prestados, seguro incluido y plazos quincenales.</p>
        </div>
        <div>
            <a href="{{ route('producto-vales.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Producto Vale
            </a>
        </div>
    </div>

    <!-- Cards de Estadísticas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card Total -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Productos</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mt-2">{{ $stats['total'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Vales en catálogo</p>
        </div>

        <!-- Card Activos -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Vales Activos</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-400 mt-2">{{ $stats['activos'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Disponibles para emisión</p>
        </div>

        <!-- Card Inactivos / Desactivados -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Desactivados</span>
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-rose-400 mt-2">{{ $stats['inactivos'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Con marca de tiempo</p>
        </div>

        <!-- Card Promedio Préstamo -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Préstamo Promedio</span>
                <div class="w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mt-2">${{ number_format($stats['monto_promedio'], 2) }}</div>
            <p class="text-xs text-slate-500 mt-1">Monto base del préstamo</p>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('producto-vales.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por clave (ej. VLT-5K) o nombre..." class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="w-full md:w-48">
                <select name="estado" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los estados</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Solo Desactivados</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['buscar', 'estado']))
                    <a href="{{ route('producto-vales.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 hover:text-white text-sm font-medium flex items-center justify-center">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Productos -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Clave / Nombre</th>
                        <th class="px-6 py-4">Monto Préstamo</th>
                        <th class="px-6 py-4">Costo Seguro</th>
                        <th class="px-6 py-4">Plazo</th>
                        <th class="px-6 py-4">Total a Pagar</th>
                        <th class="px-6 py-4">Cuota 15na</th>
                        <th class="px-6 py-4">Estado / Desactivación</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($productos as $producto)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white group-hover:text-indigo-400 transition">{{ $producto->nombre }}</div>
                                <div class="text-xs font-mono text-indigo-400 mt-0.5">{{ $producto->clave }}</div>
                                @if($producto->createdBy)
                                    <div class="text-[10px] text-slate-500 mt-0.5">Creado por: {{ $producto->createdBy->name ?? 'Usuario #'.$producto->created_by_user_id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-white">
                                ${{ number_format($producto->monto_prestamo, 2) }}
                            </td>
                            <td class="px-6 py-4 text-amber-400 font-semibold">
                                ${{ number_format($producto->costo_seguro, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $producto->plazo_quincenas }} quincenas
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-indigo-300">
                                ${{ number_format($producto->monto_total_pagar, 2) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-white">
                                ${{ number_format($producto->cuota_quincenal, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($producto->activo)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Activo
                                    </span>
                                @else
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Desactivado
                                        </span>
                                        @if($producto->desactivado_at)
                                            <span class="block text-[10px] text-slate-400 font-mono">
                                                {{ $producto->desactivado_at->format('d/m/Y H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('producto-vales.show', $producto) }}" class="inline-flex p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition" title="Ver Detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($producto->activo)
                                    <a href="{{ route('producto-vales.edit', $producto) }}" class="inline-flex p-2 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-600 hover:text-white transition" title="Desactivar Producto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </a>
                                @endif

                               
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="max-w-xs mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <p class="font-medium text-slate-400">No se encontraron productos de vales</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productos->hasPages())
            <div class="px-6 py-4 bg-slate-950 border-t border-slate-800">
                {{ $productos->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
