@extends('layouts.app')

@section('title', 'Evaluaciones de Verificador - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Verificador -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/20 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Verificación Física
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ auth()->user()->sucursal?->nombre ?? 'Sucursal sin asignar' }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bandeja de Evaluaciones, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Realiza las evaluaciones presenciales para validar la legitimidad de las distribuidoras aspirantes.</p>
            </div>
        </div>
    </div>

    <!-- Lista de Evaluaciones Pendientes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Pendientes de Verificación Domiciliaria
                </h2>
                <p class="text-slate-400 text-xs mt-1">Solicitudes listas para realizar visita domiciliaria y cotejar documentos.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Candidato</th>
                        <th class="p-4 font-semibold">Ubicación / Ciudad</th>
                        <th class="p-4 font-semibold">Coordinador</th>
                        <th class="p-4 font-semibold text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($solicitudesPendientes as $sol)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4">
                                <div class="text-slate-200 font-semibold text-sm">{{ $sol->nombres }} {{ $sol->apellidos }}</div>
                                <div class="text-slate-500 text-xs font-mono">CURP: {{ $sol->curp }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-300">
                                {{ $sol->colonia }}, {{ $sol->ciudad }}, {{ $sol->estado_republica }}
                            </td>
                            <td class="p-4 text-xs text-slate-400">
                                {{ $sol->coordinador?->name ?? 'N/A' }}
                                <span class="block text-[10px] text-slate-500">Enviada: {{ $sol->updated_at->format('d/m/Y') }}</span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('verificador.solicitudes.show', $sol->id) }}" 
                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-950/20 transition inline-flex items-center gap-1.5">
                                    Realizar Evaluación
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                No tienes solicitudes pendientes de verificar en esta sucursal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial de Evaluaciones Resueltas -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-lg font-bold text-white">Historial de Evaluaciones Resueltas</h2>
                <p class="text-slate-400 text-sm mt-1">Solicitudes que has evaluado y resuelto.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Candidato</th>
                        <th class="p-4 font-semibold">Fecha Evaluación</th>
                        <th class="p-4 font-semibold">Resolución</th>
                        <th class="p-4 font-semibold">Observaciones</th>
                        <th class="p-4 font-semibold text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($solicitudesResueltas as $sol)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4">
                                <div class="text-slate-200 font-semibold text-sm">{{ $sol->nombres }} {{ $sol->apellidos }}</div>
                                <div class="text-slate-500 text-xs">CURP: {{ $sol->curp }}</div>
                            </td>
                            <td class="p-4 text-xs text-slate-400">
                                {{ $sol->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                            </td>
                            <td class="p-4">
                                @if(($sol->dictamen_verificador ?? $sol->estado) === 'aceptado')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aceptado</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold border bg-rose-500/10 text-rose-400 border-rose-500/20">Rechazado</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-slate-400 max-w-xs truncate">
                                {{ $sol->comentarios_verificador ?? $sol->observaciones_resolucion }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('verificador.solicitudes.show', $sol->id) }}" 
                                   class="text-sky-400 hover:text-sky-300 text-xs font-bold transition">
                                    Ver Detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500 text-sm">
                                Aún no has resuelto ninguna solicitud.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
