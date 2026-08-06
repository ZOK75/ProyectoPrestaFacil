@extends('layouts.app')

@section('title', 'Estado de Cuenta - ' . $prestamo->referencia)

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Volver -->
    <div class="flex items-center justify-between">
        <a href="{{ route('prestamos.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a préstamos
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Cuenta Móvil</span>
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
                @if($prestamo->estado === 'activo')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                        Liquidado
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

        <!-- Botón de Cobro Móvil -->
        @if(!$prestamo->estaPagado())
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
