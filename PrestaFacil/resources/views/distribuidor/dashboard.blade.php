@extends('layouts.app')

@section('title', 'Panel Distribuidor Móvil - PrestaFácil')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <!-- Tarjeta de Perfil Móvil & Categoría -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-600/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-indigo-400 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-indigo-600/25 shrink-0">
                    {{ strtoupper(substr($distribuidor->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-white leading-tight truncate max-w-[190px]">
                        {{ $distribuidor->name }}
                    </h1>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $distribuidor->sucursal?->nombre ?? 'Sucursal Central' }}
                    </p>
                </div>
            </div>

            @php
                $cat = strtolower($distribuidor->categoria_distribuidor ?? 'cobre');
                $catStyles = [
                    'cobre' => 'bg-amber-800/30 text-amber-300 border-amber-700/40',
                    'bronce' => 'bg-amber-900/40 text-amber-200 border-amber-700/50',
                    'plata' => 'bg-slate-500/20 text-slate-200 border-slate-400/40',
                    'oro' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/50',
                    'platino' => 'bg-cyan-500/20 text-cyan-300 border-cyan-400/50',
                ];
                $catStyle = $catStyles[$cat] ?? $catStyles['cobre'];
            @endphp
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold border uppercase tracking-wider shrink-0 {{ $catStyle }}">
                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                {{ ucfirst($cat) }}
            </span>
        </div>
    </div>

    <!-- Banner Alerta de Morosidad si está bloqueada -->
    @if($distribuidor->esMorosa())
        <div class="bg-rose-950/60 border-2 border-rose-500/60 rounded-2xl p-4 shadow-xl space-y-2">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-600/30 border border-rose-500 flex items-center justify-center text-rose-300 font-bold shrink-0">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white leading-tight">Cuenta Suspendida por Morosidad</h3>
                    <p class="text-[11px] text-rose-300 mt-0.5">Tu cuenta ha sido declarada en estado de morosidad debido a retrasos en tus cortes. La colocación de nuevos vales ha sido bloqueada.</p>
                </div>
            </div>
            @if($distribuidor->morosa_at)
                <p class="text-[10px] text-slate-400">Estado aplicado el {{ $distribuidor->morosa_at->format('d/m/Y H:i') }}. Contacta a tu coordinador o gerencia para regularizar tu saldo.</p>
            @endif
        </div>
    @endif

    <!-- Tarjeta Billetera Digital de Crédito (Estilo Tarjeta de Crédito Móvil) -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 border border-indigo-500/30 p-5 shadow-2xl space-y-4">
        <!-- Aura de brillo -->
        <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-indigo-500/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex items-center justify-between text-xs">
            <span class="text-indigo-300 font-bold uppercase tracking-wider text-[10px]">Línea de Crédito PrestaFácil</span>
            <span class="text-[10px] font-mono text-slate-400 font-medium">Billetera de Vales</span>
        </div>

        <div>
            <span class="text-[11px] text-slate-400 uppercase font-semibold block">Crédito Disponible para Vales</span>
            <div class="text-3xl font-black text-white tracking-tight mt-0.5">
                ${{ number_format($creditoDisponible, 2) }}
            </div>
        </div>

        <!-- Barra de uso de crédito -->
        <div>
            <div class="flex justify-between text-[11px] text-slate-400 font-medium mb-1.5">
                <span>Ocupado: <strong class="text-indigo-300">${{ number_format($creditoUtilizado, 2) }}</strong></span>
                <span>Límite: <strong class="text-white">${{ number_format($limiteCredito, 2) }}</strong></span>
            </div>
            <div class="w-full bg-slate-800/80 rounded-full h-2 overflow-hidden border border-slate-700/40">
                <div class="bg-gradient-to-r from-emerald-500 to-indigo-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $limiteCredito > 0 ? min(100, ($creditoDisponible / $limiteCredito) * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- Referencia Bancaria de Pago en Tarjeta -->
        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
            <div>
                <span class="text-[10px] text-slate-400 block uppercase font-medium">Ref. Bancaria Única</span>
                <span class="font-mono text-xs font-bold text-cyan-300 select-all">{{ $referenciaPago }}</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-medium">Comisión</span>
                <span class="text-xs font-black text-amber-300">{{ number_format($porcentajeGanancia, 1) }}%</span>
            </div>
        </div>
    </div>

    <!-- Puntos y Máximo por Vale (2 Mini Cards Móviles) -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Máximo por Vale</span>
            <div class="text-base font-black text-slate-100 mt-1">
                ${{ number_format($montoMaximoVale, 2) }}
            </div>
            <span class="text-[10px] text-indigo-400 mt-0.5 block">50% Límite + $500</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Puntos Acumulados</span>
            <div class="text-base font-black text-amber-400 mt-1">
                {{ number_format($puntos) }} <span class="text-xs font-normal text-slate-400">pts</span>
            </div>
            <span class="text-[10px] text-emerald-400 mt-0.5 block">Nivel {{ ucfirst($cat) }}</span>
        </div>
    </div>

    <!-- Acciones Rápidas Móviles (Botones Touch) -->
    <div class="space-y-2 pt-1">
        @if($distribuidor->esMorosa())
            <button disabled class="w-full py-3 px-4 rounded-2xl bg-slate-800 text-slate-500 font-bold text-sm border border-slate-700 cursor-not-allowed flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Emisión Bloqueada por Morosidad
            </button>
        @else
            <a href="{{ route('prestamos.create') }}" class="w-full py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Emitir Nuevo Vale de Préstamo
            </a>
        @endif

        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('clientes.create') }}" class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-emerald-400 border border-emerald-500/30 text-xs font-bold text-center transition flex items-center justify-center gap-1.5 shadow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Nuevo Cliente
            </a>

            <a href="{{ route('prestamos.relacion-pdf') }}" target="_blank" class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-rose-400 border border-rose-500/30 text-xs font-bold text-center transition flex items-center justify-center gap-1.5 shadow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cobranza PDF
            </a>
        </div>
    </div>

    <!-- Resumen Móvil de Clientes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md flex items-center justify-between">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Mi Cartera de Clientes</span>
            <div class="text-sm font-bold text-white mt-0.5">
                {{ $clientesActivos }} activos <span class="text-slate-500 font-normal">({{ $totalClientes }} total)</span>
            </div>
        </div>
        <a href="{{ route('clientes.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
            Ver Clientes &rarr;
        </a>
    </div>

    <!-- Sección Móvil: Solicitudes Enviadas a Gerencia -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
            <h2 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Mis Solicitudes a Gerencia
            </h2>
            @if($solicitudesPendientesCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">
                    {{ $solicitudesPendientesCount }} pendiente{{ $solicitudesPendientesCount > 1 ? 's' : '' }}
                </span>
            @endif
        </div>

        @if($solicitudesRecientes->isEmpty())
            <p class="text-center text-xs text-slate-500 py-3">No tienes solicitudes enviadas recientemente.</p>
        @else
            <div class="space-y-2">
                @foreach($solicitudesRecientes as $sol)
                    <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-indigo-400 font-bold block">#SOL-{{ str_pad($sol->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-xs font-bold text-white leading-tight block mt-0.5">{{ $sol->cliente?->nombre }}</span>
                            </div>

                            @if($sol->esPendiente())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 shrink-0">
                                    ⏳ Pendiente
                                </span>
                            @elseif($sol->estado === 'aprobada')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Aprobada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Rechazada
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/60">
                            <span>
                                {{ $sol->esActualizacion() ? 'Actualización de Datos' : 'Baja / Desactivación' }}
                            </span>
                            <span class="text-[10px] text-slate-500">{{ $sol->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Sección Móvil: Vales Pendientes de Entrega en Caja -->
    @if(isset($prestamosPendientes) && $prestamosPendientes->isNotEmpty())
        <div class="bg-slate-900 border border-amber-500/30 rounded-2xl p-4 shadow-xl space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <h2 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Vales Pendientes de Entrega en Ventanilla ({{ count($prestamosPendientes) }})
                </h2>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    En Caja
                </span>
            </div>

            <div class="space-y-2.5">
                @foreach($prestamosPendientes as $prestamo)
                    <div class="bg-slate-950/80 border border-amber-500/20 rounded-xl p-3 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-amber-400 font-bold block">{{ $prestamo->referencia }}</span>
                                <span class="text-xs font-bold text-white block mt-0.5">{{ $prestamo->cliente?->nombre }}</span>
                                <span class="text-[10px] text-slate-400">{{ $prestamo->productoVale?->nombre ?? 'Vale' }} &bull; ${{ number_format($prestamo->monto_prestamo, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/10 text-amber-300 border border-amber-500/30 block">
                                    ⏳ Por Entregar
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-800/80">
                            <a href="{{ route('prestamos.show', $prestamo) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold transition">
                                Ver Ficha
                            </a>

                            <form action="{{ route('prestamos.destroy', $prestamo) }}" method="POST" onsubmit="return confirm('¿Estás seguro de desactivar y cancelar este vale pendiente? Se liberará la línea de crédito de inmediato.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-[11px] font-bold transition flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Desactivar Vale
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sección Móvil: Mis Vales / Préstamos Activos -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
            <h2 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Vales Activos en Cartera ({{ count($prestamosActivos) }})
            </h2>
            <a href="{{ route('prestamos.index') }}" class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300">
                Ver todos &rarr;
            </a>
        </div>

        @if($prestamosActivos->isEmpty())
            <p class="text-center text-xs text-slate-500 py-3">No tienes préstamos activos en este momento.</p>
        @else
            <div class="space-y-2.5">
                @foreach($prestamosActivos as $prestamo)
                    <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-indigo-400 font-bold block">{{ $prestamo->referencia }}</span>
                                <span class="text-xs font-bold text-white block mt-0.5">{{ $prestamo->cliente?->nombre }}</span>
                                <span class="text-[10px] text-slate-500">{{ $prestamo->productoVale?->nombre ?? 'Vale' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-rose-400 block">${{ number_format($prestamo->adeudo_pendiente, 2) }}</span>
                                <span class="text-[10px] text-slate-500">Saldo pendiente</span>
                            </div>
                        </div>

                        <!-- Barra de Pagos -->
                        <div class="space-y-1 pt-1 border-t border-slate-800/60">
                            <div class="flex justify-between text-[10px] text-slate-400">
                                <span>{{ $prestamo->pagos_realizados }}/{{ $prestamo->pagos_totales }} pagos</span>
                                <span class="font-semibold text-slate-300">Prestado: ${{ number_format($prestamo->monto_prestamo, 2) }}</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-emerald-500 h-1.5 rounded-full"
                                     style="width: {{ $prestamo->pagos_totales > 0 ? min(100, ($prestamo->pagos_realizados / $prestamo->pagos_totales) * 100) : 0 }}%"></div>
                            </div>
                        </div>

                        <div class="pt-1 text-right">
                            <a href="{{ route('prestamos.show', $prestamo) }}" class="inline-block px-3 py-1 rounded-lg bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 text-[11px] font-bold">
                                Ver Ficha de Vale &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
