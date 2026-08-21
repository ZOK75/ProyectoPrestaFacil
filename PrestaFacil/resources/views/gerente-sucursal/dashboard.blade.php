@extends('layouts.app')

@section('title', 'Panel de Gerente de Sucursal - PrestaFácil')

@section('content')
<div class="space-y-8" x-data="{ showTraspasoCoordModal: false, openDecisionCoordId: null }">

    <!-- Header Gerente de Sucursal -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Gerente de Sucursal
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $operador->sucursal?->nombre ?? 'Sucursal sin asignar' }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bienvenido, {{ $operador->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Supervisión y gestión del personal operativo y distribuidores de tu sucursal.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button @click="showTraspasoCoordModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/30 text-xs font-bold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Traspasar Coordinador
                </button>

                <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Registrar Usuario
                </a>
                <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Gestión de Usuarios
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs del Personal de la Sucursal -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Personal Asignado</span>
            <div class="text-2xl font-black text-white mt-2">{{ number_format($statsEquipo['total_personal']) }}</div>
            <p class="text-xs text-indigo-400 mt-1">Colaboradores en sucursal</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Cuentas Activas</span>
            <div class="text-2xl font-black text-emerald-300 mt-2">{{ number_format($statsEquipo['activos']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Con acceso al sistema</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Distribuidores</span>
            <div class="text-2xl font-black text-amber-300 mt-2">{{ number_format($statsEquipo['distribuidores']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Colocación y red de vales</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-violet-400 uppercase tracking-wider">Cajeros y Operativos</span>
            <div class="text-2xl font-black text-violet-300 mt-2">{{ number_format($statsEquipo['cajeros'] + $statsEquipo['otros']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Ventanilla y verificación</p>
        </div>
    </div>

    <!-- Sección: Equipo y Personal de tu Sucursal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Personal y Distribuidores de {{ $operador->sucursal?->nombre ?? 'tu Sucursal' }}
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Control de cuentas y perfiles de los usuarios que operan en esta sucursal.</p>
            </div>
            <a href="{{ route('usuarios.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                Administrar usuarios &rarr;
            </a>
        </div>

        @if($personalSucursal->isEmpty())
            <div class="p-12 text-center text-slate-500 text-sm">
                No hay usuarios asignados a esta sucursal aún.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Usuario / Nombre</th>
                            <th class="px-6 py-3.5">Correo Electrónico</th>
                            <th class="px-6 py-3.5">Rol</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($personalSucursal as $usuario)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-xs">
                                            {{ strtoupper(substr($usuario->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ $usuario->name }}</div>
                                            @if($usuario->esDistribuidor() && $usuario->categoria_distribuidor)
                                                <span class="text-[10px] text-amber-400 font-bold uppercase">Categoría {{ $usuario->categoria_distribuidor }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                    {{ $usuario->email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                                        @if($usuario->esGerenteSucursal()) bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                                        @elseif($usuario->esDistribuidor()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @elseif($usuario->esCajero()) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @else bg-slate-800 text-slate-300 border border-slate-700 @endif">
                                        {{ $usuario->rol?->nombre ?? 'Sin Rol' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($usuario->activo)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('usuarios.show', $usuario) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
                                            Ver Perfil
                                        </a>
                                        <a href="{{ route('usuarios.edit', $usuario) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Sección: Solicitudes de Traspaso de Distribuidoras (Pendientes de Autorización Gerencial) -->
    @if(isset($transferenciasPendientesGerente) && $transferenciasPendientesGerente->count() > 0)
        <div class="bg-slate-900 border border-purple-500/40 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-800 bg-purple-950/20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Traspasos de Distribuidora Pendientes de tu Aprobación</h2>
                        <p class="text-slate-400 text-xs mt-0.5">El coordinador receptor aceptó la solicitud. Se requiere tu autorización final para incorporar la distribuidora y su cartera a esta sucursal.</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach($transferenciasPendientesGerente as $tpg)
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-800/30 transition">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-white text-sm">{{ $tpg->distribuidor?->name }}</span>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                    Cat. {{ $tpg->distribuidor?->categoria_distribuidor ?? 'Estándar' }}
                                </span>
                                <span class="text-xs text-slate-500 font-mono">Ref: {{ $tpg->distribuidor?->referenciaPago() }}</span>
                            </div>
                            <p class="text-xs text-slate-300">
                                Transferencia de: <strong class="text-slate-200">{{ $tpg->coordinadorEmisor?->name }}</strong> (Sucursal: {{ $tpg->sucursalOrigen?->nombre }}) 
                                &rarr; Al coordinador: <strong class="text-indigo-300">{{ $tpg->coordinadorReceptor?->name }}</strong> (Tu sucursal)
                            </p>
                            <p class="text-xs text-slate-400 italic bg-slate-950/40 p-2 rounded-lg border border-slate-800">
                                "{{ $tpg->motivo }}"
                            </p>
                        </div>

                        <div class="shrink-0">
                            <a href="{{ route('gerente-sucursal.transferencias.revisar', $tpg) }}" 
                               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-900/30 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Revisar y Dictaminar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sección: Solicitudes de Incremento de Crédito Pendientes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Solicitudes de Incremento de Crédito (Distribuidoras)
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Autoriza o rechaza las peticiones de aumento de límite de crédito enviadas por tus coordinadores.</p>
            </div>
        </div>

        @if($solicitudesCreditoPendientes->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm font-medium">
                No hay solicitudes de incremento de crédito pendientes.
            </div>
        @else
            <div class="overflow-x-auto" x-data="{ openCommentId: null }">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Distribuidora</th>
                            <th class="px-6 py-3.5">Coordinador</th>
                            <th class="px-6 py-3.5 text-right">Límite Actual</th>
                            <th class="px-6 py-3.5 text-right">Nuevo Límite</th>
                            <th class="px-6 py-3.5">Motivo</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($solicitudesCreditoPendientes as $sol)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white text-sm">{{ $sol->distribuidor?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $sol->distribuidor?->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium text-slate-200">{{ $sol->coordinador?->name }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-400 text-xs">
                                    ${{ number_format($sol->limite_actual, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-emerald-400 font-bold text-sm">
                                    ${{ number_format($sol->limite_nuevo, 2) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 max-w-xs whitespace-normal">
                                    "{{ $sol->motivo }}"
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openCommentId = (openCommentId === {{ $sol->id }} ? null : {{ $sol->id }})"
                                                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
                                            Responder
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Fila de respuesta/comentario -->
                            <tr x-show="openCommentId === {{ $sol->id }}" class="bg-slate-950/40" style="display: none;">
                                <td colspan="6" class="px-6 py-4">
                                    <form method="POST" action="{{ route('solicitudes-credito.procesar', $sol->id) }}" class="space-y-3">
                                        @csrf
                                        <input type="hidden" name="accion" id="accion_sucursal_{{ $sol->id }}">
                                        
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Observaciones / Retroalimentación</label>
                                            <textarea name="observaciones" rows="2" placeholder="Opcional. Escribe notas sobre tu decisión..."
                                                      class="w-full bg-slate-900 border border-slate-850 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none transition"></textarea>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                             <button type="submit" onclick="document.getElementById('accion_sucursal_{{ $sol->id }}').value = 'rechazar'; return confirm('¿Rechazar este incremento de crédito?')"
                                                     class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                                                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                 Rechazar
                                             </button>
                                             <button type="submit" onclick="document.getElementById('accion_sucursal_{{ $sol->id }}').value = 'aprobar'; return confirm('¿Aprobar este incremento de crédito?')"
                                                     class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/20 text-xs font-bold transition">
                                                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                 Aprobar Incremento
                                             </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Sección: Solicitudes de Distribuidor (Decisión Final del Gerente) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Solicitudes de Nuevas Distribuidoras (Revisión de Gerencia)
            </h2>
            <p class="text-slate-400 text-xs mt-0.5">Evalúa el dictamen del Verificador y toma la decisión final para aprobar o rechazar a estas candidatas.</p>
        </div>

        @if($solicitudesEnEspera->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                No hay solicitudes de distribuidores pendientes de tu revisión final.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Candidata</th>
                            <th class="px-6 py-3.5">Coordinador</th>
                            <th class="px-6 py-3.5">Dictamen Verificador</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($solicitudesEnEspera as $sol)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white text-sm">{{ $sol->nombre_completo }}</div>
                                    <div class="text-xs text-slate-500">CURP: {{ $sol->curp }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium text-slate-200">{{ $sol->coordinador?->name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($sol->dictamen_verificador === 'aceptado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold uppercase tracking-wider">
                                            Aceptado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-500/10 text-rose-400 border border-rose-500/20 text-xs font-bold uppercase tracking-wider">
                                            Rechazado
                                        </span>
                                    @endif
                                    <div class="text-xs text-slate-400 mt-1 max-w-xs whitespace-normal italic">
                                        "{{ $sol->comentarios_verificador ?? 'Sin comentarios' }}"
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Por: {{ $sol->verificador?->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('gerente-sucursal.solicitudes.comparar', $sol->id) }}" 
                                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-md shadow-indigo-950/20 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Comparar y Dictaminar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Sección: Solicitudes de Distribuidoras Aprobadas (Pendiente de Cuenta) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden" 
         x-data="{ showAccountModal: false, selSolId: '', selSolName: '' }">
        
        <div class="p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Solicitudes de Distribuidoras Aprobadas (Pendiente de Cuenta)
            </h2>
            <p class="text-slate-400 text-xs mt-0.5">Asigna el correo institucional y contraseña para dar de alta definitiva en el sistema a las distribuidoras ya verificadas.</p>
        </div>

        @if($solicitudesAprobadasSinCuenta->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm font-medium">
                No hay distribuidoras aprobadas pendientes de asignación de cuenta.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Candidata</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Verificado Por</th>
                            <th class="px-6 py-3.5">Coordinador</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($solicitudesAprobadasSinCuenta as $sol)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white text-sm">{{ $sol->nombre_completo }}</div>
                                    <div class="text-xs text-slate-500">CURP: {{ $sol->curp }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                    {{ $sol->telefono }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <span class="text-emerald-400 font-semibold">{{ $sol->verificador?->name ?? 'N/A' }}</span>
                                    <span class="block text-[10px] text-slate-500">Aprobado el {{ $sol->resolved_at?->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    {{ $sol->coordinador?->name }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="selSolId = '{{ $sol->id }}'; selSolName = '{{ $sol->nombre_completo }}'; showAccountModal = true"
                                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow-md shadow-emerald-950/20 transition">
                                        Crear Cuenta
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Modal de Creación de Cuenta (Alpine.js) -->
        <div x-show="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none" style="display: none;" x-transition>
            <!-- Overlay -->
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showAccountModal = false"></div>
            
            <!-- Modal content -->
            <div class="relative w-full max-w-md mx-auto my-6 px-4 z-50">
                <div class="relative flex flex-col w-full bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl outline-none focus:outline-none p-6 text-left">
                    <!-- Header -->
                    <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-lg font-bold text-white">Crear Cuenta de Acceso</h3>
                        <button type="button" @click="showAccountModal = false" class="text-slate-400 hover:text-white text-lg font-bold leading-none">&times;</button>
                    </div>
                    
                    <!-- Body -->
                    <form :action="`/solicitudes-distribuidor/${selSolId}/crear-cuenta`" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Distribuidora</label>
                            <input type="text" readonly :value="selSolName" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-300 px-4 py-2.5 text-sm focus:outline-none select-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Correo Electrónico Corporativo *</label>
                            <input type="email" name="email" required placeholder="ejemplo@prestafacil.com"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Contraseña Inicial *</label>
                            <input type="password" name="password" required placeholder="Mínimo 6 caracteres"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800 mt-6">
                            <button type="button" @click="showAccountModal = false" class="px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-semibold transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-900/20 text-xs font-bold tracking-wide transition">
                                Registrar y Activar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Principal: Préstamos Activos por Distribuidora de esta Sucursal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden space-y-6">
        <div class="p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Préstamos Activos por Distribuidora en {{ $operador->sucursal?->nombre ?? 'tu Sucursal' }}
            </h2>
            <p class="text-slate-400 text-xs mt-0.5">Cartera de préstamos vigentes otorgados por las distribuidoras asignadas a tu sucursal.</p>
        </div>

        @if($distribuidores->isEmpty())
            <div class="p-12 text-center text-slate-500 text-sm">
                No hay distribuidoras asignadas a esta sucursal.
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
                                        Ref: <span class="font-mono text-indigo-300">{{ $dist->referenciaPago() }}</span> 
                                        &bull; {{ $dist->email }}
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

    <!-- Sección: Solicitudes de Traspaso de Coordinadores Recibidas (Paso 1) -->
    @if(isset($transferenciasCoordinadorRecibidas) && $transferenciasCoordinadorRecibidas->count() > 0)
        <div class="bg-slate-900 border border-amber-500/40 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-800 bg-amber-950/20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-300 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Solicitudes de Traspaso de Coordinador Recibidas</h2>
                        <p class="text-slate-400 text-xs mt-0.5">Otro Gerente te solicita transferir un Coordinador a tu sucursal. Al aceptar, pasará al Gerente General para su autorización final.</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach($transferenciasCoordinadorRecibidas as $tcr)
                    <div class="p-5 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-white text-base">{{ $tcr->coordinador?->name }}</span>
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        Coordinador
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300">
                                    Sucursal Origen: <strong class="text-slate-200">{{ $tcr->sucursalOrigen?->nombre }}</strong> (Gerente: {{ $tcr->gerenteEmisor?->name }})
                                </p>
                                <p class="text-xs text-slate-400 italic bg-slate-950/40 p-2.5 rounded-xl border border-slate-800">
                                    "{{ $tcr->motivo }}"
                                </p>
                            </div>

                            <div class="shrink-0">
                                <button @click="openDecisionCoordId = (openDecisionCoordId === '{{ $tcr->id }}' ? null : '{{ $tcr->id }}')" 
                                        class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-950/20 transition">
                                    Revisar y Dictaminar
                                </button>
                            </div>
                        </div>

                        <!-- Panel desplegable de decisión -->
                        <div x-show="openDecisionCoordId === '{{ $tcr->id }}'" class="p-4 bg-slate-950/70 border border-slate-850 rounded-xl space-y-3" style="display: none;" x-transition>
                            <form method="POST" action="{{ route('gerente-sucursal.coordinadores.traspaso.decidir', $tcr) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="accion" id="dec_coord_gs_{{ $tcr->id }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Observaciones / Notas (Opcional)</label>
                                    <textarea name="observaciones" rows="2" placeholder="Comentarios sobre la recepción..." class="w-full bg-slate-900 border border-slate-800 rounded-xl text-white px-4 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="submit" onclick="document.getElementById('dec_coord_gs_{{ $tcr->id }}').value = 'rechazar'; return confirm('¿Rechazar el traspaso del coordinador?')" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Rechazar
                                    </button>
                                    <button type="submit" onclick="document.getElementById('dec_coord_gs_{{ $tcr->id }}').value = 'aceptar'; return confirm('¿Aceptar el traspaso? Pasará al Gerente General para su visto bueno final.')" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg text-xs font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Aceptar y Enviar a Gerencia General
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Modal Solicitar Traspaso de Coordinador -->
    <div x-show="showTraspasoCoordModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showTraspasoCoordModal = false"></div>
        <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 z-50 text-left space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Solicitar Traspaso de Coordinador
                </h3>
                <button @click="showTraspasoCoordModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('gerente-sucursal.coordinadores.traspasar') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Coordinador a Transferir *</label>
                    <select name="coordinador_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Selecciona un Coordinador de tu sucursal --</option>
                        @foreach($coordinadoresSucursal as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->distribuidoresCoordinados->count() }} distribuidoras)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Gerente y Sucursal Destino *</label>
                    <select name="gerente_receptor_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Selecciona el Gerente Destino --</option>
                        @foreach($otrosGerentesSucursal as $og)
                            <option value="{{ $og->id }}">{{ $og->name }} - {{ $og->sucursal?->nombre ?? 'Sucursal' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Motivo del Traspaso *</label>
                    <textarea name="motivo" rows="3" required placeholder="Explica las razones del traspaso..." class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showTraspasoCoordModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold">Enviar Solicitud al Gerente Destino</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
