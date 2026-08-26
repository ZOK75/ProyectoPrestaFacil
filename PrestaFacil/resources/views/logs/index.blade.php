@extends('layouts.app')

@section('title', 'Logs del Sistema y Auditoría - PrestaFácil')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="liveLogsData()" x-init="init()">

    <!-- Encabezado y Barra de Transmisión en Tiempo Real -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
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
                <p class="text-xs text-slate-400 mt-0.5">Trazabilidad de operaciones en tiempo real, eventos de auditoría y registros de errores del servidor.</p>
            </div>
        </div>

        <!-- Controles de Tiempo Real y Tabs -->
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <!-- Indicador y Toggle de Tiempo Real -->
            <div class="flex items-center gap-2 bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-800">
                <button @click="toggleLive()" 
                        :class="autoRefresh ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border transition">
                    <span class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'"></span>
                    <span class="w-2 h-2 rounded-full absolute" :class="autoRefresh ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                    <span class="ml-2" x-text="autoRefresh ? 'EN VIVO (3s)' : 'PAUSADO'"></span>
                </button>

                <button @click="fetchLogs(true)" 
                        :class="{ 'animate-spin': isFetching }"
                        title="Actualizar ahora"
                        class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>

            <!-- Selector de Tabs -->
            <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800">
                <button @click="activeTab = 'auditoria'" 
                    :class="activeTab === 'auditoria' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Auditoría (BD)
                </button>

                <button @click="activeTab = 'sistema'" 
                    :class="activeTab === 'sistema' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Logs del Sistema
                </button>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────── -->
    <!-- TAB 1: LOGS DE AUDITORÍA (BASE DE DATOS) -->
    <!-- ────────────────────────────────────────── -->
    <div x-show="activeTab === 'auditoria'" class="space-y-4">
        
        <!-- Filtros de Auditoría -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Búsqueda General -->
                <div class="relative sm:col-span-2">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="searchAudit" @input.debounce.400ms="fetchLogs(true)" placeholder="Buscar en vivo por descripción, rol, IP o entidad..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Filtro por Tipo de Operación -->
                <div class="flex gap-2">
                    <select x-model="tipoOperacion" @change="fetchLogs(true)" class="flex-1 px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Todas las operaciones</option>
                        @foreach($tiposOperacion as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>

                    <button type="button" @click="fetchLogs(true)" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition shrink-0">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Auditoría en Tiempo Real -->
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
                        <template x-for="log in auditLogs" :key="log.id">
                            <tr class="hover:bg-slate-800/40 transition-colors duration-300"
                                :class="log.isNew ? 'bg-indigo-950/40 border-l-2 border-indigo-500' : ''">
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    <span x-text="log.fecha_hora"></span>
                                    <span class="block text-[9px] text-slate-500 font-sans" x-text="log.fecha_human"></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                          :class="getBadgeClass(log.tipo_operacion)"
                                          x-text="log.tipo_operacion">
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-white font-bold block" x-text="log.user_rol || 'Sistema'"></span>
                                    <span class="text-[10px] text-slate-500 font-mono" x-text="log.user_name || 'N/A'"></span>
                                </td>
                                <td class="px-4 py-3 text-slate-200 max-w-xs truncate" :title="log.descripcion" x-text="log.descripcion"></td>
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-400 whitespace-nowrap" x-text="log.ip_address || '127.0.0.1'"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button x-show="log.datos_anteriores || log.datos_nuevos || log.detalles" 
                                            @click="openModal(log)"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-bold transition">
                                        Ver JSON
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="auditLogs.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                No se encontraron registros de auditoría que coincidan con los filtros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────── -->
    <!-- TAB 2: LOGS DEL SISTEMA (LARAVEL.LOG) -->
    <!-- ────────────────────────────────────────── -->
    <div x-show="activeTab === 'sistema'" class="space-y-4" style="display: none;">
        
        <!-- Filtros de Logs del Sistema -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Búsqueda en texto del log -->
                <div class="relative sm:col-span-2">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="searchSystem" @input.debounce.400ms="fetchLogs(true)" placeholder="Buscar en mensaje o traza de error en tiempo real..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Filtro por Nivel de Severidad -->
                <div class="flex gap-2">
                    <select x-model="levelSystem" @change="fetchLogs(true)" class="flex-1 px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Todos los niveles</option>
                        <option value="error">ERROR / CRITICAL</option>
                        <option value="warning">WARNING</option>
                        <option value="info">INFO / NOTICE</option>
                        <option value="debug">DEBUG</option>
                    </select>

                    <button type="button" @click="fetchLogs(true)" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition shrink-0">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Flujo de Registros de Log del Sistema en Tiempo Real -->
        <div class="space-y-3">
            <template x-for="log in systemLogs" :key="log.id">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg space-y-2 relative transition-all duration-300">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-800/80 pb-2">
                        <div class="flex items-center gap-2">
                            <!-- Badge de Nivel -->
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase"
                                  :class="getSystemLevelBadge(log.level)"
                                  x-text="log.level">
                            </span>

                            <span class="text-[11px] font-mono text-slate-400 font-semibold" x-text="log.timestamp"></span>
                            <span class="text-[10px] font-mono text-slate-500 uppercase" x-text="'[' + log.env + ']'"></span>
                        </div>

                        <template x-if="log.stack_trace">
                            <button @click="toggleStackTrace(log.id)"
                                class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-bold transition flex items-center gap-1">
                                <span x-text="openStackTraceId === log.id ? 'Ocultar Traza' : 'Ver Stack Trace'"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Mensaje Principal -->
                    <div class="font-mono text-xs text-slate-200 bg-slate-950 p-3 rounded-xl border border-slate-800/80 break-words" x-text="log.message"></div>

                    <!-- Stack Trace Desplegable -->
                    <template x-if="log.stack_trace && openStackTraceId === log.id">
                        <div class="pt-2">
                            <pre class="font-mono text-[10px] text-slate-400 bg-slate-950/90 p-3.5 rounded-xl border border-slate-800 overflow-x-auto max-h-80 leading-relaxed" x-text="log.stack_trace"></pre>
                        </div>
                    </template>
                </div>
            </template>

            <div x-show="systemLogs.length === 0" class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 space-y-2">
                <svg class="w-8 h-8 mx-auto text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-xs font-semibold text-slate-400">No hay registros en el archivo de log que coincidan con la búsqueda.</p>
            </div>
        </div>
    </div>

    <!-- Modal para Detalle JSON de Auditoría -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden" @click.away="modalOpen = false">
            
            <div class="bg-slate-950 p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-white" x-text="'Detalle de Auditoría: ' + (modalData ? modalData.tipo_operacion : '')"></h3>
                    <p class="text-[11px] text-slate-400" x-text="modalData ? modalData.fecha_hora : ''"></p>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
            </div>

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="space-y-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Descripción:</span>
                    <p class="text-xs text-slate-200 bg-slate-950 p-2.5 rounded-xl border border-slate-800" x-text="modalData ? modalData.descripcion : ''"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="modalData && (modalData.datos_anteriores || modalData.datos_nuevos || modalData.detalles)">
                    <div x-show="modalData && modalData.datos_anteriores">
                        <span class="text-[10px] uppercase font-bold text-amber-400 block mb-1">Datos Anteriores:</span>
                        <pre class="font-mono text-[10px] text-amber-300/90 bg-slate-950 p-3 rounded-xl border border-amber-900/30 overflow-x-auto max-h-60" x-text="modalData && modalData.datos_anteriores ? JSON.stringify(modalData.datos_anteriores, null, 2) : 'Sin datos'"></pre>
                    </div>

                    <div x-show="modalData && modalData.datos_nuevos">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 block mb-1">Datos Nuevos:</span>
                        <pre class="font-mono text-[10px] text-emerald-300/90 bg-slate-950 p-3 rounded-xl border border-emerald-900/30 overflow-x-auto max-h-60" x-text="modalData && modalData.datos_nuevos ? JSON.stringify(modalData.datos_nuevos, null, 2) : 'Sin datos'"></pre>
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

<script>
    function liveLogsData() {
        return {
            activeTab: '{{ $tab ?? 'auditoria' }}',
            autoRefresh: true,
            isFetching: false,
            pollTimer: null,
            searchAudit: '{{ request('buscar_auditoria', '') }}',
            tipoOperacion: '{{ request('tipo_operacion', '') }}',
            searchSystem: '{{ request('buscar_sistema', '') }}',
            levelSystem: '{{ request('nivel_sistema', '') }}',
            auditLogs: @json($auditLogs->items() ?? []),
            systemLogs: @json($systemLogs ?? []),
            modalOpen: false,
            modalData: null,
            openStackTraceId: null,

            init() {
                // Adaptar audit logs iniciales
                this.auditLogs = this.auditLogs.map(log => ({
                    id: String(log.id),
                    fecha_hora: log.created_at ? new Date(log.created_at).toLocaleString('es-MX') : '',
                    fecha_human: 'hace un momento',
                    tipo_operacion: log.tipo_operacion,
                    user_name: log.user_name || (log.usuario ? log.usuario.name : 'Sistema'),
                    user_rol: log.user_rol || (log.usuario && log.usuario.rol ? log.usuario.rol.nombre : 'N/A'),
                    descripcion: log.descripcion,
                    ip_address: log.ip_address || '127.0.0.1',
                    datos_anteriores: log.datos_anteriores,
                    datos_nuevos: log.datos_nuevos,
                    detalles: log.detalles,
                    isNew: false
                }));

                this.fetchLogs();
                this.startPolling();
            },

            startPolling() {
                if (this.pollTimer) clearInterval(this.pollTimer);
                this.pollTimer = setInterval(() => {
                    if (this.autoRefresh && !this.modalOpen) {
                        this.fetchLogs(false);
                    }
                }, 3000);
            },

            toggleLive() {
                this.autoRefresh = !this.autoRefresh;
            },

            async fetchLogs(manual = false) {
                if (this.isFetching) return;
                this.isFetching = true;

                try {
                    const params = new URLSearchParams({
                        buscar_auditoria: this.searchAudit,
                        tipo_operacion: this.tipoOperacion,
                        buscar_sistema: this.searchSystem,
                        nivel_sistema: this.levelSystem,
                    });

                    const res = await fetch(`{{ route('logs.live') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.audit_logs) {
                            const currentIds = new Set(this.auditLogs.map(l => l.id));
                            const updatedAuditLogs = data.audit_logs.map(l => ({
                                ...l,
                                isNew: !currentIds.has(l.id) && !manual
                            }));
                            this.auditLogs = updatedAuditLogs;
                        }

                        if (data.system_logs) {
                            this.systemLogs = data.system_logs;
                        }
                    }
                } catch (e) {
                } finally {
                    this.isFetching = false;
                }
            },

            openModal(log) {
                this.modalData = log;
                this.modalOpen = true;
            },

            toggleStackTrace(id) {
                this.openStackTraceId = this.openStackTraceId === id ? null : id;
            },

            getBadgeClass(op) {
                op = (op || '').toUpperCase();
                if (op.includes('DELETE') || op.includes('DESACTIVA') || op.includes('RECHAZ') || op.includes('CANCEL')) {
                    return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
                }
                if (op.includes('CREA') || op.includes('REGISTRO') || op.includes('ENTREGA') || op.includes('PAGO') || op.includes('ABONO') || op.includes('APROB') || op.includes('ACEPT')) {
                    return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                }
                if (op.includes('CONFIG') || op.includes('ACTUALIZA') || op.includes('MODIFICA') || op.includes('TRASPASO') || op.includes('REASIGNA')) {
                    return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
                }
                return 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20';
            },

            getSystemLevelBadge(lvl) {
                lvl = (lvl || '').toUpperCase();
                if (['ERROR', 'CRITICAL', 'EMERGENCY'].includes(lvl)) {
                    return 'bg-rose-500/10 text-rose-400 border border-rose-500/30';
                }
                if (lvl === 'WARNING') {
                    return 'bg-amber-500/10 text-amber-400 border border-amber-500/30';
                }
                if (['INFO', 'NOTICE'].includes(lvl)) {
                    return 'bg-blue-500/10 text-blue-400 border border-blue-500/30';
                }
                return 'bg-slate-800 text-slate-400 border border-slate-700';
            }
        };
    }
</script>
@endsection
