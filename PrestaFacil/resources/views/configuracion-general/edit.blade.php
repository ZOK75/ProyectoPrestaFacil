@extends('layouts.app')

@section('title', 'Configuración General - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Configuración General del Sistema</h1>
            <p class="text-sm text-slate-400">Reglas periódicas de corte por día y hora, multas, comisiones y puntos.</p>
        </div>

        <!-- Reloj del Servidor en Tiempo Real -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-right shrink-0">
            <span class="text-[10px] uppercase font-bold text-indigo-400 block tracking-wider">Hora del Servidor</span>
            <span class="text-sm font-black text-white font-mono block">
                {{ now()->format('d/m/Y H:i:s') }}
            </span>
            <span class="text-[10px] text-slate-500 block">Zona: {{ config('app.timezone') }}</span>
        </div>
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
        <form action="{{ route('configuracion-general.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Seccion 1: Día y Hora de Corte y Fecha Límite -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider">
                        1. Reglas Periódicas de Corte y Fecha Límite de Pago
                    </h3>
                    <span class="text-[11px] text-slate-400 font-medium">
                        Cálculo automático: siguiente mes solo cuando el día y hora límite se anteponen al corte
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Parámetros de Corte -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-indigo-500/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                                ✂️ Fecha de Corte
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">Día del mes y hora</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-1">
                                    Día del Mes (1-31) <span class="text-rose-400">*</span>
                                </label>
                                <input type="number" name="dia_corte" min="1" max="31"
                                    value="{{ old('dia_corte', $configuracion->dia_corte ?? 10) }}" required
                                    {{ !$puedeEditar ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-indigo-500 @error('dia_corte') border-rose-500 @enderror">
                                @error('dia_corte')
                                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-1">
                                    Hora de Corte <span class="text-rose-400">*</span>
                                </label>
                                <input type="time" name="hora_corte"
                                    value="{{ old('hora_corte', substr($configuracion->hora_corte ?? '22:20', 0, 5)) }}" required
                                    {{ !$puedeEditar ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-indigo-500"
                                    style="color-scheme: dark;">
                                @error('hora_corte')
                                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Próximo corte: <strong class="text-indigo-300 font-mono">{{ $configuracion->fechaCorteCalculada()->format('d/m/Y H:i') }}</strong>
                        </div>
                    </div>

                    <!-- Parámetros de Fecha Límite de Pago -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-amber-500/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                                📅 Fecha Límite de Pago
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">Día del mes y hora</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-1">
                                    Día del Mes (1-31) <span class="text-rose-400">*</span>
                                </label>
                                <input type="number" name="dia_limite_pago" min="1" max="31"
                                    value="{{ old('dia_limite_pago', $configuracion->dia_limite_pago ?? 15) }}" required
                                    {{ !$puedeEditar ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-amber-500 @error('dia_limite_pago') border-rose-500 @enderror">
                                @error('dia_limite_pago')
                                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-1">
                                    Hora Límite <span class="text-rose-400">*</span>
                                </label>
                                <input type="time" name="hora_limite_pago"
                                    value="{{ old('hora_limite_pago', substr($configuracion->hora_limite_pago ?? '23:59', 0, 5)) }}" required
                                    {{ !$puedeEditar ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-amber-500"
                                    style="color-scheme: dark;">
                                @error('hora_limite_pago')
                                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Próximo vencimiento: <strong class="text-amber-300 font-mono">{{ $configuracion->fechaLimitePagoCalculada()->format('d/m/Y H:i') }}</strong>
                            @if($configuracion->esLimiteSiguienteMes())
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 inline-block ml-1">
                                    Siguiente Mes (Antepuesto)
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 inline-block ml-1">
                                    Mismo Mes
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Nota informativa de la regla de anteposición -->
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 text-xs flex items-center gap-2">
                    <span class="text-indigo-400 font-bold">💡 Regla de Anteposición:</span>
                    <span>Si el día y la hora límite son posteriores al corte dentro del mes (ej. día 10 a las 10:00 PM y día 10 a las 10:01 PM), se mantienen en el <strong>mismo mes</strong>. Si el día u hora límite son anteriores o iguales (ej. día 10 a las 10:00 PM o día 01), se calculan para el <strong>siguiente mes</strong>.</span>
                </div>

                <!-- Multa por Adeudo -->
                <div class="pt-1">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Multa por Adeudo Vencido ($) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative max-w-xs">
                        <span class="absolute left-3.5 top-2.5 text-slate-500 text-sm">$</span>
                        <input type="number" step="0.01" name="multa_adeudo"
                            value="{{ old('multa_adeudo', $configuracion->multa_adeudo) }}" required
                            {{ !$puedeEditar ? 'disabled' : '' }}
                            class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-rose-400 font-semibold focus:outline-none focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed @error('multa_adeudo') border-rose-500 @enderror">
                    </div>
                    <span class="text-[10px] text-slate-500 mt-1 block">Monto que se cargará de forma automática al superar el día y hora límite si hay adeudo pendiente.</span>
                </div>
            </div>

            <!-- Seccion 2: Porcentajes de Ganancia por Categoría de Distribuidor -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">
                    2. Porcentajes de Ganancia de Distribuidores por Categoría
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Cobre -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-amber-900/30">
                        <label class="block text-xs font-bold text-amber-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span> Categoría Cobre (%)
                        </label>
                        <div class="relative mt-2">
                            <input type="number" step="0.01" min="0" max="100" name="comision_cobre"
                                value="{{ old('comision_cobre', $configuracion->comision_cobre) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full pr-8 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-amber-500 disabled:opacity-50">
                            <span class="absolute right-3 top-2 text-slate-400 text-sm font-bold">%</span>
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Valor por defecto: 3%</span>
                    </div>

                    <!-- Plata -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-slate-400/30">
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span> Categoría Plata (%)
                        </label>
                        <div class="relative mt-2">
                            <input type="number" step="0.01" min="0" max="100" name="comision_plata"
                                value="{{ old('comision_plata', $configuracion->comision_plata) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full pr-8 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-slate-400 disabled:opacity-50">
                            <span class="absolute right-3 top-2 text-slate-400 text-sm font-bold">%</span>
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Valor por defecto: 6%</span>
                    </div>

                    <!-- Oro -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-amber-400/30">
                        <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Categoría Oro (%)
                        </label>
                        <div class="relative mt-2">
                            <input type="number" step="0.01" min="0" max="100" name="comision_oro"
                                value="{{ old('comision_oro', $configuracion->comision_oro) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full pr-8 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-amber-400 disabled:opacity-50">
                            <span class="absolute right-3 top-2 text-slate-400 text-sm font-bold">%</span>
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Valor por defecto: 10%</span>
                    </div>
                </div>
            </div>

            <!-- Seccion 3: Parámetros del Sistema de Puntos -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">
                    3. Parámetros del Sistema de Puntos
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Monto Base en Productos -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-indigo-500/20">
                        <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">
                            Monto Base en Productos ($) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative mt-2">
                            <span class="absolute left-3 top-2 text-slate-500 text-sm">$</span>
                            <input type="number" step="0.01" min="1" name="monto_base_puntos"
                                value="{{ old('monto_base_puntos', $configuracion->monto_base_puntos ?? 1200) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full pl-7 pr-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-indigo-500 disabled:opacity-50">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Por defecto: $1,200.00</span>
                    </div>

                    <!-- Puntos Otorgados por Bloque -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-indigo-500/20">
                        <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">
                            Puntos por Monto Base <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative mt-2">
                            <input type="number" step="1" min="1" name="puntos_por_monto_base"
                                value="{{ old('puntos_por_monto_base', $configuracion->puntos_por_monto_base ?? 3) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-bold focus:outline-none focus:border-indigo-500 disabled:opacity-50">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Por defecto: 3 puntos</span>
                    </div>

                    <!-- Valor en Dinero por Punto -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-emerald-500/20">
                        <label class="block text-xs font-bold text-emerald-400 uppercase tracking-wider mb-1">
                            Valor por Punto ($) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative mt-2">
                            <span class="absolute left-3 top-2 text-slate-500 text-sm">$</span>
                            <input type="number" step="0.01" min="0" name="valor_punto"
                                value="{{ old('valor_punto', $configuracion->valor_punto ?? 2.00) }}" required
                                {{ !$puedeEditar ? 'disabled' : '' }}
                                class="w-full pl-7 pr-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-emerald-400 font-bold focus:outline-none focus:border-emerald-500 disabled:opacity-50">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Por defecto: $2.00 por punto</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs">
                    <strong>Fórmula Aplicada:</strong> Puntos = floor(Total en Productos / ${{ number_format($configuracion->monto_base_puntos ?? 1200, 0) }}) &times; {{ $configuracion->puntos_por_monto_base ?? 3 }} puntos (Redondeo hacia abajo en pago anticipado).
                </div>
            </div>

            <!-- Motivo del Cambio -->
            <div class="pt-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Motivo del Cambio
                </label>
                <textarea name="motivo" rows="2" placeholder="Describe brevemente la razón del cambio (ej. Ajuste de día y hora de corte y fecha límite)..."
                    {{ !$puedeEditar ? 'disabled' : '' }}
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">{{ old('motivo') }}</textarea>
                <span class="text-[11px] text-slate-500 mt-1 block">Opcional. Se registrará en el historial de cambios de configuración.</span>
            </div>

            @if($puedeEditar)
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Configuración General
                    </button>
                </div>
            @endif
        </form>
    </div>

    <!-- Resumen de Configuración Vigente -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">
            Resumen de Ciclo Periódico Vigente
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-950 rounded-xl p-4 border border-indigo-500/20">
                <span class="text-xs text-indigo-400 block font-bold">Corte Periódico</span>
                <span class="text-base font-black text-white mt-1 block">
                    Día {{ $configuracion->dia_corte ?? 10 }} a las {{ substr($configuracion->hora_corte ?? '22:20', 0, 5) }} hrs
                </span>
                <span class="text-[10px] text-slate-400">Próximo: {{ $configuracion->fechaCorteCalculada()->format('d/m/Y H:i') }}</span>
            </div>

            <div class="bg-slate-950 rounded-xl p-4 border border-amber-500/20">
                <span class="text-xs text-amber-400 block font-bold">Fecha Límite de Pago</span>
                <span class="text-base font-black text-amber-400 mt-1 block">
                    Día {{ $configuracion->dia_limite_pago ?? 15 }} a las {{ substr($configuracion->hora_limite_pago ?? '23:59', 0, 5) }} hrs
                </span>
                <span class="text-[10px] text-slate-400">
                    Próximo: {{ $configuracion->fechaLimitePagoCalculada()->format('d/m/Y H:i') }}
                    @if($configuracion->esLimiteSiguienteMes())
                        (Siguiente Mes)
                    @else
                        (Mismo Mes)
                    @endif
                </span>
            </div>

            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800">
                <span class="text-xs text-slate-400 block font-bold">Multa por Adeudo</span>
                <span class="text-lg font-black text-rose-400 mt-1 block">
                    ${{ number_format($configuracion->multa_adeudo, 2) }}
                </span>
                <span class="text-[10px] text-slate-500">Sanción por atraso</span>
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
                            <th class="px-6 py-3">Motivo / Descripción</th>
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
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
