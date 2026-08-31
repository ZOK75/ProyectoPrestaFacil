@extends('layouts.app')

@section('title', 'Módulo de Conciliaciones de Pago - Gerencia')

@section('content')
<div class="space-y-6" x-data="{ 
    openModalDecidir: false, 
    modalAccion: 'aprobar', 
    modalConciliacionId: '', 
    modalRef: '', 
    modalMonto: '', 
    modalUrl: '',
    abrirModal(id, accion, ref, monto, url) {
        this.modalConciliacionId = id;
        this.modalAccion = accion;
        this.modalRef = ref;
        this.modalMonto = monto;
        this.modalUrl = url;
        this.openModalDecidir = true;
    }
}">

    <!-- Header Ejecutivo del Módulo -->
    <div class="relative overflow-hidden rounded-3xl bg-white border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                        Módulo Gerencial
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $operador->esGerenteGeneral() ? 'Dirección General' : ($operador->sucursal?->nombre ?? 'Sucursal') }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-2">
                    Conciliaciones de Pago
                </h1>
                <p class="text-slate-600 text-sm mt-1">Supervisión, dictamen de correcciones bancarias y auditoría de pagos conciliados.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ $operador->esGerenteGeneral() ? route('gerente-general.dashboard') : route('gerente-sucursal.dashboard') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Regresar al Dashboard
                </a>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas / Resumen -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100">
            <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Pendientes de Dictamen</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-amber-600">{{ number_format($conteos['pendientes']) }}</span>
                    <span class="text-xs text-slate-500">solicitudes</span>
                </div>
            </div>

            <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Monto Total Pendiente</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-slate-900 font-mono">${{ number_format($conteos['monto_pendiente'], 2) }}</span>
                </div>
            </div>

            <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Conciliadas / Aprobadas</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-emerald-600">{{ number_format($conteos['conciliadas']) }}</span>
                    <span class="text-xs text-slate-500">aplicadas</span>
                </div>
            </div>

            <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Rechazadas</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-rose-600">{{ number_format($conteos['rechazadas']) }}</span>
                    <span class="text-xs text-slate-500">denegadas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Filtro y Buscador -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Pestañas de Estado -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0">
                <a href="{{ route('gerente.conciliaciones.index', array_merge(request()->query(), ['estado' => 'pendientes'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 {{ $filtroEstado === 'pendientes' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>Pendientes</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $filtroEstado === 'pendientes' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }}">
                        {{ $conteos['pendientes'] }}
                    </span>
                </a>

                <a href="{{ route('gerente.conciliaciones.index', array_merge(request()->query(), ['estado' => 'conciliadas'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 {{ $filtroEstado === 'conciliadas' || $filtroEstado === 'aprobadas' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>Conciliadas / Aprobadas</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $filtroEstado === 'conciliadas' || $filtroEstado === 'aprobadas' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $conteos['conciliadas'] }}
                    </span>
                </a>

                <a href="{{ route('gerente.conciliaciones.index', array_merge(request()->query(), ['estado' => 'rechazadas'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 {{ $filtroEstado === 'rechazadas' ? 'bg-rose-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>Rechazadas</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $filtroEstado === 'rechazadas' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700' }}">
                        {{ $conteos['rechazadas'] }}
                    </span>
                </a>

                <a href="{{ route('gerente.conciliaciones.index', array_merge(request()->query(), ['estado' => 'todas'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 {{ $filtroEstado === 'todas' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>Historial Completo</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $filtroEstado === 'todas' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $conteos['todas'] }}
                    </span>
                </a>
            </div>

            <!-- Formulario de Búsqueda y Filtros -->
            <form novalidate method="GET" action="{{ route('gerente.conciliaciones.index') }}" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <input type="hidden" name="estado" value="{{ $filtroEstado }}">

                @if($operador->esGerenteGeneral() || $operador->esAdministrador())
                    <select name="sucursal_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">Todas las sucursales</option>
                        @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}" {{ $sucursalId == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="relative flex-1 sm:w-64">
                    <input type="text" 
                           name="buscar" 
                           value="{{ request('buscar') }}" 
                           placeholder="Buscar por ref, cajero, folio..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                    Filtrar
                </button>
                @if(request('buscar') || request('sucursal_id'))
                    <a href="{{ route('gerente.conciliaciones.index', ['estado' => $filtroEstado]) }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 text-xs font-bold transition">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Listado de Conciliaciones -->
        @if($conciliaciones->isEmpty())
            <div class="p-12 text-center text-slate-400 font-medium space-y-2">
                <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm">No se encontraron solicitudes de conciliación en esta categoría.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Referencias / Folio</th>
                            <th class="px-5 py-3.5">Distribuidora / Vales</th>
                            <th class="px-5 py-3.5">Cajero / Sede</th>
                            <th class="px-5 py-3.5 text-center">Fecha Pago</th>
                            <th class="px-5 py-3.5 text-right">Monto Conciliado</th>
                            <th class="px-5 py-3.5 text-center">Estado</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($conciliaciones as $c)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Referencias -->
                                <td class="px-5 py-4">
                                    <div class="font-mono font-bold text-slate-900 text-xs">
                                        {{ $c->referencia_conciliacion ?: 'Sin referencia' }}
                                    </div>
                                    @if($c->referencia_original && $c->referencia_original !== $c->referencia_conciliacion)
                                        <div class="text-[10px] text-rose-500 font-mono line-through">
                                            Orig: {{ $c->referencia_original }}
                                        </div>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Sol: {{ $c->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>

                                <!-- Distribuidora y Vales Ligados -->
                                <td class="px-5 py-4">
                                    @if($c->distribuidora)
                                        <div class="font-semibold text-slate-900 text-xs">{{ $c->distribuidora->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Ref: {{ $c->distribuidora->referenciaPago() }}</div>
                                    @else
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endif

                                    @if(!empty($c->prestamos_asignados))
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($c->prestamos_asignados as $pItem)
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-mono border border-slate-200">
                                                    {{ $pItem['folio'] ?? 'Vale' }}: ${{ number_format($pItem['monto'] ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif($c->prestamo)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-mono border border-slate-200">
                                            {{ $c->prestamo->referencia }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Cajero / Sucursal -->
                                <td class="px-5 py-4">
                                    <div class="text-xs font-medium text-slate-800">{{ $c->solicitante?->name ?? 'Cajero' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $c->solicitante?->sucursal?->nombre ?? 'Sucursal' }}</div>
                                </td>

                                <!-- Fecha Pago -->
                                <td class="px-5 py-4 text-center">
                                    <span class="text-xs font-mono font-bold text-slate-800 block">
                                        {{ $c->fecha_pago ? $c->fecha_pago->format('d/m/Y') : 'N/A' }}
                                    </span>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">{{ $c->metodo_pago }}</span>
                                </td>

                                <!-- Monto -->
                                <td class="px-5 py-4 text-right">
                                    <span class="font-mono font-black text-emerald-600 text-sm block">
                                        ${{ number_format($c->monto_corregido, 2) }}
                                    </span>
                                    @if($c->monto_original && $c->monto_original != $c->monto_corregido)
                                        <span class="text-[10px] font-mono text-slate-400 line-through">
                                            ${{ number_format($c->monto_original, 2) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Estado -->
                                <td class="px-5 py-4 text-center">
                                    @if(in_array($c->estado, ['pendiente', 'pendiente_gerencia', 'pendiente_coordinador']))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wider">
                                            Pendiente
                                        </span>
                                    @elseif($c->estado === 'conciliado')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                                            Conciliado
                                        </span>
                                    @elseif($c->estado === 'rechazada')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">
                                            Rechazada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider">
                                            {{ $c->estado }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Ver Detalle -->
                                        <a href="{{ route('gerente.conciliaciones.show', $c) }}" 
                                           class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-200" 
                                           title="Ver Detalle Completo">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @if(in_array($c->estado, ['pendiente', 'pendiente_gerencia', 'pendiente_coordinador']))
                                            <!-- Botón Aprobar -->
                                            <button @click="abrirModal('{{ $c->id }}', 'aprobar', '{{ $c->referencia_conciliacion }}', '{{ number_format($c->monto_corregido, 2) }}', '{{ route('gerente.conciliaciones.decidir', $c) }}')"
                                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition">
                                                Aprobar
                                            </button>

                                            <!-- Botón Rechazar -->
                                            <button @click="abrirModal('{{ $c->id }}', 'rechazar', '{{ $c->referencia_conciliacion }}', '{{ number_format($c->monto_corregido, 2) }}', '{{ route('gerente.conciliaciones.decidir', $c) }}')"
                                                    class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition">
                                                Rechazar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($conciliaciones->hasPages())
                <div class="pt-4 border-t border-slate-100">
                    {{ $conciliaciones->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Modal para Decidir Conciliación (Aprobar / Rechazar) -->
    <div x-show="openModalDecidir" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition>
        
        <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 text-left space-y-4"
             @click.outside="openModalDecidir = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <template x-if="modalAccion === 'aprobar'">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                            ✓
                        </div>
                    </template>
                    <template x-if="modalAccion === 'rechazar'">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                            ✕
                        </div>
                    </template>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="modalAccion === 'aprobar' ? 'Aprobar Conciliación' : 'Rechazar Conciliación'"></h3>
                </div>
                <button @click="openModalDecidir = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form novalidate :action="modalUrl" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="accion" :value="modalAccion">

                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500 font-bold">Referencia:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="modalRef"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500 font-bold">Monto a Conciliar:</span>
                        <span class="font-mono font-black text-emerald-600" x-text="'$' + modalMonto"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" x-text="modalAccion === 'aprobar' ? 'Observaciones / Notas (Opcional)' : 'Motivo del Rechazo (Obligatorio)'"></label>
                    <textarea name="observaciones" 
                              rows="3" 
                              :required="modalAccion === 'rechazar'"
                              :placeholder="modalAccion === 'aprobar' ? 'Escribe observaciones sobre la aprobación...' : 'Indica claramente por qué se rechaza la solicitud...'"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="openModalDecidir = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :class="modalAccion === 'aprobar' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'"
                            class="px-5 py-2 rounded-xl text-xs font-bold shadow-md transition"
                            x-text="modalAccion === 'aprobar' ? 'Confirmar Aprobación' : 'Confirmar Rechazo'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
