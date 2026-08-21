@extends('layouts.app')

@section('title', 'Préstamos y Cobranza - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil con Badge de Categoría y Crédito Disponible -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="flex items-center justify-between gap-2 mb-3">
            <div>
                <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Préstamos y Cartera
                    @if($operador && $operador->esAdministrador())
                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-slate-800 text-indigo-400 border border-indigo-500/30">
                            Auditoría
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">Control de referencias y estado de cuenta</p>
            </div>

            @auth
                @if(Auth::user()->esDistribuidor())
                    <div class="text-right shrink-0">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 block">
                            Categoría {{ strtoupper(Auth::user()->categoria_distribuidor ?? 'cobre') }}
                        </span>
                        <span class="text-[10px] font-extrabold text-emerald-400 block mt-0.5">
                            {{ number_format(Auth::user()->obtenerPorcentajeGanancia(), 0) }}% Ganancia
                        </span>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Banner de Estado de Periodo de Cobranza & Puntos -->
        @auth
            @if(Auth::user()->esDistribuidor() && isset($configuracion))
                @php
                    $ahora = now();
                    $fechaCorte = $configuracion->fecha_corte;
                    $fechaLimite = $configuracion->fecha_limite_pago;
                    $esAntesDeCorte = $fechaCorte && $ahora->lessThan($fechaCorte);
                    $esPeriodoATiempo = $fechaCorte && $fechaLimite && $ahora->greaterThanOrEqualTo($fechaCorte) && $ahora->lessThanOrEqualTo($fechaLimite);
                    $esPeriodoVencido = $fechaLimite && $ahora->greaterThan($fechaLimite);
                @endphp

                <div class="mb-3 p-3 rounded-xl border text-xs
                    @if($esAntesDeCorte) bg-emerald-500/10 border-emerald-500/30 text-emerald-300
                    @elseif($esPeriodoATiempo) bg-amber-500/10 border-amber-500/30 text-amber-300
                    @else bg-rose-500/10 border-rose-500/30 text-rose-300 @endif">
                    <div class="flex items-center justify-between font-bold pb-1 mb-1 border-b border-white/10">
                        <span class="inline-flex items-center gap-1.5">
                            @if($esAntesDeCorte)
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Periodo Anticipado Activo (Gana Puntos)
                            @elseif($esPeriodoATiempo)
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span> Corte Realizado (Pago a Tiempo)
                            @else
                                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span> Periodo Vencido (Multa & -20% Puntos)
                            @endif
                        </span>
                        <span class="font-mono text-[10px]">
                            Puntos: {{ Auth::user()->puntos ?? 0 }}
                        </span>
                    </div>
                    <p class="text-[11px] leading-tight opacity-90">
                        @if($esAntesDeCorte)
                            Liquida antes del <strong>{{ $fechaCorte->format('d/m/Y H:i') }}</strong> para acumular puntos por productos otorgados.
                        @elseif($esPeriodoATiempo)
                            Liquida antes del <strong>{{ $fechaLimite->format('d/m/Y H:i') }}</strong> para evitar multas de ${{ number_format($configuracion->multa_adeudo, 0) }}.
                        @else
                            La fecha límite venció el <strong>{{ $fechaLimite->format('d/m/Y H:i') }}</strong>. Se aplicó multa por adeudo.
                        @endif
                    </p>
                </div>
            @endif
        @endauth

        <!-- Ficha de Crédito Disponible & Tope de Vale -->
        @auth
            @if(Auth::user()->esDistribuidor())
                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800 mb-3 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium">Crédito Disponible:</span>
                        <span class="font-black text-emerald-400 text-sm">
                            ${{ number_format(Auth::user()->creditoDisponible(), 2) }}
                            <span class="text-[10px] text-slate-500 font-normal">/ ${{ number_format(Auth::user()->limite_credito ?? 20000, 0) }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-800/80">
                        <span class="text-indigo-300 font-medium">Tope por vale (50% + $500):</span>
                        <span class="font-extrabold text-indigo-300">${{ number_format(Auth::user()->montoMaximoPermitidoPorVale(), 2) }}</span>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Botones de Acción -->
        <div class="flex items-center gap-2 pt-2 border-t border-slate-800">
            @if(!$operador || !$operador->esAdministrador())
                <a href="{{ route('prestamos.create') }}" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs text-center shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Asignar Vale
                </a>
            @endif

            <a href="{{ route('prestamos.relacion-pdf') }}" target="_blank" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs text-center transition flex items-center justify-center gap-1">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Relación PDF
            </a>
        </div>

        <!-- Badges de Resumen Móvil -->
        <div class="grid grid-cols-3 gap-2 pt-3 mt-3 border-t border-slate-800/80 text-center">
            <div class="bg-indigo-500/5 rounded-xl p-2 border border-indigo-500/20">
                <span class="text-[10px] uppercase font-semibold text-indigo-400 block">Prevales</span>
                <span class="text-base font-extrabold text-white">{{ $stats['prevales'] }}</span>
            </div>
            <div class="bg-blue-500/5 rounded-xl p-2 border border-blue-500/20">
                <span class="text-[10px] uppercase font-semibold text-blue-400 block">Vales</span>
                <span class="text-base font-extrabold text-white">{{ $stats['vales'] }}</span>
            </div>
            <div class="bg-emerald-500/5 rounded-xl p-2 border border-emerald-500/20">
                <span class="text-[10px] uppercase font-semibold text-emerald-400 block">Recibido</span>
                <span class="text-base font-extrabold text-emerald-400">${{ number_format($stats['pagos_recibidos_total'], 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Buscador y Filtros Móviles -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md">
        <form action="{{ route('prestamos.index') }}" method="GET" class="space-y-2">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar Referencia o Cliente..."
                    class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <select name="tipo" class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none">
                    <option value="">Todos los tipos</option>
                    <option value="prevale" {{ request('tipo') === 'prevale' ? 'selected' : '' }}>Prevales</option>
                    <option value="vale" {{ request('tipo') === 'vale' ? 'selected' : '' }}>Vales</option>
                </select>

                <select name="estado" class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendientes (Caja)</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activos</option>
                    <option value="finalizado" {{ request('estado') === 'finalizado' ? 'selected' : '' }}>Liquidados</option>
                    <option value="desactivado" {{ request('estado') === 'desactivado' ? 'selected' : '' }}>Desactivados</option>
                </select>

                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista Móvil de Referencias de Préstamo -->
    <div class="space-y-3">
        @forelse($prestamos as $prestamo)
            <div class="bg-slate-900 border @if($prestamo->esPendiente()) border-amber-500/30 @else border-slate-800 @endif rounded-2xl p-4 shadow-lg space-y-3 relative overflow-hidden">
                
                <!-- Encabezado con Referencia y Badge Prevale/Vale -->
                <div class="flex items-start justify-between gap-2 border-b border-slate-800 pb-2.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-indigo-400 tracking-wider">
                                {{ $prestamo->referencia }}
                            </span>
                            @if($prestamo->esPrevale())
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase">
                                    Prevale (1ra vez)
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                                    Vale
                                </span>
                            @endif
                        </div>
                        <h3 class="font-extrabold text-sm text-white mt-1">{{ $prestamo->cliente->nombre }}</h3>
                    </div>

                    <div>
                        @if($prestamo->esPendiente())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 animate-pulse">
                                * Pendiente Caja
                            </span>
                        @elseif($prestamo->estaCancelado())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                ✕ Desactivado
                            </span>
                        @elseif($prestamo->estado === 'activo')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                • Activo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                • Liquidado
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Resumen de Cuenta del Cliente -->
                <div class="bg-slate-950/80 rounded-xl p-3 grid grid-cols-2 gap-2 text-xs border border-slate-800/80">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Progreso de Pagos:</span>
                        <span class="font-extrabold text-white text-xs">
                            {{ $prestamo->pagos_realizados }} de {{ $prestamo->pagos_totales }} 15nas
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[10px]">Cuota Quincenal:</span>
                        <span class="font-extrabold text-white text-xs">
                            ${{ number_format($prestamo->cuota_quincenal, 2) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[10px]">Adeudo Pendiente:</span>
                        <span class="font-black text-rose-400 text-sm">
                            ${{ number_format($prestamo->adeudo_pendiente, 2) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[10px]">Pagos Recibidos:</span>
                        <span class="font-extrabold text-emerald-400 text-xs">
                            ${{ number_format($prestamo->pagos_recibidos, 2) }}
                        </span>
                    </div>

                    @if($prestamo->multas > 0)
                        <div class="col-span-2 pt-1 border-t border-slate-800/60 flex items-center justify-between text-[11px]">
                            <span class="text-rose-400 font-semibold">Multas Acumuladas:</span>
                            <span class="font-bold text-rose-400">${{ number_format($prestamo->multas, 2) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Botones Móviles Táctiles -->
                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('prestamos.show', $prestamo) }}" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold text-center transition flex items-center justify-center gap-1">
                        Estado de Cuenta
                    </a>

                    @if($prestamo->puedeDesactivarsePorDistribuidor() && Auth::check() && (Auth::user()->esDistribuidor() || Auth::user()->id === $prestamo->created_by_user_id))
                        <form action="{{ route('prestamos.destroy', $prestamo) }}" method="POST" class="flex-1" onsubmit="return confirm('¿Estás seguro de desactivar y cancelar este vale pendiente? Se liberará la línea de crédito de inmediato.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-xs font-bold text-center transition flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Desactivar Vale
                            </button>
                        </form>
                    @endif

                    @if($prestamo->esActivo() && !$prestamo->estaPagado() && (!Auth::check() || (!Auth::user()->esDistribuidor() && !Auth::user()->esAdministrador())))
                        <a href="{{ route('prestamos.pago', $prestamo) }}" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold text-center shadow-lg transition flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Cobrar Abono
                        </a>
                    @endif
                </div>

            </div>
        @empty
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 space-y-2">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-400">No hay préstamos ni prevales registrados</p>
            </div>
        @endforelse
    </div>

    <!-- Paginación Móvil -->
    @if($prestamos->hasPages())
        <div class="pt-2">
            {{ $prestamos->links() }}
        </div>
    @endif

</div>
@endsection
