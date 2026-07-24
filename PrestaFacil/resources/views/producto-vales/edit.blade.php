@extends('layouts.app')

@section('title', 'Desactivar Vale ' . $productoVale->clave . ' - PrestaFácil')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div>
        <a href="{{ route('producto-vales.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
            &larr; Volver al catálogo
        </a>
        <h1 class="text-2xl font-extrabold text-white">Desactivar Producto Vale: <span class="text-indigo-400 font-mono">{{ $productoVale->clave }}</span></h1>
        <p class="text-sm text-slate-400">Confirmación de desactivación del producto en el catálogo.</p>
    </div>

    <!-- Card de Confirmación -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        
        @if(!$productoVale->activo)
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center font-bold">!</div>
                    <div>
                        <h4 class="font-bold text-sm">Este producto ya está desactivado</h4>
                        <p class="text-xs text-rose-300/80 mt-0.5">
                            Fecha de desactivación: {{ $productoVale->desactivado_at ? $productoVale->desactivado_at->format('d/m/Y H:i:s') : 'N/A' }}
                            @if($productoVale->updatedBy)
                                por {{ $productoVale->updatedBy->name }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
                ⚠️ <strong>Nota:</strong> Los parámetros del vale (montos, seguros y plazos) no se pueden alterar después de creados. La única acción permitida en la edición es la <strong>desactivación permanente</strong> del vale.
            </div>
        @endif

        <!-- Resumen del Vale -->
        <div class="bg-slate-950 rounded-xl p-4 border border-slate-800 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Nombre del Producto:</span>
                <span class="font-bold text-white">{{ $productoVale->nombre }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Monto Préstamo:</span>
                <span class="font-bold text-white">${{ number_format($productoVale->monto_prestamo, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Costo Seguro:</span>
                <span class="font-bold text-amber-400">${{ number_format($productoVale->costo_seguro, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Plazo en Quincenas:</span>
                <span class="font-bold text-slate-300">{{ $productoVale->plazo_quincenas }} quincenas</span>
            </div>
            <div class="flex justify-between text-sm border-t border-slate-800 pt-2">
                <span class="text-slate-400">Monto Total a Pagar por Cliente:</span>
                <span class="font-extrabold text-indigo-300">${{ number_format($productoVale->monto_total_pagar, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Cuota Quincenal:</span>
                <span class="font-extrabold text-white">${{ number_format($productoVale->cuota_quincenal, 2) }}</span>
            </div>
        </div>

        <!-- Formulario para Desactivar -->
        @if($productoVale->activo)
            <form action="{{ route('producto-vales.update', $productoVale) }}" method="POST" class="pt-2 flex items-center justify-end gap-3">
                @csrf
                @method('PUT')
                
                <a href="{{ route('producto-vales.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                    Cancelar
                </a>
                
                <button type="submit" onclick="return confirm('¿Confirmas que deseas desactivar este producto vale? Se registrará la marca de tiempo actual.');"
                    class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold shadow-lg shadow-rose-600/30 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Confirmar Desactivación (Con Timestamp)
                </button>
            </form>
        @else
            <div class="flex justify-end">
                <a href="{{ route('producto-vales.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                    Volver al Catálogo
                </a>
            </div>
        @endif

    </div>

</div>
@endsection
