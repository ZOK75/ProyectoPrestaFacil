@extends('layouts.app')

@section('title', 'Configuración General - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div>
        <h1 class="text-2xl font-extrabold text-white">Configuración General del Sistema</h1>
        <p class="text-sm text-slate-400">Estos parámetros aplican a todas las sucursales. Cada cambio queda registrado con usuario, fecha/hora y motivo.</p>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400/60 hover:text-emerald-400 text-lg leading-none">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-400/60 hover:text-rose-400 text-lg leading-none">&times;</button>
        </div>
    @endif

    @if(!$puedeEditar)
        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m0-6h4m-2 0V9m0 0V7m0 2h2m-2 0H10m-4 8a8 8 0 1116 0 8 8 0 01-16 0z"/>
                </svg>
            </div>
            <div>
                <strong>🔒 Modo Lectura:</strong> Estás visualizando la configuración como <span class="text-white font-semibold">{{ $operador?->name }}</span> ({{ $operador?->rol?->nombre ?? 'Sin rol' }}). Únicamente el <strong>Gerente General</strong> tiene permisos para modificar estos parámetros.
            </div>
        </div>
    @endif

    <!-- Card Formulario -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <form action="{{ route('configuracion-general.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Fecha y Hora de Corte -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Fecha y Hora de Corte <span class="text-rose-400">*</span>
                    </label>
                    <input type="datetime-local" name="fecha_corte" id="fecha_corte"
                        value="{{ old('fecha_corte', $configuracion->fecha_corte ? $configuracion->fecha_corte->format('Y-m-d\TH:i') : '') }}" required
                        {{ !$puedeEditar ? 'disabled' : '' }}
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed @error('fecha_corte') border-rose-500 @enderror"
                        style="color-scheme: dark;">
                    <span class="text-[11px] text-slate-500 mt-1 block">Selecciona día, hora y minuto del corte.</span>
                    @error('fecha_corte')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha y Hora Límite de Pago -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Fecha y Hora Límite de Pago <span class="text-rose-400">*</span>
                    </label>
                    <input type="datetime-local" name="fecha_limite_pago" id="fecha_limite_pago"
                        value="{{ old('fecha_limite_pago', $configuracion->fecha_limite_pago ? $configuracion->fecha_limite_pago->format('Y-m-d\TH:i') : '') }}" required
                        {{ !$puedeEditar ? 'disabled' : '' }}
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed @error('fecha_limite_pago') border-rose-500 @enderror"
                        style="color-scheme: dark;">
                    <span class="text-[11px] text-slate-500 mt-1 block">Debe ser igual o posterior a la fecha de corte.</span>
                    @error('fecha_limite_pago')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Multa por Adeudo -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Multa por Adeudo ($) <span class="text-rose-400">*</span>
                </label>
                <div class="relative max-w-xs">
                    <span class="absolute left-3.5 top-2.5 text-slate-500 text-sm">$</span>
                    <input type="number" step="0.01" name="multa_adeudo"
                        value="{{ old('multa_adeudo', $configuracion->multa_adeudo) }}" required
                        {{ !$puedeEditar ? 'disabled' : '' }}
                        class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-rose-400 font-semibold focus:outline-none focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed @error('multa_adeudo') border-rose-500 @enderror">
                </div>
                <span class="text-[11px] text-slate-500 mt-1 block">Monto fijo aplicado a cuentas con adeudo vencido.</span>
                @error('multa_adeudo')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Motivo del Cambio -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Motivo del Cambio
                </label>
                <textarea name="motivo" rows="2" placeholder="Describe brevemente la razón del cambio (ej. Ajuste de fechas por periodo vacacional)..."
                    {{ !$puedeEditar ? 'disabled' : '' }}
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">{{ old('motivo') }}</textarea>
                <span class="text-[11px] text-slate-500 mt-1 block">Opcional. Se registrará en el historial de cambios.</span>
            </div>

            @if($puedeEditar)
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            @endif
        </form>
    </div>

    <!-- Configuración Actual -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
            Configuración Actual Vigente
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800">
                <span class="text-xs text-slate-400 block font-medium">Fecha y Hora de Corte</span>
                <span class="text-lg font-extrabold text-indigo-300 mt-1 block">
                    {{ $configuracion->fecha_corte ? $configuracion->fecha_corte->format('d/m/Y H:i') : 'Sin definir' }}
                </span>
            </div>
            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800">
                <span class="text-xs text-slate-400 block font-medium">Fecha y Hora Límite de Pago</span>
                <span class="text-lg font-extrabold text-amber-400 mt-1 block">
                    {{ $configuracion->fecha_limite_pago ? $configuracion->fecha_limite_pago->format('d/m/Y H:i') : 'Sin definir' }}
                </span>
            </div>
            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800">
                <span class="text-xs text-slate-400 block font-medium">Multa por Adeudo</span>
                <span class="text-lg font-extrabold text-rose-400 mt-1 block">
                    ${{ number_format($configuracion->multa_adeudo, 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Historial de Cambios -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-base font-bold text-white">Registro de Cambios</h2>
            </div>
            <span class="text-xs text-slate-400">{{ $configuracion->logs->count() }} cambio(s) registrado(s)</span>
        </div>

        @if($configuracion->logs->count() > 0)
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800 sticky top-0">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Fecha del Cambio</th>
                            <th class="px-6 py-3">Fecha Corte</th>
                            <th class="px-6 py-3">Fecha Límite Pago</th>
                            <th class="px-6 py-3">Multa</th>
                            <th class="px-6 py-3">Usuario</th>
                            <th class="px-6 py-3">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($configuracion->logs as $index => $log)
                            <tr class="hover:bg-slate-800/40 transition {{ $index === 0 ? 'bg-indigo-500/5' : '' }}">
                                <td class="px-6 py-3 text-slate-500 font-mono text-xs">
                                    {{ $configuracion->logs->count() - $index }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="font-semibold text-white text-xs font-mono">{{ $log->changed_at->format('d/m/Y H:i:s') }}</span>
                                    @if($index === 0)
                                        <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">ÚLTIMO</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-indigo-300 font-mono text-xs">{{ $log->fecha_corte->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-amber-400 font-mono text-xs">{{ $log->fecha_limite_pago->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-rose-400 font-semibold">${{ number_format($log->multa_adeudo, 2) }}</td>
                                <td class="px-6 py-3">
                                    @if($log->changedBy)
                                        <span class="text-xs text-slate-200">{{ $log->changedBy->name }}</span>
                                    @else
                                        <span class="text-xs text-slate-500 italic">Sistema</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-400 max-w-48 truncate" title="{{ $log->motivo }}">
                                    {{ $log->motivo ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-slate-500">
                <div class="max-w-xs mx-auto space-y-3">
                    <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="font-medium text-slate-400">Aún no hay cambios registrados</p>
                    <p class="text-xs text-slate-500">El historial se poblará cuando se guarden modificaciones a la configuración.</p>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
