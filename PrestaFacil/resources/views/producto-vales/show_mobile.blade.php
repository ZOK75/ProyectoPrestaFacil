@extends('layouts.app')

@section('title', 'Tabla de Amortización Móvil - ' . $productoVale->nombre)

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Volver -->
    <div class="flex items-center justify-between">
        <a href="{{ route('producto-vales.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al catálogo de vales
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Detalle Móvil</span>
    </div>

    <!-- Ficha del Vale Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
        
        <div class="border-b border-slate-800 pb-3">
            <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-mono text-[10px] font-bold border border-indigo-500/20 mb-1 inline-block">
                {{ $productoVale->clave }}
            </span>
            <h1 class="text-lg font-black text-white leading-tight">{{ $productoVale->nombre }}</h1>
        </div>

        <div class="bg-slate-950/80 rounded-xl p-3 space-y-2 border border-slate-800">
            <div class="flex justify-between items-center">
                <span class="text-xs text-slate-400">Monto del Préstamo:</span>
                <span class="text-base font-black text-emerald-400">${{ number_format($productoVale->monto_prestamo, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-slate-400">Cuota Quincenal:</span>
                <span class="text-sm font-bold text-white">${{ number_format($productoVale->cuota_quincenal, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-slate-400">Plazo Total:</span>
                <span class="text-xs font-semibold text-slate-200">{{ $productoVale->plazo_quincenas }} quincenas</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-slate-400">Costo Seguro Incluido:</span>
                <span class="text-xs font-semibold text-amber-400">${{ number_format($productoVale->costo_seguro, 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                <span class="text-xs font-bold text-slate-300">Total General a Pagar:</span>
                <span class="text-sm font-black text-indigo-300">${{ number_format($productoVale->monto_total_pagar, 2) }}</span>
            </div>
        </div>

    </div>

    <!-- Tabla Móvil de Amortización Quincenal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
        <h2 class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Tabla de Amortización Quincenal
        </h2>

        <div class="space-y-2">
            @foreach($amortizacion as $item)
                <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between font-bold border-b border-slate-800/60 pb-1">
                        <span class="text-indigo-400 font-mono">Quincena #{{ $item['quincena'] }}</span>
                        <span class="text-white">Pago: ${{ number_format($item['cuota'], 2) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-1 text-[11px] text-slate-400">
                        <div>Abono a Capital: <strong class="text-slate-200">${{ number_format($item['capital'], 2) }}</strong></div>
                        <div>Interés: <strong class="text-slate-200">${{ number_format($item['interes'], 2) }}</strong></div>
                        <div>Seguro: <strong class="text-amber-400">${{ number_format($item['seguro'], 2) }}</strong></div>
                        <div>Saldo Restante: <strong class="text-indigo-300">${{ number_format($item['saldo_restante'], 2) }}</strong></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">
            <a href="{{ route('producto-vales.index') }}" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold text-center block transition">
                &larr; Volver al catálogo
            </a>
        </div>
    </div>

</div>
@endsection
