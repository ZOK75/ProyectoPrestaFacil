@extends('layouts.app')

@section('title', 'Cartera de Préstamos y Distribuidoras - PrestaFácil')

@section('content')
<div class="space-y-6 sm:space-y-8">

    <!-- Header Coordinador Préstamos (Tablet Responsive) -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-5 sm:p-7 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Módulo de Préstamos
                    </span>
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ auth()->user()->sucursal?->nombre ?? 'Sucursal' }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Cartera Activa de Distribuidoras y Clientes
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">Supervisa en tiempo real los préstamos vigentes, saldos por cobrar y abonos de las distribuidoras asignadas a tu coordinación.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('coordinador.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs de la Cartera Activa (Tablet 2x2 / 4 cols) -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Préstamos Activos</p>
            <h3 class="text-xl sm:text-2xl font-black text-sky-400 mt-1">{{ $statsPrestamos['total_activos'] }}</h3>
            <span class="text-[10px] text-slate-500">En amortización</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Adeudo Pendiente</p>
            <h3 class="text-lg sm:text-xl font-black text-amber-400 mt-1">${{ number_format($statsPrestamos['adeudo_total'], 2) }}</h3>
            <span class="text-[10px] text-slate-500">Saldo total por cobrar</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Capital Colocado</p>
            <h3 class="text-lg sm:text-xl font-black text-indigo-400 mt-1">${{ number_format($statsPrestamos['capital_colocado'], 2) }}</h3>
            <span class="text-[10px] text-slate-500">Monto prestado activo</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Distribuidoras Activas</p>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-400 mt-1">{{ $statsPrestamos['distribuidores_con_cartera'] }}</h3>
            <span class="text-[10px] text-slate-500">Con cartera viva</span>
        </div>
    </div>

    <!-- Filtros de Búsqueda y Distribuidora -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
        <form novalidate method="GET" action="{{ route('coordinador.prestamos') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <!-- Selector de Distribuidora -->
            <div class="sm:col-span-5">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filtrar por Distribuidora</label>
                <select name="distribuidor_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium">
                    <option value="">-- Todas las distribuidoras a mi cargo --</option>
                    @foreach($distribuidoresFiltro as $df)
                        <option value="{{ $df->id }}" {{ request('distribuidor_id') == $df->id ? 'selected' : '' }}>
                            {{ $df->name }} (Ref: {{ $df->referenciaPago() }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buscador por Cliente / Folio -->
            <div class="sm:col-span-5">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Buscar por Folio, Cliente o CURP</label>
                <div class="relative">
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Ej: REF-VALE..., Juan Pérez, CURP..."
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white pl-9 pr-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-900/30 transition flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filtrar
                </button>
                @if(request()->hasAny(['distribuidor_id', 'buscar']))
                    <a href="{{ route('coordinador.prestamos') }}" class="px-3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-bold transition" title="Limpiar filtros">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- LISTADO DE PRÉSTAMOS ACTIVOS (Tablet Card/Table View) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-800 flex items-center justify-between bg-slate-900/50">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Préstamos Activos en Supervisión
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Mostrando {{ $prestamos->total() }} préstamo(s) activo(s) en total.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-3.5 sm:p-4 font-semibold">Folio / Referencia</th>
                        <th class="p-3.5 sm:p-4 font-semibold">Distribuidora</th>
                        <th class="p-3.5 sm:p-4 font-semibold">Cliente Final</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Monto / Cuota</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Saldo Pendiente</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-center">Progreso</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-center">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($prestamos as $p)
                        @php
                            $porcentaje = ($p->pagos_totales > 0) ? min(100, round(($p->pagos_realizados / $p->pagos_totales) * 100)) : 0;
                        @endphp
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <!-- Folio y Tipo -->
                            <td class="p-3.5 sm:p-4">
                                <div class="font-bold text-white font-mono text-xs sm:text-sm">{{ $p->referencia }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $p->esPrevale() ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                        {{ $p->tipo }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">{{ $p->created_at->format('d/m/Y') }}</span>
                                </div>
                            </td>

                            <!-- Distribuidora Asignada -->
                            <td class="p-3.5 sm:p-4">
                                <div class="font-semibold text-slate-200 text-xs sm:text-sm">{{ $p->createdBy?->name ?? 'Distribuidora no asignada' }}</div>
                                <div class="text-slate-500 text-[11px] font-mono">Ref: {{ $p->createdBy?->referenciaPago() }}</div>
                            </td>

                            <!-- Cliente Final -->
                            <td class="p-3.5 sm:p-4">
                                <div class="font-semibold text-slate-100 text-xs sm:text-sm">{{ $p->cliente?->nombre }}</div>
                                <div class="text-slate-500 text-[11px] font-mono">CURP: {{ $p->cliente?->curp ?? 'N/A' }}</div>
                                @if($p->cliente?->telefono)
                                    <div class="text-slate-500 text-[11px]">Tel: {{ $p->cliente->telefono }}</div>
                                @endif
                            </td>

                            <!-- Monto y Cuota -->
                            <td class="p-3.5 sm:p-4 text-right">
                                <div class="font-bold text-white text-xs sm:text-sm">${{ number_format($p->monto_prestamo, 2) }}</div>
                                <div class="text-slate-400 text-[11px]">${{ number_format($p->cuota_quincenal, 2) }} / qna</div>
                            </td>

                            <!-- Saldo Pendiente -->
                            <td class="p-3.5 sm:p-4 text-right">
                                <div class="font-black text-amber-400 text-xs sm:text-sm">${{ number_format($p->adeudo_pendiente, 2) }}</div>
                                <div class="text-slate-500 text-[10px]">Total: ${{ number_format($p->monto_total_pagar, 2) }}</div>
                            </td>

                            <!-- Progreso Quincenas -->
                            <td class="p-3.5 sm:p-4 text-center min-w-[130px]">
                                <div class="text-xs font-bold text-slate-300">
                                    {{ $p->pagos_realizados }} / {{ $p->pagos_totales }} <span class="text-[10px] text-slate-500">qnas</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-1.5 mt-1.5 overflow-hidden border border-slate-800">
                                    <div class="bg-gradient-to-r from-indigo-500 to-emerald-400 h-1.5 rounded-full" style="width: {{ $porcentaje }}%"></div>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500">{{ $porcentaje }}% liquidado</span>
                            </td>

                            <!-- Estatus -->
                            <td class="p-3.5 sm:p-4 text-center">
                                @if($p->estado === 'activo')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                        Finalizado
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-500 text-sm">
                                No se encontraron préstamos activos con los criterios de búsqueda seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prestamos->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                {{ $prestamos->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
