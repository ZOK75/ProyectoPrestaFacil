@extends('layouts.app')

@section('title', 'Logs del Sistema y Auditoría - PrestaFácil')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: '{{ $tab ?? 'auditoria' }}', modalOpen: false, modalData: null, stackTraceOpen: null }">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                    Centro de Logs y Auditoría
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        Supervisión
                    </span>
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">Trazabilidad de operaciones, eventos de auditoría y registros de errores del servidor.</p>
            </div>
        </div>

        <!-- Selector de Tabs -->
        <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 shrink-0">
            <button @click="activeTab = 'auditoria'" 
                :class="activeTab === 'auditoria' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Auditoría (BD)
            </button>

            <button @click="activeTab = 'sistema'" 
                :class="activeTab === 'sistema' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                Logs del Sistema (Laravel)
            </button>
        </div>
    </div>

    <!-- ────────────────────────────────────────── -->
    <!-- TAB 1: LOGS DE AUDITORÍA (BASE DE DATOS) -->
    <!-- ────────────────────────────────────────── -->
    <div x-show="activeTab === 'auditoria'" class="space-y-4">
        
        <!-- Filtros de Auditoría -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <form novalidate action="{{ route('logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="tab" value="auditoria">

                <!-- Búsqueda General -->
                <div class="relative sm:col-span-2">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="buscar_auditoria" value="{{ request('buscar_auditoria') }}" placeholder="Buscar por descripción, rol, IP o entidad..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Filtro por Tipo de Operación -->
                <div class="flex gap-2">
                    <select name="tipo_operacion" class="flex-1 px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Todas las operaciones</option>
                        @foreach($tiposOperacion as $tipo)
                            <option value="{{ $tipo }}" {{ request('tipo_operacion') === $tipo ? 'selected' : '' }}>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition shrink-0">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Auditoría -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Fecha / Hora</th>
                            <th class="px-4 py-3.5">Operación</th>
                            <th class="px-4 py-3.5">Usuario / Rol</th>
                            <th class="px-4 py-3.5">Descripción</th>
                            <th class="px-4 py-3.5">IP</th>
                            <th class="px-4 py-3.5 text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                        @if(str_contains($log->tipo_operacion, 'DELETE') || str_contains($log->tipo_operacion, 'DESACTIVACION') || str_contains($log->tipo_operacion, 'RECHAZO') || str_contains($log->tipo_operacion, 'CANCELACION')) bg-rose-500/10 text-rose-400 border border-rose-500/20
                                        @elseif(str_contains($log->tipo_operacion, 'CREACION') || str_contains($log->tipo_operacion, 'REGISTRO') || str_contains($log->tipo_operacion, 'ENTREGA') || str_contains($log->tipo_operacion, 'PAGO') || str_contains($log->tipo_operacion, 'ABONO') || str_contains($log->tipo_operacion, 'APROBAC') || str_contains($log->tipo_operacion, 'ACEPTAC') || str_contains($log->tipo_operacion, 'ASIGNACION')) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @elseif(str_contains($log->tipo_operacion, 'CONFIGURACION') || str_contains($log->tipo_operacion, 'ACTUALIZACION') || str_contains($log->tipo_operacion, 'MODIFICACION') || str_contains($log->tipo_operacion, 'TRASPASO') || str_contains($log->tipo_operacion, 'REASIGNACION') || str_contains($log->tipo_operacion, 'SIMULACION')) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 @endif">
                                        {{ $log->tipo_operacion }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-white font-bold block">{{ $log->user_rol ?? 'Sistema' }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">UID: {{ $log->user_id ?? 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-200 max-w-xs truncate" title="{{ $log->descripcion }}">
                                    {{ $log->descripcion }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($log->datos_antes || $log->datos_despues)
                                        <button @click="modalData = {{ json_encode($log) }}; modalOpen = true"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-bold transition">
                                            Ver JSON
                                        </button>
                                    @else
                                        <span class="text-slate-600 text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                    No se encontraron registros de auditoría que coincidan con los filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="px-4 py-3 border-t border-slate-800 bg-slate-950">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ────────────────────────────────────────── -->
    <!-- TAB 2: LOGS DEL SISTEMA (LARAVEL.LOG) -->
    <!-- ────────────────────────────────────────── -->
    <div x-show="activeTab === 'sistema'" class="space-y-4" style="display: none;">
        
        <!-- Filtros de Logs del Sistema -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <form novalidate action="{{ route('logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="tab" value="sistema">

                <!-- Búsqueda en texto del log -->
                <div class="relative sm:col-span-2">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="buscar_sistema" value="{{ request('buscar_sistema') }}" placeholder="Buscar en mensaje o traza de error..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Filtro por Nivel de Severidad -->
                <div class="flex gap-2">
                    <select name="nivel_sistema" class="flex-1 px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Todos los niveles</option>
                        <option value="error" {{ request('nivel_sistema') === 'error' ? 'selected' : '' }}>ERROR / CRITICAL</option>
                        <option value="warning" {{ request('nivel_sistema') === 'warning' ? 'selected' : '' }}>WARNING</option>
                        <option value="info" {{ request('nivel_sistema') === 'info' ? 'selected' : '' }}>INFO / NOTICE</option>
                        <option value="debug" {{ request('nivel_sistema') === 'debug' ? 'selected' : '' }}>DEBUG</option>
                    </select>

                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition shrink-0">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Flujo de Registros de Log del Sistema -->
        <div class="space-y-3">
            @forelse($systemLogs as $log)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg space-y-2 relative">
                    
                    <div class="flex items-center justify-between gap-3 border-b border-slate-800/80 pb-2">
                        <div class="flex items-center gap-2">
                            <!-- Badge de Nivel -->
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase
                                @if(in_array($log['level'], ['ERROR', 'CRITICAL', 'EMERGENCY'])) bg-rose-500/10 text-rose-400 border border-rose-500/30
                                @elseif($log['level'] === 'WARNING') bg-amber-500/10 text-amber-400 border border-amber-500/30
                                @elseif(in_array($log['level'], ['INFO', 'NOTICE'])) bg-blue-500/10 text-blue-400 border border-blue-500/30
                                @else bg-slate-800 text-slate-400 border border-slate-700 @endif">
                                {{ $log['level'] }}
                            </span>

                            <span class="text-[11px] font-mono text-slate-400 font-semibold">{{ $log['timestamp'] }}</span>
                            <span class="text-[10px] font-mono text-slate-500 uppercase">[{{ $log['env'] }}]</span>
                        </div>

                        @if(!empty($log['stack_trace']))
                            <button @click="stackTraceOpen = (stackTraceOpen === {{ $log['id'] }} ? null : {{ $log['id'] }})"
                                class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-bold transition flex items-center gap-1">
                                <span x-text="stackTraceOpen === {{ $log['id'] }} ? 'Ocultar Traza' : 'Ver Stack Trace'"></span>
                            </button>
                        @endif
                    </div>

                    <!-- Mensaje Principal -->
                    <div class="font-mono text-xs text-slate-200 bg-slate-950 p-3 rounded-xl border border-slate-800/80 break-words">
                        {{ $log['message'] }}
                    </div>

                    <!-- Stack Trace Desplegable -->
                    @if(!empty($log['stack_trace']))
                        <div x-show="stackTraceOpen === {{ $log['id'] }}" x-collapse class="pt-2">
                            <pre class="font-mono text-[10px] text-slate-400 bg-slate-950/90 p-3.5 rounded-xl border border-slate-800 overflow-x-auto max-h-80 leading-relaxed">{{ $log['stack_trace'] }}</pre>
                        </div>
                    @endif

                </div>
            @empty
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 space-y-2">
                    <svg class="w-8 h-8 mx-auto text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-xs font-semibold text-slate-400">No hay registros en el archivo de log que coincidan con la búsqueda.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal para Detalle JSON de Auditoría -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden" @click.away="modalOpen = false">
            
            <div class="bg-slate-950 p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-white" x-text="'Detalle de Auditoría: ' + (modalData ? modalData.tipo_operacion : '')"></h3>
                    <p class="text-[11px] text-slate-400" x-text="modalData ? modalData.created_at : ''"></p>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
            </div>

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="space-y-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Descripción:</span>
                    <p class="text-xs text-slate-200 bg-slate-950 p-2.5 rounded-xl border border-slate-800" x-text="modalData ? modalData.descripcion : ''"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="modalData && (modalData.datos_antes || modalData.datos_despues)">
                    <div x-show="modalData && modalData.datos_antes">
                        <span class="text-[10px] uppercase font-bold text-amber-400 block mb-1">Datos Anteriores (Antes):</span>
                        <pre class="font-mono text-[10px] text-amber-300/90 bg-slate-950 p-3 rounded-xl border border-amber-900/30 overflow-x-auto max-h-60" x-text="modalData && modalData.datos_antes ? JSON.stringify(modalData.datos_antes, null, 2) : 'Sin datos'"></pre>
                    </div>

                    <div x-show="modalData && modalData.datos_despues">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 block mb-1">Datos Nuevos (Después):</span>
                        <pre class="font-mono text-[10px] text-emerald-300/90 bg-slate-950 p-3 rounded-xl border border-emerald-900/30 overflow-x-auto max-h-60" x-text="modalData && modalData.datos_despues ? JSON.stringify(modalData.datos_despues, null, 2) : 'Sin datos'"></pre>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-950 border-t border-slate-800 text-right">
                <button @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
