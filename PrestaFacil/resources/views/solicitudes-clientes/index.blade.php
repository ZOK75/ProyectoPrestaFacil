@extends('layouts.app')

@section('title', 'Bandeja de Solicitudes y Notificaciones - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-lg shadow-indigo-500/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">Bandeja de Autorizaciones y Solicitudes</h1>
                    <p class="text-slate-400 text-xs sm:text-sm mt-0.5">
                        Revisa, aprueba o rechaza solicitudes de modificación y baja de clientes enviadas por los distribuidores.
                    </p>
                </div>
            </div>
        </div>

        @if($stats['pendientes'] > 0)
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                {{ $stats['pendientes'] }} Solicitud{{ $stats['pendientes'] > 1 ? 'es' : '' }} Requieren tu Revisión
            </div>
        @endif
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('solicitudes-clientes.index') }}" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-indigo-500/40 transition">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Solicitudes</div>
            <div class="text-2xl font-black text-white mt-1">{{ number_format($stats['total']) }}</div>
        </a>
        <a href="{{ route('solicitudes-clientes.index', ['estado' => 'pendiente']) }}" class="bg-slate-900 border border-amber-500/30 rounded-xl p-4 hover:border-amber-500/60 transition bg-amber-500/5">
            <div class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pendientes de Aprobación</div>
            <div class="text-2xl font-black text-amber-300 mt-1">{{ number_format($stats['pendientes']) }}</div>
        </a>
        <a href="{{ route('solicitudes-clientes.index', ['estado' => 'aprobada']) }}" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-emerald-500/40 transition">
            <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Aprobadas</div>
            <div class="text-2xl font-black text-emerald-300 mt-1">{{ number_format($stats['aprobadas']) }}</div>
        </a>
        <a href="{{ route('solicitudes-clientes.index', ['estado' => 'rechazada']) }}" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-rose-500/40 transition">
            <div class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Rechazadas</div>
            <div class="text-2xl font-black text-rose-300 mt-1">{{ number_format($stats['rechazadas']) }}</div>
        </a>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
        <form method="GET" action="{{ route('solicitudes-clientes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Buscar -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Buscar</label>
                <div class="relative">
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Cliente, CURP o Distribuidor..." 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 placeholder-slate-600">
                </div>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Estado</label>
                <select name="estado" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="aprobada" {{ request('estado') === 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                    <option value="rechazada" {{ request('estado') === 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                </select>
            </div>

            <!-- Tipo de Solicitud -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Tipo de Solicitud</label>
                <select name="tipo" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los tipos</option>
                    <option value="actualizacion" {{ request('tipo') === 'actualizacion' ? 'selected' : '' }}>Actualización de Datos</option>
                    <option value="desactivacion" {{ request('tipo') === 'desactivacion' ? 'selected' : '' }}>Baja / Desactivación</option>
                </select>
            </div>

            <!-- Filtro Sucursal (solo Gerente General) -->
            @if($operador->esGerenteGeneral())
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Sucursal</label>
                    <select name="sucursal_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Todas las sucursales</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" {{ request('sucursal_id') == $sucursal->id ? 'selected' : '' }}>
                                {{ $sucursal->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Botones de Acción -->
            <div class="sm:col-span-2 lg:col-span-4 flex justify-end gap-2 pt-2 border-t border-slate-800/80">
                @if(request()->hasAny(['buscar', 'estado', 'tipo', 'sucursal_id']))
                    <a href="{{ route('solicitudes-clientes.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                        Limpiar Filtros
                    </a>
                @endif
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
                    Aplicar Filtros
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla Principal de Solicitudes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        @if($solicitudes->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-800 text-slate-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">No se encontraron solicitudes</h3>
                <p class="text-xs text-slate-500 mt-1">No hay solicitudes pendientes o que coincidan con los filtros seleccionados.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Folio / Fecha</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Distribuidor / Sucursal</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($solicitudes as $sol)
                            <tr class="hover:bg-slate-800/40 transition {{ $sol->esPendiente() ? 'bg-indigo-950/10' : '' }}">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs text-indigo-400 font-bold">#SOL-{{ str_pad($sol->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="block text-xs text-slate-500">{{ $sol->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white">{{ $sol->cliente?->nombre }}</div>
                                    <div class="text-xs text-slate-500 font-mono">CURP: {{ $sol->cliente?->curp }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium text-slate-200">{{ $sol->distribuidor?->name }}</div>
                                    <div class="text-xs text-indigo-400">{{ $sol->sucursal?->nombre ?? 'Sin Sucursal' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($sol->esActualizacion())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Actualización
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Baja / Desactivación
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($sol->esPendiente())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pendiente
                                        </span>
                                    @elseif($sol->estado === 'aprobada')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Aprobada
                                        </span>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Por: {{ $sol->aprobadoPor?->name }}</div>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Rechazada
                                        </span>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Por: {{ $sol->rechazadoPor?->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('solicitudes-clientes.show', $sol) }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
                                            Comparar / Revisar
                                        </a>

                                        @if($sol->esPendiente() && !$operador->esAdministrador())
                                            <!-- Botón Rápido Aprobar -->
                                            <form method="POST" action="{{ route('solicitudes-clientes.aprobar', $sol) }}" onsubmit="return confirm('¿Aprobar inmediatamente esta solicitud para {{ $sol->cliente?->nombre }}?');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition" title="Aprobar Solicitud">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Aceptar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="p-4 border-t border-slate-800">
                {{ $solicitudes->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
