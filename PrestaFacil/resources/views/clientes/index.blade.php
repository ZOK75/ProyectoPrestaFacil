@extends('layouts.app')

@section('title', 'Clientes - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="flex items-center justify-between gap-2 mb-3">
            <div>
                <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Clientes
                    @if($operador->esAdministrador())
                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-slate-800 text-indigo-400 border border-indigo-500/30">
                            Auditoría
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">Expedientes digitales y consulta de clientes</p>
            </div>
            @if(!$operador->esAdministrador())
                <a href="{{ route('clientes.create') }}" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition flex items-center gap-1 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo
                </a>
            @endif
        </div>

        <!-- Badges de Resumen Móvil -->
        <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-800 text-center">
            <div class="bg-slate-950/60 rounded-xl p-2 border border-slate-800">
                <span class="text-[10px] uppercase font-semibold text-slate-400 block">Total</span>
                <span class="text-base font-extrabold text-white">{{ $stats['total'] }}</span>
            </div>
            <div class="bg-emerald-500/5 rounded-xl p-2 border border-emerald-500/20">
                <span class="text-[10px] uppercase font-semibold text-emerald-400 block">Activos</span>
                <span class="text-base font-extrabold text-emerald-400">{{ $stats['activos'] }}</span>
            </div>
            <div class="bg-rose-500/5 rounded-xl p-2 border border-rose-500/20">
                <span class="text-[10px] uppercase font-semibold text-rose-400 block">Inactivos</span>
                <span class="text-base font-extrabold text-rose-400">{{ $stats['inactivos'] }}</span>
            </div>
        </div>
    </div>

    <!-- Buscador Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md">
        <form action="{{ route('clientes.index') }}" method="GET" class="space-y-2">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar nombre o CURP..."
                    class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <select name="estado" class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los estados</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Solo Desactivados</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['buscar', 'estado']))
                    <a href="{{ route('clientes.index') }}" class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 text-xs font-medium">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Lista Móvil de Clientes -->
    <div class="space-y-3">
        @forelse($clientes as $cliente)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg transition hover:border-slate-700 space-y-3">
                
                <!-- Encabezado del Card Móvil -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white text-sm font-extrabold shadow-md shrink-0">
                            {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white leading-tight">{{ $cliente->nombre }}</h3>
                            <span class="text-[11px] font-mono text-indigo-400 block mt-0.5">CURP: {{ $cliente->curp }}</span>
                        </div>
                    </div>

                    <div>
                        @if($cliente->tieneSolicitudPendiente())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 animate-pulse">
                                 Solicitud en Revisión
                            </span>
                        @elseif($cliente->activo)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                • Activo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                • Inactivo
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Detalles de Ubicación e Identificación -->
                <div class="bg-slate-950/70 rounded-xl p-2.5 text-xs space-y-1 border border-slate-800/80">
                    <div class="flex items-center justify-between text-slate-300">
                        <span class="text-slate-400">Lugar:</span>
                        <span class="font-medium truncate max-w-[180px]">{{ $cliente->ciudad }}, {{ $cliente->estado }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-300">
                        <span class="text-slate-400">Dirección:</span>
                        <span class="font-medium truncate max-w-[180px]">{{ $cliente->calle }}</span>
                    </div>
                </div>

                <!-- Badges de Expedientes PDF -->
                <div class="flex items-center justify-between text-[11px] pt-1">
                    <div class="flex items-center gap-1.5">
                        @if($cliente->path_ine_pdf)
                            <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20">✓ INE PDF</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-500 italic">Sin INE</span>
                        @endif

                        @if($cliente->path_comprobante_pdf)
                            <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20">✓ Comp. PDF</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-500 italic">Sin Comp.</span>
                        @endif
                    </div>
                </div>

                <!-- Acciones Móviles -->
                <div class="flex items-center gap-2 pt-2 border-t border-slate-800/80">
                    <a href="{{ route('clientes.show', $cliente) }}" class="flex-1 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold text-center transition flex items-center justify-center gap-1 shadow">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Expediente
                    </a>

                    @if($cliente->activo && !$cliente->tieneSolicitudPendiente() && !$operador->esAdministrador())
                        <a href="{{ route('clientes.edit', $cliente) }}" class="px-3 py-2 rounded-xl bg-slate-800 text-indigo-400 hover:text-white text-xs font-semibold transition" title="Editar / Solicitar Modificación">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>

                        @php
                            $mensajeConfirmacion = ($operador && $operador->esDistribuidor())
                                ? "¿Enviar solicitud a Gerencia para desactivar al cliente {$cliente->nombre}?"
                                : "¿Desactivar al cliente {$cliente->nombre}?";
                        @endphp
                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ $mensajeConfirmacion }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-2 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-600 hover:text-white text-xs font-semibold transition" title="Desactivar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        @empty
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 space-y-2">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-400">No hay clientes en esta búsqueda</p>
            </div>
        @endforelse
    </div>

    <!-- Paginación Móvil -->
    @if($clientes->hasPages())
        <div class="pt-2">
            {{ $clientes->links() }}
        </div>
    @endif

</div>
@endsection
