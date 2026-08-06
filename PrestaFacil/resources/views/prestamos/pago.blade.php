@extends('layouts.app')

@section('title', 'Registrar Abono - ' . $prestamo->referencia)

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="flex items-center justify-between">
        <a href="{{ route('prestamos.show', $prestamo) }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al estado de cuenta
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Cobranza Móvil</span>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
        <div class="border-b border-slate-800 pb-3">
            <span class="font-mono text-xs font-black text-indigo-400 block mb-1">
                {{ $prestamo->referencia }}
            </span>
            <h1 class="text-lg font-black text-white">Registrar Abono Quincenal</h1>
            <p class="text-xs text-slate-400">{{ $prestamo->cliente->nombre }} (15na #{{ $prestamo->pagos_realizados + 1 }} de {{ $prestamo->pagos_totales }})</p>
        </div>

        <!-- Resumen Rápido -->
        <div class="bg-slate-950/80 rounded-xl p-3 grid grid-cols-2 gap-2 text-xs border border-slate-800">
            <div>
                <span class="text-slate-400 block text-[10px]">Cuota Sugerida:</span>
                <span class="font-black text-white text-sm">${{ number_format($prestamo->cuota_quincenal, 2) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px]">Adeudo Pendiente:</span>
                <span class="font-black text-rose-400 text-sm">${{ number_format($prestamo->adeudo_pendiente, 2) }}</span>
            </div>
        </div>

        <form action="{{ route('prestamos.pago.store', $prestamo) }}" method="POST" class="space-y-4">
            @csrf

            <!-- Monto Abonado -->
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Monto a Abonar ($) <span class="text-rose-400">*</span>
                </label>
                <input type="number" step="0.01" name="monto_abonado" value="{{ old('monto_abonado', $prestamo->cuota_quincenal) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-bold font-mono focus:outline-none focus:border-indigo-500 @error('monto_abonado') border-rose-500 @enderror">
                @error('monto_abonado')
                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Monto Multa Opcional -->
            <div>
                <label class="block text-[11px] font-bold text-amber-400 uppercase tracking-wider mb-1">
                    Multa / Recargo por Mora ($)
                </label>
                <input type="number" step="0.01" name="monto_multa" value="{{ old('monto_multa', '0.00') }}" placeholder="0.00"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-amber-300 font-bold font-mono focus:outline-none focus:border-amber-500 @error('monto_multa') border-rose-500 @enderror">
                <span class="text-[10px] text-slate-500 mt-1 block">Opcional. Se sumará al historial de multas de la referencia.</span>
                @error('monto_multa')
                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Método de Pago -->
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Método de Pago <span class="text-rose-400">*</span>
                </label>
                <select name="metodo_pago" required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="Efectivo" {{ old('metodo_pago') === 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="Transferencia" {{ old('metodo_pago') === 'Transferencia' ? 'selected' : '' }}>Transferencia Bancaria</option>
                    <option value="Depósito" {{ old('metodo_pago') === 'Depósito' ? 'selected' : '' }}>Depósito en Ventanilla</option>
                </select>
            </div>

            <!-- Observaciones -->
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Observaciones
                </label>
                <textarea name="observaciones" rows="2" placeholder="ej. Pago entregado en sucursal..."
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <!-- Botones Móviles -->
            <div class="flex items-center gap-2 pt-3 border-t border-slate-800">
                <a href="{{ route('prestamos.show', $prestamo) }}" class="w-1/3 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-1">
                    Confirmar Abono
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
