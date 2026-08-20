@extends('layouts.app')

@section('title', 'Panel de Gerencia General - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Gerente General / Auditor -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    @if(Auth::user()->esAdministrador())
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                            🔒 Auditoría y Supervisión
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            Gerencia General
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        Corporativo Nacional
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    {{ Auth::user()->esAdministrador() ? 'Panel de Auditoría del Sistema' : 'Bienvenido, ' . $operador->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Supervisión integral de la red de sucursales, usuarios, reglas financieras y políticas del sistema.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('sucursales.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Gestión Sucursales
                </a>

                <a href="{{ route('configuracion-general.edit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Reglas y Configuración
                </a>

                <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Gestión de Usuarios
                </a>

                <a href="{{ route('producto-vales.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                    Catálogo de Vales
                </a>

                @if(Auth::user()->esAdministrador())
                    <a href="{{ route('logs.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Visor de Logs
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- KPIs Corporativos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sucursales Activas</span>
            <div class="text-2xl font-black text-white mt-2">{{ number_format($statsCorporativas['total_sucursales']) }}</div>
            <p class="text-xs text-indigo-400 mt-1">Red en operación</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Total Personal</span>
            <div class="text-2xl font-black text-emerald-300 mt-2">{{ number_format($statsCorporativas['total_usuarios']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Usuarios corporativos activos</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Red de Distribución</span>
            <div class="text-2xl font-black text-amber-300 mt-2">{{ number_format($statsCorporativas['distribuidores']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Distribuidores activos</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-violet-400 uppercase tracking-wider">Productos de Vales</span>
            <div class="text-2xl font-black text-violet-300 mt-2">{{ number_format($statsCorporativas['vales_catalogo']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Tipos de vales en catálogo</p>
        </div>
    </div>

    <!-- Reglas de Corte y Configuración Activa -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                    ⚙️
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Reglas del Sistema y Políticas Financieras Vigentes</h2>
                    <p class="text-xs text-slate-400">Parámetros de corte, vencimientos, multas y comisiones automáticas</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('configuracion-general.simular-corte') }}" method="POST" onsubmit="return confirm('¿Deseas simular y ejecutar el siguiente corte quincenal? Se procesarán los vales y abonos, se aplicarán las multas moratorias por vale y se avanzará el ciclo 15 días (+15d).');">
                    @csrf
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white text-xs font-black shadow-md shadow-orange-950/30 transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        ⚡ Simular Corte (+15d)
                    </button>
                </form>
                @if(!Auth::user()->esAdministrador())
                    <a href="{{ route('configuracion-general.edit') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                        Modificar Configuración &rarr;
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Día y Hora de Corte</span>
                <span class="text-sm font-extrabold text-white">Día {{ $configuracion->dia_corte }} @ {{ $configuracion->hora_corte ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Cierre de quincena</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Fecha Límite de Pago</span>
                <span class="text-sm font-extrabold text-amber-400">Día {{ $configuracion->dia_limite_pago }} @ {{ $configuracion->hora_limite_pago ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Límite para liquidar quincena</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Programa de Puntos</span>
                <span class="text-sm font-extrabold text-emerald-400">${{ number_format($configuracion->monto_base_puntos ?? 1200, 0) }} &rarr; {{ $configuracion->puntos_por_monto_base ?? 3 }} pts</span>
                <span class="text-[10px] text-slate-500 block">Valor: ${{ number_format($configuracion->valor_punto ?? 2.00, 2) }}/punto</span>
            </div>
        </div>
    </div>

    <!-- Sección: Solicitudes de Traspaso de Coordinadores (Paso 2: Autorización Final Gerencia General) -->
    @if(isset($transferenciasCoordinadorPendientesGG) && $transferenciasCoordinadorPendientesGG->count() > 0)
        <div class="bg-slate-900 border border-amber-500/40 rounded-2xl shadow-xl overflow-hidden" x-data="{ openDecisionCoordId: null }">
            <div class="p-6 border-b border-slate-800 bg-amber-950/20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-300 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Traspasos de Coordinador Pendientes de Aprobación Final</h2>
                        <p class="text-slate-400 text-xs mt-0.5">Ambos Gerentes de Sucursal acordaron el traspaso. Como Gerente General debes emitir la autorización final (Propagación en Cascada).</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach($transferenciasCoordinadorPendientesGG as $tc)
                    <div class="p-5 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-white text-base">{{ $tc->coordinador?->name }}</span>
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        Coordinador
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300">
                                    Sucursal Origen: <strong class="text-slate-200">{{ $tc->sucursalOrigen?->nombre }}</strong> (Gerente: {{ $tc->gerenteEmisor?->name }}) 
                                    &rarr; Sucursal Destino: <strong class="text-emerald-400">{{ $tc->sucursalDestino?->nombre }}</strong> (Gerente: {{ $tc->gerenteReceptor?->name }})
                                </p>
                                <p class="text-xs text-slate-400 italic bg-slate-950/40 p-2.5 rounded-xl border border-slate-800">
                                    "{{ $tc->motivo }}"
                                </p>
                            </div>

                            <div class="shrink-0">
                                <button @click="openDecisionCoordId = (openDecisionCoordId === '{{ $tc->id }}' ? null : '{{ $tc->id }}')" 
                                        class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-950/20 transition">
                                    Evaluar Aprobación Final
                                </button>
                            </div>
                        </div>

                        <!-- Panel desplegable de decisión -->
                        <div x-show="openDecisionCoordId === '{{ $tc->id }}'" class="p-4 bg-slate-950/70 border border-slate-850 rounded-xl space-y-3" style="display: none;" x-transition>
                            <form method="POST" action="{{ route('gerente-general.coordinadores.traspaso.decidir-final', $tc) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="accion" id="dec_coord_gg_{{ $tc->id }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Observaciones Corporativas (Opcional)</label>
                                    <textarea name="observaciones" rows="2" placeholder="Notas sobre esta aprobación o motivo de rechazo..." class="w-full bg-slate-900 border border-slate-800 rounded-xl text-white px-4 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="submit" onclick="document.getElementById('dec_coord_gg_{{ $tc->id }}').value = 'rechazar'; return confirm('¿Rechazar el traspaso del coordinador?')" class="px-4 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                                        ✕ Rechazar Traspaso
                                    </button>
                                    <button type="submit" onclick="document.getElementById('dec_coord_gg_{{ $tc->id }}').value = 'aprobar'; return confirm('¿Aprobar traspaso? El Coordinador y sus Distribuidoras se moverán en cascada a la nueva sucursal.')" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg text-xs font-bold transition">
                                        ✓ Aprobar Traspaso en Cascada
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sección: Solicitudes de Traspaso de Distribuidoras (Pendientes de Autorización Corporativa / Gerencial) -->
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
                        <h2 class="text-lg font-bold text-white">Traspasos de Distribuidora Pendientes de Autorización</h2>
                        <p class="text-slate-400 text-xs mt-0.5">El coordinador receptor aceptó la solicitud. Como Gerente General puedes revisar y autorizar el traspaso a nivel corporativo.</p>
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
                                De: <strong class="text-slate-200">{{ $tpg->coordinadorEmisor?->name }}</strong> (Sucursal: {{ $tpg->sucursalOrigen?->nombre }}) 
                                &rarr; Hacia: <strong class="text-indigo-300">{{ $tpg->coordinadorReceptor?->name }}</strong> (Sucursal: {{ $tpg->sucursalDestino?->nombre }})
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

    <!-- Sección: Solicitudes de Distribuidor (Decisión Final del Gerente General) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Solicitudes de Nuevas Distribuidoras (Revisión de Gerencia General)
            </h2>
            <p class="text-slate-400 text-xs mt-0.5">Evalúa el dictamen del Verificador y toma la decisión final para aprobar o rechazar a estas candidatas a nivel nacional.</p>
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
                            <th class="px-6 py-3.5">Sucursal / Coordinador</th>
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
                                    <div class="text-xs font-bold text-indigo-300">{{ $sol->sucursal?->nombre }}</div>
                                    <div class="text-[10px] text-slate-400">Coord: {{ $sol->coordinador?->name }}</div>
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
                                    <a href="{{ route('gerente-general.solicitudes.comparar', $sol->id) }}" 
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
            <div class="p-8 text-center text-slate-500 text-sm">
                🎉 No hay distribuidoras aprobadas pendientes de asignación de cuenta.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Candidata</th>
                            <th class="px-6 py-3.5">Sucursal</th>
                            <th class="px-6 py-3.5">Teléfono</th>
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
                                <td class="px-6 py-4">
                                    <span class="text-indigo-300 font-bold text-xs">{{ $sol->sucursal?->nombre }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                    {{ $sol->telefono }}
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

        <!-- Modal de Creación de Cuenta -->
        <div x-show="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showAccountModal = false"></div>
            
            <div class="relative w-full max-w-md mx-auto my-6 px-4 z-50">
                <div class="relative flex flex-col w-full bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl outline-none focus:outline-none p-6 text-left">
                    <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-lg font-bold text-white">Crear Cuenta de Acceso</h3>
                        <button type="button" @click="showAccountModal = false" class="text-slate-400 hover:text-white text-lg font-bold leading-none">&times;</button>
                    </div>
                    
                    <form :action="`/solicitudes-distribuidor/${selSolId}/crear-cuenta`" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Distribuidora</label>
                            <input type="text" readonly :value="selSolName" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-300 px-4 py-2.5 text-sm focus:outline-none select-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Correo Electrónico *</label>
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

    <!-- Directorio de Sucursales -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Red de Sucursales y Desglose de Personal
            </h2>
            <a href="{{ route('usuarios.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                Ver todos los usuarios &rarr;
            </a>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($sucursales as $sucursal)
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-black text-sm">
                                {{ strtoupper(substr($sucursal->nombre, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-extrabold text-white text-sm">{{ $sucursal->nombre }}</h3>
                                <span class="text-[11px] text-slate-400 block">{{ $sucursal->ciudad ?? 'Sede Regional' }}</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Activa
                        </span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Gerente a Cargo:</span>
                            <span class="font-semibold text-slate-200">
                                {{ $sucursal->usuarios->firstWhere('rol.nombre', 'Gerente de Sucursal')?->name ?? 'Sin asignar' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Distribuidores:</span>
                            <span class="font-bold text-amber-400">
                                {{ $sucursal->usuarios->filter(fn($u) => $u->esDistribuidor())->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Cajeros:</span>
                            <span class="font-bold text-emerald-400">
                                {{ $sucursal->usuarios->filter(fn($u) => $u->esCajero())->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400 pt-2 border-t border-slate-800">
                            <span class="font-medium text-slate-300">Total Colaboradores:</span>
                            <span class="font-black text-white text-sm">{{ $sucursal->usuarios->count() }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('usuarios.index', ['sucursal_id' => $sucursal->id]) }}" class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-indigo-300 text-center transition block">
                            Ver Personal de Sucursal
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
