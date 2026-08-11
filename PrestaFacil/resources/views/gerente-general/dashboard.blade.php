@extends('layouts.app')

@section('title', 'Panel de Gerente General - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Gerente General -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Gerencia General
                    </span>
                    @if($totalSolicitudesPendientes > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">
                            {{ $totalSolicitudesPendientes }} Solicitud{{ $totalSolicitudesPendientes > 1 ? 'es' : '' }} Pendiente{{ $totalSolicitudesPendientes > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Supervisión general de cartera de vales, sucursales y autorizaciones.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('solicitudes-clientes.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Bandeja de Solicitudes ({{ $totalSolicitudesPendientes }})
                </a>
                <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Gestión de Usuarios
                </a>
                <a href="{{ route('configuracion-general.edit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs de Cartera Activa Global -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Préstamos Activos</span>
            <div class="text-2xl font-black text-white mt-2">{{ number_format($statsPrestamos['total_activos']) }} vales</div>
            <p class="text-xs text-indigo-400 mt-1">En curso en toda la empresa</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Capital Prestado Activo</span>
            <div class="text-2xl font-black text-white mt-2">${{ number_format($statsPrestamos['monto_prestado'], 2) }}</div>
            <p class="text-xs text-slate-400 mt-1">Monto nominal en calle</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Saldo por Cobrar</span>
            <div class="text-2xl font-black text-rose-300 mt-2">${{ number_format($statsPrestamos['adeudo_pendiente'], 2) }}</div>
            <p class="text-xs text-slate-400 mt-1">Adeudo pendiente total</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Pagos Recibidos</span>
            <div class="text-2xl font-black text-emerald-300 mt-2">${{ number_format($statsPrestamos['pagos_recibidos'], 2) }}</div>
            <p class="text-xs text-slate-400 mt-1">Recuperación acumulada</p>
        </div>
    </div>

    <!-- Sección: Solicitudes Pendientes de Aprobación de Clientes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Solicitudes de Clientes Pendientes de Autorización
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Autoriza o rechaza solicitudes de modificación y baja emitidas por distribuidores.</p>
            </div>
            <a href="{{ route('solicitudes-clientes.index', ['estado' => 'pendiente']) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                Ver todas las pendientes &rarr;
            </a>
        </div>

        @if($solicitudesPendientes->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                 No hay solicitudes pendientes de aprobación en este momento.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Folio / Fecha</th>
                            <th class="px-6 py-3.5">Cliente</th>
                            <th class="px-6 py-3.5">Distribuidor / Sucursal</th>
                            <th class="px-6 py-3.5">Tipo</th>
                            <th class="px-6 py-3.5 text-right">Acción Rápida</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($solicitudesPendientes as $sol)
                            <tr class="hover:bg-slate-800/40 transition">
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
                                            Actualización
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            Baja / Desactivación
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('solicitudes-clientes.show', $sol) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
                                            Revisar
                                        </a>
                                        <form method="POST" action="{{ route('solicitudes-clientes.aprobar', $sol) }}" onsubmit="return confirm('¿Aprobar solicitud para {{ $sol->cliente?->nombre }}?');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition">
                                                ✓ Aceptar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Sección Principal: Préstamos Activos por Distribuidora -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden space-y-6">
        <div class="p-6 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Préstamos Activos por Distribuidora (Global)
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Control de cartera vigente, crédito ocupado y desglose de préstamos por distribuidor.</p>
            </div>

            <!-- Filtro de Sucursal -->
            <form method="GET" action="{{ route('gerente-general.dashboard') }}" class="flex items-center gap-2">
                <select name="sucursal_id" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todas las Sucursales</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ $sucursalId == $sucursal->id ? 'selected' : '' }}>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
                @if($sucursalId)
                    <a href="{{ route('gerente-general.dashboard') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition" title="Quitar Filtro">
                        &times;
                    </a>
                @endif
            </form>
        </div>

        @if($distribuidores->isEmpty())
            <div class="p-12 text-center text-slate-500 text-sm">
                No se encontraron distribuidores registrados en esta sucursal.
            </div>
        @else
            <div class="p-6 pt-0 space-y-6">
                @foreach($distribuidores as $dist)
                    @php
                        $activos = $dist->prestamos;
                        $totalPrestado = $activos->sum('monto_prestamo');
                        $totalAdeudo = $activos->sum('adeudo_pendiente');
                        $totalPagado = $activos->sum('pagos_recibidos');
                        $limite = floatval($dist->limite_credito ?? 20000.00);
                        $disponible = max(0, $limite - $totalPrestado);
                    @endphp

                    <div x-data="{ open: false }" class="border border-slate-800 rounded-2xl bg-slate-950/50 overflow-hidden shadow-md">
                        <!-- Cabecera de la Distribuidora -->
                        <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/60 transition"
                             @click="open = !open">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-lg">
                                    {{ strtoupper(substr($dist->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base font-bold text-white">{{ $dist->name }}</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 uppercase">
                                            {{ $dist->categoria_distribuidor ?? 'Cobre' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Sucursal: <span class="text-slate-300 font-semibold">{{ $dist->sucursal?->nombre ?? 'Sin Asignar' }}</span>
                                        &bull; Ref: <span class="font-mono text-indigo-300">{{ $dist->referenciaPago() }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Métricas de la Distribuidora -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div>
                                    <span class="text-slate-400 block">Vales Activos</span>
                                    <span class="font-bold text-white text-sm">{{ count($activos) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Capital Colocado</span>
                                    <span class="font-bold text-indigo-300 text-sm">${{ number_format($totalPrestado, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Saldo Pendiente</span>
                                    <span class="font-bold text-rose-300 text-sm">${{ number_format($totalAdeudo, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Crédito Disponible</span>
                                    <span class="font-bold text-emerald-300 text-sm">${{ number_format($disponible, 2) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-indigo-400" x-text="open ? 'Ocultar Préstamos ▲' : 'Ver Préstamos (' + {{ count($activos) }} + ') ▼'"></span>
                            </div>
                        </div>

                        <!-- Detalle Desplegable de Préstamos Activos -->
                        <div x-show="open" class="border-t border-slate-800 bg-slate-900/90 p-5" style="display: none;">
                            @if($activos->isEmpty())
                                <p class="text-xs text-slate-500 italic">Esta distribuidora no tiene préstamos activos vigentes en este momento.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs text-slate-300">
                                        <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                            <tr>
                                                <th class="px-4 py-2.5">Referencia</th>
                                                <th class="px-4 py-2.5">Cliente</th>
                                                <th class="px-4 py-2.5">Producto</th>
                                                <th class="px-4 py-2.5">Monto Prestado</th>
                                                <th class="px-4 py-2.5">Adeudo Pendiente</th>
                                                <th class="px-4 py-2.5">Progreso</th>
                                                <th class="px-4 py-2.5 text-right">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800/60">
                                            @foreach($activos as $prestamo)
                                                <tr class="hover:bg-slate-800/40">
                                                    <td class="px-4 py-2.5 font-mono font-bold text-indigo-400">{{ $prestamo->referencia }}</td>
                                                    <td class="px-4 py-2.5">
                                                        <div class="font-semibold text-white">{{ $prestamo->cliente?->nombre }}</div>
                                                        <div class="text-[10px] text-slate-500 font-mono">{{ $prestamo->cliente?->curp }}</div>
                                                    </td>
                                                    <td class="px-4 py-2.5">{{ $prestamo->productoVale?->nombre ?? 'Vale' }}</td>
                                                    <td class="px-4 py-2.5 font-semibold text-white">${{ number_format($prestamo->monto_prestamo, 2) }}</td>
                                                    <td class="px-4 py-2.5 font-bold text-rose-300">${{ number_format($prestamo->adeudo_pendiente, 2) }}</td>
                                                    <td class="px-4 py-2.5">
                                                        {{ $prestamo->pagos_realizados }}/{{ $prestamo->pagos_totales }} pagos
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right">
                                                        <a href="{{ route('prestamos.show', $prestamo) }}" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-indigo-300 font-semibold text-[11px] transition">
                                                            Ver Vale
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection