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
                <p class="text-slate-400 text-sm mt-1">Supervisión integral de la red de sucursales, usuarios, reglas financieras y logs.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
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
            @if(!Auth::user()->esAdministrador())
                <a href="{{ route('configuracion-general.edit') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                    Modificar Configuración &rarr;
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Día y Hora de Corte</span>
                <span class="text-sm font-extrabold text-white">Día {{ $configuracion->dia_corte }} @ {{ $configuracion->hora_corte ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Cierre de quincena</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Fecha Límite de Pago</span>
                <span class="text-sm font-extrabold text-amber-400">Día {{ $configuracion->dia_limite_pago }} @ {{ $configuracion->hora_limite_pago ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Límite para liquidar sin multa</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Multa por Adeudo Vencido</span>
                <span class="text-sm font-extrabold text-rose-400">${{ number_format($configuracion->multa_adeudo, 2) }}</span>
                <span class="text-[10px] text-slate-500 block">Por distribuidora incumplida</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Programa de Puntos</span>
                <span class="text-sm font-extrabold text-emerald-400">${{ number_format($configuracion->monto_base_puntos ?? 1200, 0) }} &rarr; {{ $configuracion->puntos_por_monto_base ?? 3 }} pts</span>
                <span class="text-[10px] text-slate-500 block">Valor: ${{ number_format($configuracion->valor_punto ?? 2.00, 2) }}/punto</span>
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

        @if($solicitudesPendientes->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                🎉 No hay solicitudes pendientes de aprobación en este momento.
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
                <p class="text-slate-400 text-xs mt-0.5">Autorización global de aumentos de límite de crédito para distribuidoras de todas las sucursales.</p>
            </div>
        </div>

        @if($solicitudesCreditoPendientes->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                🎉 No hay solicitudes de incremento de crédito pendientes a nivel global.
            </div>
        @else
            <div class="overflow-x-auto" x-data="{ openCommentId: null }">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Distribuidora / Sucursal</th>
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
                                    <div class="text-xs text-indigo-400 font-semibold">{{ $sol->distribuidor?->sucursal?->nombre ?? 'Sin Sucursal' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $sol->distribuidor?->email }}</div>
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
                                        <input type="hidden" name="accion" id="accion_general_{{ $sol->id }}">
                                        
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Observaciones / Retroalimentación</label>
                                            <textarea name="observaciones" rows="2" placeholder="Opcional. Escribe notas sobre tu decisión..."
                                                      class="w-full bg-slate-900 border border-slate-850 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none transition"></textarea>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button type="submit" onclick="document.getElementById('accion_general_{{ $sol->id }}').value = 'rechazar'; return confirm('¿Rechazar este incremento de crédito?')"
                                                    class="px-4 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                                                ✕ Rechazar
                                            </button>
                                            <button type="submit" onclick="document.getElementById('accion_general_{{ $sol->id }}').value = 'aprobar'; return confirm('¿Aprobar este incremento de crédito?')"
                                                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/20 text-xs font-bold transition">
                                                ✓ Aprobar Incremento
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
            <p class="text-slate-400 text-xs mt-0.5">Asigna el correo institucional y contraseña para dar de alta definitiva en el sistema a las distribuidoras ya verificadas a nivel nacional.</p>
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
                            <th class="px-6 py-3.5">Candidata / Sucursal</th>
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
                                    <div class="text-xs text-indigo-400 font-semibold">{{ $sol->sucursal?->nombre ?? 'Sin Sucursal' }}</div>
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
