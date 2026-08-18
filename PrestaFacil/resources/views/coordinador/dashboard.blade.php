@extends('layouts.app')

@section('title', 'Dashboard Coordinador - PrestaFácil')

@section('content')
<div class="space-y-6 sm:space-y-8" x-data="{ 
    showCreditModal: false, 
    selectedDistId: '', 
    selectedDistName: '', 
    selectedDistLimit: 0,
    openCreditModal(id, name, limit) {
        this.selectedDistId = id;
        this.selectedDistName = name;
        this.selectedDistLimit = limit;
        this.showCreditModal = true;
    },
    showTransferModal: false,
    transferDistId: '',
    transferDistName: '',
    transferDistSucursal: '',
    openTransferModal(id, name, sucursal) {
        this.transferDistId = id;
        this.transferDistName = name;
        this.transferDistSucursal = sucursal;
        this.showTransferModal = true;
    }
}">

    <!-- Header Coordinador (Tablet Responsive) -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-5 sm:p-7 lg:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Coordinación
                    </span>
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ auth()->user()->sucursal?->nombre ?? 'Sucursal sin asignar' }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Hola, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">Supervisión de distribuidoras, cartera activa de préstamos y traspaso de distribuidoras.</p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('coordinador.prestamos') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-900/30 transition text-xs sm:text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                    </svg>
                    Ver Cartera de Préstamos
                </a>
                <a href="{{ route('coordinador.solicitudes.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Solicitudes Distribuidora
                </a>
                <a href="{{ route('coordinador.solicitudes.create') }}" class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Registro
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs y Estadísticas de Cartera (Grid Tablet 2x2 o 4 columnas) -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- KPI 1: Distribuidoras Asignadas -->
        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Distribuidoras</p>
                <h3 class="text-xl sm:text-2xl font-black text-white mt-1">{{ $stats['total_distribuidores'] }}</h3>
                <span class="text-[10px] text-slate-500">Supervisadas</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 2: Préstamos Activos -->
        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Préstamos Activos</p>
                <h3 class="text-xl sm:text-2xl font-black text-sky-400 mt-1">{{ $stats['prestamos_activos'] }}</h3>
                <span class="text-[10px] text-slate-500">En amortización</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 3: Saldo por Cobrar (Adeudo Cartera) -->
        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Saldo por Cobrar</p>
                <h3 class="text-lg sm:text-xl font-black text-amber-400 mt-1">${{ number_format($stats['adeudo_cartera'], 2) }}</h3>
                <span class="text-[10px] text-slate-500">Cartera activa</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 4: Traspasos Pendientes -->
        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Traspasos Recibidos</p>
                <h3 class="text-xl sm:text-2xl font-black {{ $stats['transferencias_pendientes'] > 0 ? 'text-rose-400 animate-pulse' : 'text-emerald-400' }} mt-1">
                    {{ $stats['transferencias_pendientes'] }}
                </h3>
                <span class="text-[10px] text-slate-500">Por revisar</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: Solicitudes de Transferencia de Distribuidoras (Recibidas y Emitidas) -->
    @if($transferenciasRecibidas->count() > 0 || $transferenciasEmitidas->count() > 0)
        <div class="space-y-4">
            <!-- Transferencias Recibidas (Acción Requerida) -->
            @if($transferenciasRecibidas->count() > 0)
                <div class="bg-slate-900 border border-purple-500/30 rounded-2xl shadow-lg shadow-purple-950/20 overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-800 bg-purple-950/20 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300 font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-bold text-white">Traspasos de Distribuidora Recibidos</h2>
                                <p class="text-slate-400 text-xs">Otros coordinadores te proponen incorporar a sus distribuidoras a tu equipo.</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-800">
                        @foreach($transferenciasRecibidas as $tr)
                            <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-800/30 transition">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-white">{{ $tr->distribuidor?->name }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            Cat. {{ $tr->distribuidor?->categoria_distribuidor ?? 'Estándar' }}
                                        </span>
                                        @if($tr->esPendienteCoordinador())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">
                                                Requiere tu Aceptación
                                            </span>
                                        @elseif($tr->esPendienteGerente())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                                Aceptada por ti &bull; Pendiente Visto Bueno Gerencia
                                            </span>
                                        @elseif($tr->esAprobada())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                Traspaso Concluido
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                Rechazada
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400">
                                        Propuesto por: <span class="text-slate-200 font-semibold">{{ $tr->coordinadorEmisor?->name }}</span> 
                                        (Sucursal Origen: {{ $tr->sucursalOrigen?->nombre }}) &bull; Fecha: {{ $tr->created_at->format('d/m/Y H:i') }}
                                    </p>
                                    <p class="text-xs text-slate-300 italic bg-slate-950/40 p-2 rounded-lg border border-slate-800">
                                        "{{ $tr->motivo }}"
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <a href="{{ route('coordinador.transferencias.revisar', $tr) }}" 
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Revisar Distribuidora y Cartera
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Transferencias Emitidas por este Coordinador -->
            @if($transferenciasEmitidas->count() > 0)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-white">Traspasos Emitidos</h2>
                            <p class="text-slate-400 text-xs">Seguimiento de transferencias de distribuidoras solicitadas a otros coordinadores.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-800/50 text-slate-300 uppercase tracking-wider">
                                    <th class="p-3.5 font-semibold">Distribuidora</th>
                                    <th class="p-3.5 font-semibold">Coordinador Destino</th>
                                    <th class="p-3.5 font-semibold">Sucursal Destino</th>
                                    <th class="p-3.5 font-semibold">Fecha</th>
                                    <th class="p-3.5 font-semibold">Estatus</th>
                                    <th class="p-3.5 font-semibold">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($transferenciasEmitidas as $te)
                                    <tr class="hover:bg-slate-800/20 transition">
                                        <td class="p-3.5 font-bold text-white">{{ $te->distribuidor?->name }}</td>
                                        <td class="p-3.5 text-slate-300">{{ $te->coordinadorReceptor?->name }}</td>
                                        <td class="p-3.5 text-slate-400">{{ $te->sucursalDestino?->nombre }}</td>
                                        <td class="p-3.5 text-slate-400">{{ $te->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="p-3.5">
                                            @if($te->estado === 'pendiente_coordinador')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Pendiente Coordinador</span>
                                            @elseif($te->estado === 'pendiente_gerente')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">Pendiente Gerencia</span>
                                            @elseif($te->estado === 'aprobada')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aprobada</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Rechazada</span>
                                            @endif
                                        </td>
                                        <td class="p-3.5 text-slate-400 italic max-w-xs truncate">
                                            {{ $te->observaciones_gerente ?? $te->observaciones_coordinador_receptor ?? $te->motivo }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- TABLA DE DISTRIBUIDORAS ASIGNADAS (Adaptada a Tablet y Móvil) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/50">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Distribuidoras a tu Cargo
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Control individual de línea de crédito, préstamos activos y solicitud de traspaso.</p>
            </div>

            <a href="{{ route('coordinador.prestamos') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition flex items-center gap-1">
                Ver todos los préstamos
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-3.5 sm:p-4 font-semibold">Distribuidora</th>
                        <th class="p-3.5 sm:p-4 font-semibold">Categoría</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-center">Préstamos Activos</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Crédito Disponible</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($distribuidores as $dist)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-3.5 sm:p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                                        <span class="text-indigo-300 font-black text-xs">{{ strtoupper(substr($dist->name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-slate-100 font-bold text-xs sm:text-sm">{{ $dist->name }}</div>
                                        <div class="text-slate-500 text-[11px] font-mono">Ref: {{ $dist->referenciaPago() }} &bull; {{ $dist->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 sm:p-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold border
                                    @if(strtoupper($dist->categoria_distribuidor) === 'ORO') bg-amber-500/10 text-amber-400 border-amber-500/20
                                    @elseif(strtoupper($dist->categoria_distribuidor) === 'PLATA') bg-slate-300/10 text-slate-300 border-slate-300/20
                                    @elseif(strtoupper($dist->categoria_distribuidor) === 'BRONCE') bg-orange-500/10 text-orange-400 border-orange-500/20
                                    @else bg-emerald-500/10 text-emerald-400 border-emerald-500/20 @endif">
                                    Cat. {{ $dist->categoria_distribuidor ?? 'Cobre' }}
                                </span>
                            </td>
                            <td class="p-3.5 sm:p-4 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold {{ $dist->prestamos->count() > 0 ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'bg-slate-800 text-slate-400' }}">
                                    {{ $dist->prestamos->count() }}
                                </span>
                            </td>
                            <td class="p-3.5 sm:p-4 text-right">
                                <div class="text-emerald-400 font-bold text-xs sm:text-sm">${{ number_format($dist->creditoDisponible(), 2) }}</div>
                                <div class="text-slate-500 text-[10px]">Límite: ${{ number_format($dist->limite_credito, 2) }}</div>
                            </td>
                            <td class="p-3.5 sm:p-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 sm:gap-2 flex-wrap">
                                    <!-- Ver Préstamos de esta distribuidora -->
                                    <a href="{{ route('coordinador.prestamos', ['distribuidor_id' => $dist->id]) }}"
                                       class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-semibold transition"
                                       title="Ver cartera de préstamos de esta distribuidora">
                                        Préstamos
                                    </a>

                                    <!-- Incrementar Crédito -->
                                    <button type="button" 
                                            @click="openCreditModal('{{ $dist->id }}', '{{ $dist->name }}', {{ $dist->limite_credito }})"
                                            class="px-2.5 py-1.5 bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 rounded-lg text-xs font-semibold transition">
                                        + Crédito
                                    </button>

                                    <!-- Solicitar Cambio de Distribuidora -->
                                    <button type="button" 
                                            @click="openTransferModal('{{ $dist->id }}', '{{ $dist->name }}', '{{ $dist->sucursal?->nombre ?? 'Sin sucursal' }}')"
                                            class="px-2.5 py-1.5 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/30 text-purple-300 rounded-lg text-xs font-semibold transition"
                                            title="Solicitar cambio / traspaso de distribuidora">
                                        Traspaso
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500 text-sm">
                                No tienes distribuidoras activas bajo tu supervisión en este momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial de Incrementos de Crédito Solicitados -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-white">Solicitudes de Incremento de Crédito</h2>
                <p class="text-slate-400 text-xs mt-0.5">Estatus de autorizaciones enviadas a Gerencia.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 uppercase tracking-wider">
                        <th class="p-3.5 font-semibold">Distribuidora</th>
                        <th class="p-3.5 font-semibold text-right">Crédito Original</th>
                        <th class="p-3.5 font-semibold text-right">Nuevo Crédito</th>
                        <th class="p-3.5 font-semibold">Fecha</th>
                        <th class="p-3.5 font-semibold">Estado</th>
                        <th class="p-3.5 font-semibold">Resolución / Gerente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($solicitudesCredito as $sol)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-3.5">
                                <div class="text-slate-200 font-bold">{{ $sol->distribuidor?->name }}</div>
                                <div class="text-slate-500 text-[10px] font-mono">Ref: {{ $sol->distribuidor?->referenciaPago() }}</div>
                            </td>
                            <td class="p-3.5 text-right text-slate-400 font-medium">
                                ${{ number_format($sol->limite_actual, 2) }}
                            </td>
                            <td class="p-3.5 text-right text-emerald-400 font-bold">
                                ${{ number_format($sol->limite_nuevo, 2) }}
                            </td>
                            <td class="p-3.5 text-slate-400">
                                {{ $sol->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-3.5">
                                @if($sol->estado === 'pendiente')
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border bg-amber-500/10 text-amber-400 border-amber-500/20">Pendiente</span>
                                @elseif($sol->estado === 'aprobado')
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aprobado</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border bg-rose-500/10 text-rose-400 border-rose-500/20">Rechazado</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-400 max-w-xs truncate">
                                @if($sol->gerente)
                                    <span class="block text-slate-300 font-semibold">{{ $sol->gerente->name }}</span>
                                @endif
                                @if($sol->observaciones)
                                    <span class="block italic text-slate-500">"{{ $sol->observaciones }}"</span>
                                @else
                                    <span class="block italic text-slate-600">Sin comentarios</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500 text-xs">
                                No has solicitado incrementos de crédito anteriormente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: Solicitar Incremento de Crédito (Alpine.js) -->
    <div x-show="showCreditModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showCreditModal = false"></div>
        
        <div class="relative w-full max-w-lg mx-auto z-50">
            <div class="relative flex flex-col w-full bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
                <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Solicitar Incremento de Crédito</h3>
                    <button type="button" @click="showCreditModal = false" class="text-slate-400 hover:text-white text-lg font-bold leading-none">&times;</button>
                </div>
                
                <form :action="`/coordinador/distribuidores/${selectedDistId}/solicitar-credito`" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Distribuidora</label>
                        <input type="text" readonly :value="selectedDistName" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-300 px-4 py-2.5 text-xs font-bold select-none focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Límite Actual</label>
                            <div class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-400 px-4 py-2.5 text-xs select-none font-bold">
                                $<span x-text="Number(selectedDistLimit).toLocaleString('es-MX', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nuevo Límite ($) *</label>
                            <input type="number" step="0.01" name="limite_nuevo" required :min="selectedDistLimit + 0.01" placeholder="Ej: 30000"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Motivo / Justificación *</label>
                        <textarea name="motivo" rows="3" required placeholder="Explica detalladamente las razones del aumento de crédito..."
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-800 mt-5">
                        <button type="button" @click="showCreditModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-semibold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/20 text-xs font-bold transition">
                            Enviar a Gerencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Solicitar Cambio / Traspaso de Distribuidora -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showTransferModal = false"></div>
        
        <div class="relative w-full max-w-lg mx-auto z-50">
            <div class="relative flex flex-col w-full bg-slate-900 border border-purple-500/40 rounded-2xl shadow-2xl p-6">
                <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300 font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-white">Solicitar Traspaso de Distribuidora</h3>
                    </div>
                    <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-white text-lg font-bold leading-none">&times;</button>
                </div>
                
                <form :action="`/coordinador/distribuidores/${transferDistId}/solicitar-transferencia`" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Distribuidora a Traspasar</label>
                        <input type="text" readonly :value="transferDistName" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-indigo-300 px-4 py-2.5 text-xs font-bold select-none focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Coordinador Receptor (Misma u Otra Sucursal) *</label>
                        <select name="coordinador_receptor_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none font-medium">
                            <option value="">-- Selecciona el coordinador que recibirá a la distribuidora --</option>
                            @foreach($coordinadoresDestino as $coord)
                                <option value="{{ $coord->id }}">
                                    {{ $coord->name }} &bull; Sucursal: {{ $coord->sucursal?->nombre ?? 'Sin sucursal' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">El coordinador receptor recibirá una notificación para revisar a la distribuidora y su cartera activa de préstamos.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Motivo del Cambio / Traspaso *</label>
                        <textarea name="motivo" rows="3" required placeholder="Describe la razón por la que se solicita el traspaso (cambio de zona, reasignación de equipo, traslado de sucursal, etc.)..."
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none"></textarea>
                    </div>

                    <div class="p-3 bg-purple-950/20 border border-purple-500/20 rounded-xl text-[11px] text-purple-300 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Flujo de Autorización:
                        </p>
                        <ol class="list-decimal list-inside space-y-0.5 text-slate-300 pl-1">
                            <li>El Coordinador Receptor recibe notificación y revisa la distribuidora y sus préstamos.</li>
                            <li>Si acepta, se turna a la Gerencia de la Sucursal Receptora para su visto bueno definitivo.</li>
                        </ol>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-800 mt-5">
                        <button type="button" @click="showTransferModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-semibold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white shadow-lg shadow-purple-900/30 text-xs font-bold transition">
                            Enviar Solicitud de Traspaso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
