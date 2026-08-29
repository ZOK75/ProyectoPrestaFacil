@extends('layouts.app')

@section('title', 'Estado de Cuenta - ' . $prestamo->referencia)

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Volver -->
    <div class="flex items-center justify-between">
        <a href="{{ Auth::user() && (Auth::user()->esGerenteSucursal() || Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador()) ? route('dashboard') : route('prestamos.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">
            {{ Auth::user()->esAdministrador() ? 'Modo Auditoría' : 'Cuenta Móvil' }}
        </span>
    </div>

    <!-- Ficha de la Referencia Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">

        <div class="flex items-start justify-between gap-2 border-b border-slate-800 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-black text-indigo-400 tracking-wider">
                        {{ $prestamo->referencia }}
                    </span>
                    @if($prestamo->esPrevale())
                        <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase">
                            Prevale
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                            Vale
                        </span>
                    @endif
                </div>
                <h1 class="text-base font-extrabold text-white mt-1">{{ $prestamo->cliente->nombre }}</h1>
                <span class="text-[11px] font-mono text-slate-400 block">CURP: {{ $prestamo->cliente->curp }}</span>
            </div>

            <div>
                @if($prestamo->esPendiente())
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 animate-pulse">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pendiente en Caja
                    </span>
                @elseif($prestamo->estaCancelado())
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Desactivado
                    </span>
                @elseif($prestamo->estado === 'activo')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        • Activo
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                        • Liquidado
                    </span>
                @endif
            </div>
        </div>

        <!-- Estado de Cuenta (Campos Requeridos del Usuario) -->
        <div class="bg-slate-950/80 rounded-xl p-3 space-y-2 border border-slate-800 text-xs">
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Referencia de Cuenta:</span>
                <span class="font-mono font-bold text-indigo-300">{{ $prestamo->referencia }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Producto Asignado:</span>
                <span class="font-semibold text-white">{{ $prestamo->productoVale->nombre }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Número de Pagos Totales:</span>
                <span class="font-bold text-slate-200">{{ $prestamo->pagos_totales }} quincenas</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Número de Pagos Realizados:</span>
                <span class="font-extrabold text-white">{{ $prestamo->pagos_realizados }} de {{ $prestamo->pagos_totales }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Monto de Pagos Recibidos:</span>
                <span class="font-bold text-emerald-400">${{ number_format($prestamo->pagos_recibidos, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400">Multas Acumuladas:</span>
                <span class="font-bold text-rose-400">${{ number_format($prestamo->multas, 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                <span class="font-bold text-slate-300">Adeudo Pendiente:</span>
                <span class="text-base font-black text-rose-400">${{ number_format($prestamo->adeudo_pendiente, 2) }}</span>
            </div>
        </div>

        <!-- Alerta y Desactivación para Distribuidor si está pendiente -->
        @if($prestamo->puedeDesactivarsePorDistribuidor() && Auth::check() && (Auth::user()->esDistribuidor() || Auth::user()->id === $prestamo->created_by_user_id))
            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-2 text-xs">
                <div class="flex items-center gap-2 text-amber-300 font-bold">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Vale en Espera de Entrega en Ventanilla</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-tight">
                    Este vale aún no ha sido entregado por el cajero. Puedes desactivarlo y cancelar la referencia para liberar tu saldo de crédito.
                </p>
                <form novalidate action="{{ route('prestamos.destroy', $prestamo) }}" method="POST" onsubmit="return confirm('¿Estás seguro de desactivar y cancelar este vale pendiente? Se liberará la línea de crédito de inmediato.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-xs font-bold transition flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Desactivar y Cancelar Vale
                    </button>
                </form>
            </div>
        @endif

        <!-- Botón de Cobro Móvil (Exclusivo para Caja/Cajero) -->
        @if($prestamo->esActivo() && !$prestamo->estaPagado() && Auth::check() && Auth::user()->esCajero())
            <div>
                <a href="{{ route('prestamos.pago', $prestamo) }}" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold text-center shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Registrar Abono Quincenal / Multa
                </a>
            </div>
        @endif

    </div>

    <!-- Bitácora de Abonos Recibidos -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
        <h2 class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center justify-between">
            <span>Historial de Pagos Recibidos</span>
            <span class="text-slate-400 font-mono text-[10px]">{{ $prestamo->pagos->count() }} abonos</span>
        </h2>

        <div class="space-y-2">
            @forelse($prestamo->pagos as $pago)
                <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 space-y-1 text-xs">
                    <div class="flex items-center justify-between font-bold">
                        <span class="text-emerald-400 font-mono">15na #{{ $pago->numero_quincena }}</span>
                        <span class="text-white">${{ number_format($pago->monto_abonado, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Folio: <strong class="font-mono text-slate-300">{{ $pago->folio_pago }}</strong></span>
                        <span>{{ $pago->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    @if($pago->monto_multa > 0)
                        <div class="text-[11px] text-rose-400 font-semibold pt-1 border-t border-slate-800/60">
                            Multa aplicada: ${{ number_format($pago->monto_multa, 2) }}
                        </div>
                    @endif

                    @if($pago->observaciones)
                        <div class="text-[10px] text-slate-400 italic">
                            "{{ $pago->observaciones }}"
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-slate-500 text-xs italic">
                    Aún no se han registrado abonos para esta referencia.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
