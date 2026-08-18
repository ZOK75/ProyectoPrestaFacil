@extends('layouts.app')

@section('title', 'Autorización de Traspaso de Distribuidora - Gerencia de Sucursal')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 sm:space-y-8">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-5 sm:p-7 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                            Gerencia General Corporativa
                        </span>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                            Sucursal Destino: {{ $transferencia->sucursalDestino?->nombre }}
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            Gerencia de Sucursal
                        </span>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                            {{ $transferencia->sucursalDestino?->nombre }}
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Autorización de Traspaso: {{ $distribuidora->name }}
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">El coordinador receptor aceptó la incorporación de esta distribuidora. Se requiere el visto bueno gerencial para formalizar el cambio.</p>
            </div>

            @php
                $backRoute = (Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador()) 
                    ? route('gerente-general.dashboard') 
                    : route('gerente-sucursal.dashboard');
            @endphp
            <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition shrink-0">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Panel
            </a>
        </div>
    </div>

    <!-- Fichas de Detalle -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Distribuidora -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Datos de la Distribuidora</h4>
            <div class="space-y-1.5 text-xs">
                <div class="text-white font-bold text-sm">{{ $distribuidora->name }}</div>
                <div class="text-slate-500 font-mono text-[11px]">Ref: {{ $distribuidora->referenciaPago() }}</div>
                <div class="flex justify-between pt-1 border-t border-slate-800">
                    <span class="text-slate-400">Categoría:</span>
                    <span class="font-bold text-amber-400">{{ $distribuidora->categoria_distribuidor ?? 'Estándar' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Línea de Crédito:</span>
                    <span class="font-bold text-white">${{ number_format($distribuidora->limite_credito, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Crédito Utilizado:</span>
                    <span class="font-bold text-sky-400">${{ number_format($distribuidora->creditoUtilizado(), 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Flujo del Traspaso -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Coordinaciones Involucradas</h4>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Origen:</span>
                    <span class="text-slate-200 font-semibold">{{ $transferencia->coordinadorEmisor?->name }}</span>
                    <span class="text-slate-400 block text-[11px]">Sucursal: {{ $transferencia->sucursalOrigen?->nombre }}</span>
                </div>
                <div class="pt-1.5 border-t border-slate-800/80">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Destino (Tu Sucursal):</span>
                    <span class="text-indigo-300 font-semibold">{{ $transferencia->coordinadorReceptor?->name }}</span>
                    <span class="text-slate-400 block text-[11px]">Sucursal: {{ $transferencia->sucursalDestino?->nombre }}</span>
                </div>
            </div>
        </div>

        <!-- Motivo y Aceptación -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Motivo del Traspaso</h4>
                <div class="bg-slate-950/60 border border-slate-800 p-2.5 rounded-xl text-xs text-slate-300 italic">
                    "{{ $transferencia->motivo }}"
                </div>
            </div>

            @if($transferencia->observaciones_coordinador_receptor)
                <div class="mt-2 pt-2 border-t border-slate-800 text-xs">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Nota de Coordinador Receptor:</span>
                    <p class="text-slate-300 italic text-[11px]">"{{ $transferencia->observaciones_coordinador_receptor }}"</p>
                </div>
            @endif
        </div>
    </div>

    <!-- CARTERA DE PRÉSTAMOS ACTIVOS -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-900/50">
            <div>
                <h2 class="text-base font-bold text-white">Cartera Activa que se Asignará a tu Sucursal</h2>
                <p class="text-slate-400 text-xs mt-0.5">{{ $prestamosActivos->count() }} préstamo(s) activo(s) &bull; Saldo pendiente: <strong class="text-amber-400">${{ number_format($prestamosActivos->sum('adeudo_pendiente'), 2) }}</strong></p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-3 font-semibold">Folio</th>
                        <th class="p-3 font-semibold">Cliente Final</th>
                        <th class="p-3 font-semibold text-right">Monto</th>
                        <th class="p-3 font-semibold text-right">Saldo Pendiente</th>
                        <th class="p-3 font-semibold text-center">Progreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($prestamosActivos as $pa)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="p-3 font-mono font-bold text-white">{{ $pa->referencia }}</td>
                            <td class="p-3 text-slate-200">{{ $pa->cliente?->nombre }}</td>
                            <td class="p-3 text-right text-slate-300">${{ number_format($pa->monto_prestamo, 2) }}</td>
                            <td class="p-3 text-right font-bold text-amber-400">${{ number_format($pa->adeudo_pendiente, 2) }}</td>
                            <td class="p-3 text-center text-slate-400">{{ $pa->pagos_realizados }} / {{ $pa->pagos_totales }} qnas</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-500 text-xs">
                                Sin préstamos activos en este momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL DE DECISIÓN GERENCIAL -->
    @if($transferencia->esPendienteGerente())
        <div class="bg-slate-900 border border-indigo-500/40 rounded-2xl p-5 sm:p-7 shadow-2xl space-y-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Resolución Final de Gerencia de Sucursal
                </h3>
                <p class="text-slate-400 text-xs mt-0.5">Al aprobar, la distribuidora y su cartera quedarán formalmente reasignadas al nuevo coordinador y a esta sucursal.</p>
            </div>

            <form action="{{ route('gerente-sucursal.transferencias.decidir', $transferencia) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Comentarios / Justificación Gerencial (Opcional)</label>
                    <textarea name="observaciones_gerente" rows="2" placeholder="Agrega notas o condiciones para la resolución gerencial..."
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" name="accion" value="rechazar" 
                            onclick="return confirm('¿Estás seguro de rechazar esta solicitud de traspaso?')"
                            class="px-5 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                        Rechazar Traspaso
                    </button>
                    <button type="submit" name="accion" value="aprobar"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/30 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aprobar y Formalizar Traspaso
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
