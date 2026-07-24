@extends('layouts.app')

@section('title', 'Detalle de Vale ' . $productoVale->clave . ' - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Encabezado y Acciones -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('producto-vales.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
                &larr; Volver al catálogo
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-white">{{ $productoVale->nombre }}</h1>
                <span class="px-2.5 py-1 rounded-md text-xs font-mono font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    {{ $productoVale->clave }}
                </span>
                @if($productoVale->activo)
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Activo
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        Desactivado
                    </span>
                @endif
            </div>
        </div>

        @if($productoVale->activo)
            <div class="flex items-center gap-2">
                <a href="{{ route('producto-vales.edit', $productoVale) }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm shadow-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Desactivar Producto
                </a>
            </div>
        @endif
    </div>

    <!-- Ficha Financiera en Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Monto Préstamo -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Monto Préstamo</span>
            <span class="text-2xl font-extrabold text-white mt-1 block">${{ number_format($productoVale->monto_prestamo, 2) }}</span>
            <span class="text-xs text-slate-500 mt-1 block">Monto otorgado en el vale</span>
        </div>

        <!-- Total a Pagar -->
        <div class="bg-slate-900 border border-indigo-500/30 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider block">Monto Total a Pagar</span>
            <span class="text-2xl font-extrabold text-indigo-300 mt-1 block">${{ number_format($productoVale->monto_total_pagar, 2) }}</span>
            <span class="text-xs text-slate-400 mt-1 block">Préstamo + Seguro + Comisión + Intereses</span>
        </div>

        <!-- Cuota Quincenal -->
        <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">Cuota Quincenal</span>
            <span class="text-2xl font-extrabold text-emerald-400 mt-1 block">${{ number_format($productoVale->cuota_quincenal, 2) }}</span>
            <span class="text-xs text-slate-400 mt-1 block">Monto Total / {{ $productoVale->plazo_quincenas }} quincenas</span>
        </div>

    </div>

    <!-- Desglose de Gastos y Condiciones -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Cargos e Integración
            </h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Monto Préstamo:</dt>
                    <dd class="font-semibold text-white">${{ number_format($productoVale->monto_prestamo, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Pago por Seguro:</dt>
                    <dd class="font-semibold text-amber-400">${{ number_format($productoVale->costo_seguro, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Comisión Transferencia:</dt>
                    <dd class="font-semibold text-slate-200">${{ number_format($productoVale->comision_transferencia, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Condiciones de Amortización
            </h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Plazo en Quincenas:</dt>
                    <dd class="font-bold text-white">{{ $productoVale->plazo_quincenas }} quincenas</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Tasa Interés 15nal:</dt>
                    <dd class="font-semibold text-indigo-400">{{ number_format($productoVale->tasa_interes_quincenal, 2) }}%</dd>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-800 font-bold">
                    <dt class="text-slate-300">Interés Total Plazo:</dt>
                    <dd class="text-indigo-300">${{ number_format($productoVale->interes_total, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
                Auditoría & Desactivación
            </h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Fecha Creación:</dt>
                    <dd class="text-slate-200 font-mono">{{ $productoVale->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if($productoVale->createdBy)
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Creado por:</dt>
                        <dd class="text-indigo-300 font-semibold">{{ $productoVale->createdBy->name }}</dd>
                    </div>
                @endif
                @if(!$productoVale->activo)
                    <div class="pt-2 border-t border-slate-800 space-y-1">
                        <div class="flex justify-between text-rose-400 font-semibold">
                            <dt>Desactivado el:</dt>
                            <dd class="font-mono">{{ $productoVale->desactivado_at ? $productoVale->desactivado_at->format('d/m/Y H:i:s') : 'N/A' }}</dd>
                        </div>
                        @if($productoVale->updatedBy)
                            <div class="flex justify-between text-slate-400">
                                <dt>Desactivado por:</dt>
                                <dd class="text-slate-300">{{ $productoVale->updatedBy->name }}</dd>
                            </div>
                        @endif
                    </div>
                @endif
            </dl>
        </div>

    </div>

    <!-- Tabla Simulada de Amortización Quincenal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Tabla de Amortización Estimada ({{ $productoVale->plazo_quincenas }} Quincenas)</h2>
            <span class="text-xs text-slate-400">Cuota Quincenal Total: <strong class="text-emerald-400">${{ number_format($productoVale->cuota_quincenal, 2) }}</strong></span>
        </div>

        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800 sticky top-0">
                    <tr>
                        <th class="px-6 py-3"># Quincena</th>
                        <th class="px-6 py-3">Cuota Total</th>
                        <th class="px-6 py-3">Capital Base</th>
                        <th class="px-6 py-3">Seguro</th>
                        <th class="px-6 py-3">Interés</th>
                        <th class="px-6 py-3 text-right">Saldo Restante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($amortizacion as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3 font-semibold text-slate-400">Quincena {{ $item['quincena'] }}</td>
                            <td class="px-6 py-3 font-bold text-white">${{ number_format($item['cuota'], 2) }}</td>
                            <td class="px-6 py-3 text-slate-300">${{ number_format($item['capital'], 2) }}</td>
                            <td class="px-6 py-3 text-amber-400">${{ number_format($item['seguro'], 2) }}</td>
                            <td class="px-6 py-3 text-indigo-400">${{ number_format($item['interes'], 2) }}</td>
                            <td class="px-6 py-3 text-right font-mono text-slate-300">${{ number_format($item['saldo_restante'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
