@extends('layouts.app')

@section('title', 'Revisar Solicitud de Traspaso de Distribuidora - PrestaFácil')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 sm:space-y-8">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-purple-950/40 to-slate-900 border border-purple-500/30 p-5 sm:p-7 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase tracking-wider">
                        Solicitud de Traspaso
                    </span>
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $transferencia->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Traspaso de Distribuidora: {{ $distribuidora->name }}
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">Revisa el perfil crediticio, historial y cartera activa de clientes antes de aceptar el traspaso.</p>
            </div>

            <a href="{{ route('coordinador.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition shrink-0">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Panel
            </a>
        </div>
    </div>

    <!-- Ficha de Información de la Transferencia -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Tarjeta: Distribuidora -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-black text-sm shrink-0">
                    {{ strtoupper(substr($distribuidora->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-400 font-medium">Distribuidora</p>
                    <h3 class="text-sm sm:text-base font-bold text-white truncate">{{ $distribuidora->name }}</h3>
                    <p class="text-[11px] text-slate-500 font-mono truncate">Ref: {{ $distribuidora->referenciaPago() }}</p>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-800 space-y-1.5 text-xs">
                <div class="flex justify-between">
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
                <div class="flex justify-between">
                    <span class="text-slate-400">Crédito Disponible:</span>
                    <span class="font-bold text-emerald-400">${{ number_format($distribuidora->creditoDisponible(), 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Tarjeta: Coordinación Origen y Destino -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Detalles del Traspaso</h4>
            
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Coordinador Emisor:</span>
                    <span class="text-slate-200 font-semibold">{{ $transferencia->coordinadorEmisor?->name }}</span>
                    <span class="text-slate-400 block text-[11px]">Sucursal: {{ $transferencia->sucursalOrigen?->nombre }}</span>
                </div>

                <div class="pt-1.5 border-t border-slate-800/80">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Coordinador Receptor:</span>
                    <span class="text-indigo-300 font-semibold">{{ $transferencia->coordinadorReceptor?->name }}</span>
                    <span class="text-slate-400 block text-[11px]">Sucursal: {{ $transferencia->sucursalDestino?->nombre }}</span>
                </div>

                <div class="pt-1.5 border-t border-slate-800/80">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Estado Actual:</span>
                    @if($transferencia->esPendienteCoordinador())
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            Pendiente de tu decisión
                        </span>
                    @elseif($transferencia->esPendienteGerente())
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                            Aceptada por ti &bull; Pendiente Gerente de Sucursal
                        </span>
                    @elseif($transferencia->esAprobada())
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Aprobada y Formalizada
                        </span>
                    @else
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                            Rechazada
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tarjeta: Motivo del Traspaso -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Motivo Expresado por el Emisor</h4>
                <div class="bg-slate-950/60 border border-slate-800 p-3 rounded-xl text-xs text-slate-300 italic leading-relaxed">
                    "{{ $transferencia->motivo }}"
                </div>
            </div>

            @if($transferencia->observaciones_coordinador_receptor)
                <div class="mt-3 pt-3 border-t border-slate-800 text-xs">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Tus Observaciones:</span>
                    <p class="text-slate-300 italic mt-0.5">"{{ $transferencia->observaciones_coordinador_receptor }}"</p>
                </div>
            @endif
        </div>
    </div>

    <!-- CARTERA DE PRÉSTAMOS ACTIVOS DE LA DISTRIBUIDORA -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-800 flex items-center justify-between bg-slate-900/50">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Cartera Activa que Recibirías ({{ $prestamosActivos->count() }} préstamos)
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Saldo acumulado por cobrar: <strong class="text-amber-400">${{ number_format($prestamosActivos->sum('adeudo_pendiente'), 2) }}</strong></p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-3.5 sm:p-4 font-semibold">Folio / Referencia</th>
                        <th class="p-3.5 sm:p-4 font-semibold">Cliente Final</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Monto</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Cuota Quincenal</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-right">Adeudo Pendiente</th>
                        <th class="p-3.5 sm:p-4 font-semibold text-center">Progreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($prestamosActivos as $pa)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="p-3.5 sm:p-4 font-mono font-bold text-white">{{ $pa->referencia }}</td>
                            <td class="p-3.5 sm:p-4">
                                <div class="font-semibold text-slate-200">{{ $pa->cliente?->nombre }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $pa->cliente?->curp }}</div>
                            </td>
                            <td class="p-3.5 sm:p-4 text-right font-bold text-slate-300">${{ number_format($pa->monto_prestamo, 2) }}</td>
                            <td class="p-3.5 sm:p-4 text-right text-slate-400">${{ number_format($pa->cuota_quincenal, 2) }}</td>
                            <td class="p-3.5 sm:p-4 text-right font-bold text-amber-400">${{ number_format($pa->adeudo_pendiente, 2) }}</td>
                            <td class="p-3.5 sm:p-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">
                                    {{ $pa->pagos_realizados }} / {{ $pa->pagos_totales }} qnas
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 text-xs">
                                Esta distribuidora no tiene préstamos activos colocados actualmente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL DE DECISIÓN (Solo para el Coordinador Receptor cuando está pendiente) -->
    @if($esReceptor && $transferencia->esPendienteCoordinador())
        <div class="bg-slate-900 border border-purple-500/40 rounded-2xl p-5 sm:p-7 shadow-2xl space-y-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Dictamen del Coordinador Receptor
                </h3>
                <p class="text-slate-400 text-xs mt-0.5">Si aceptas, se enviará la solicitud al Gerente de Sucursal para la autorización final del cambio.</p>
            </div>

            <form action="{{ route('coordinador.transferencias.decidir', $transferencia) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Comentarios u Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" placeholder="Agrega notas para el Gerente o para el Coordinador Emisor..."
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" name="accion" value="rechazar" 
                            onclick="return confirm('¿Estás seguro de rechazar la incorporación de esta distribuidora?')"
                            class="px-5 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                        Rechazar Traspaso
                    </button>
                    <button type="submit" name="accion" value="aceptar"
                            class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white shadow-lg shadow-purple-900/30 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aceptar Traspaso y Enviar a Gerencia
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
