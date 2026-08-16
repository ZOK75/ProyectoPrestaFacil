@extends('layouts.app')

@section('title', 'Panel de Coordinador - PrestaFácil')

@section('content')
<div class="space-y-8" x-data="{ 
    showCreditModal: false, 
    selectedDistId: '', 
    selectedDistName: '', 
    selectedDistLimit: 0,
    openModal(id, name, limit) {
        this.selectedDistId = id;
        this.selectedDistName = name;
        this.selectedDistLimit = limit;
        this.showCreditModal = true;
    }
}">

    <!-- Header Coordinador -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-sky-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30 uppercase tracking-wider">
                        Coordinación
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ auth()->user()->sucursal?->nombre ?? 'Sucursal sin asignar' }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Gestión de distribuidoras, enlaces de postulación y solicitudes de incremento de crédito.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('coordinador.solicitudes.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold shadow-lg shadow-sky-900/20 transition-all text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ver Solicitudes de Registro
                </a>
                <a href="{{ route('coordinador.solicitudes.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-sm font-semibold transition">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Registrar Solicitud Interna
                </a>
            </div>
        </div>
    </div>

    <!-- Enlace de Postulación Pública -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Enlace de Postulación Pública
            </h2>
            <p class="text-slate-400 text-xs">Comparte este enlace con las interesadas en registrarse como nuevas distribuidoras para que llenen su formulario.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto" x-data="{ copied: false }">
            <input type="text" readonly value="{{ route('postulacion.create', auth()->id()) }}" 
                   class="bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs font-mono text-indigo-300 w-full md:w-80 select-all focus:outline-none">
            <button @click="
                navigator.clipboard.writeText('{{ route('postulacion.create', auth()->id()) }}');
                copied = true;
                setTimeout(() => copied = false, 2000);
            " class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0"
               :class="copied ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-950/20' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-950/20'">
                <span x-text="copied ? '¡Copiado!' : 'Copiar Enlace'"></span>
            </button>
        </div>
    </div>

    <!-- Lista de Distribuidores Activos -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-lg font-bold text-white">Distribuidoras en tu Sucursal</h2>
                <p class="text-slate-400 text-sm mt-1">Lista de todos las distribuidoras activas bajo tu supervisión.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Nombre</th>
                        <th class="p-4 font-semibold">Categoría</th>
                        <th class="p-4 font-semibold text-right">Crédito Disp.</th>
                        <th class="p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($distribuidores as $dist)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-sky-500/10 flex items-center justify-center border border-sky-500/20">
                                        <span class="text-sky-400 font-bold text-xs">{{ substr($dist->name, 0, 2) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-slate-200 font-semibold text-sm">{{ $dist->name }}</div>
                                        <div class="text-slate-500 text-xs">Ref: {{ $dist->referenciaPago() }} &bull; {{ $dist->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-medium border
                                    @if(strtoupper($dist->categoria_distribuidor) === 'ORO') bg-amber-500/10 text-amber-400 border-amber-500/20
                                    @elseif(strtoupper($dist->categoria_distribuidor) === 'PLATA') bg-slate-300/10 text-slate-300 border-slate-300/20
                                    @elseif(strtoupper($dist->categoria_distribuidor) === 'BRONCE') bg-orange-500/10 text-orange-400 border-orange-500/20
                                    @else bg-emerald-500/10 text-emerald-400 border-emerald-500/20 @endif">
                                    Cat. {{ $dist->categoria_distribuidor ?? 'Cobre' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="text-emerald-400 font-semibold text-sm">${{ number_format($dist->creditoDisponible(), 2) }}</div>
                                <div class="text-slate-500 text-xs">de ${{ number_format($dist->limite_credito, 2) }}</div>
                            </td>
                            <td class="p-4 text-right">
                                <button type="button" @click="openModal('{{ $dist->id }}', '{{ $dist->name }}', {{ $dist->limite_credito }})"
                                        class="px-3 py-1.5 bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/20 text-indigo-400 rounded-lg text-xs font-semibold transition">
                                    Incrementar Crédito
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                No hay distribuidoras registradas y activas en esta sucursal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial de Incrementos de Crédito Solicitados -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-lg font-bold text-white">Solicitudes de Incremento de Crédito</h2>
                <p class="text-slate-400 text-sm mt-1">Estatus de tus solicitudes enviadas a autorización de gerencia.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Distribuidora</th>
                        <th class="p-4 font-semibold text-right">Crédito Original</th>
                        <th class="p-4 font-semibold text-right">Nuevo Crédito</th>
                        <th class="p-4 font-semibold">Fecha</th>
                        <th class="p-4 font-semibold">Estado</th>
                        <th class="p-4 font-semibold">Observaciones / Gerente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($solicitudesCredito as $sol)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4">
                                <div class="text-slate-200 font-semibold text-sm">{{ $sol->distribuidor?->name }}</div>
                                <div class="text-slate-500 text-xs font-mono">ID: {{ $sol->distribuidor?->id }}</div>
                            </td>
                            <td class="p-4 text-right text-slate-400 text-sm font-medium">
                                ${{ number_format($sol->limite_actual, 2) }}
                            </td>
                            <td class="p-4 text-right text-emerald-400 text-sm font-semibold">
                                ${{ number_format($sol->limite_nuevo, 2) }}
                            </td>
                            <td class="p-4 text-slate-400 text-xs">
                                {{ $sol->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-4">
                                @if($sol->estado === 'pendiente')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-amber-500/10 text-amber-400 border-amber-500/20">Pendiente</span>
                                @elseif($sol->estado === 'aprobado')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aprobado</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-rose-500/10 text-rose-400 border-rose-500/20">Rechazado</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-slate-400 max-w-xs truncate">
                                @if($sol->gerente)
                                    <span class="block text-slate-300 font-semibold">Gerente: {{ $sol->gerente->name }}</span>
                                @endif
                                @if($sol->observaciones)
                                    <span class="block italic text-slate-500">"{{ $sol->observaciones }}"</span>
                                @else
                                    <span class="block italic text-slate-600">Sin comentarios</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 text-sm">
                                No has solicitado incrementos de crédito anteriormente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Solicitar Incremento de Crédito (Alpine.js) -->
    <div x-show="showCreditModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none" style="display: none;" x-transition>
        <!-- Overlay -->
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showCreditModal = false"></div>
        
        <!-- Modal content -->
        <div class="relative w-full max-w-lg mx-auto my-6 px-4 z-50">
            <div class="relative flex flex-col w-full bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl outline-none focus:outline-none p-6">
                <!-- Header -->
                <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-white">Solicitar Incremento de Crédito</h3>
                    <button type="button" @click="showCreditModal = false" class="text-slate-400 hover:text-white text-lg font-bold leading-none">&times;</button>
                </div>
                
                <!-- Body -->
                <form :action="`/coordinador/distribuidores/${selectedDistId}/solicitar-credito`" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Distribuidora</label>
                        <input type="text" readonly :value="selectedDistName" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-300 px-4 py-2.5 text-sm focus:outline-none select-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Límite Actual</label>
                            <div class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-400 px-4 py-2.5 text-sm select-none font-semibold">
                                $<span x-text="Number(selectedDistLimit).toLocaleString('es-MX', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nuevo Límite ($) *</label>
                            <input type="number" step="0.01" name="limite_nuevo" required :min="selectedDistLimit + 0.01" placeholder="Ej: 30000"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition font-semibold">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Motivo / Justificación *</label>
                        <textarea name="motivo" rows="3" required placeholder="Explica detalladamente por qué se solicita el aumento de crédito (ej. incremento de ventas, excelente historial, etc.)"
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-800 mt-6">
                        <button type="button" @click="showCreditModal = false" class="px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-semibold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/20 text-xs font-bold tracking-wide transition">
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
