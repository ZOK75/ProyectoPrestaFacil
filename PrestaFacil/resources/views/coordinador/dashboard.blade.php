@extends('layouts.app')

@section('title', 'Panel de Coordinador - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Coordinador -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-sky-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30 uppercase tracking-wider">
                        Coordinación
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Gestión y monitoreo de distribuidores en tu sucursal.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('coordinador.solicitudes.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold shadow-lg shadow-sky-900/20 transition-all text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ver Solicitudes
                </a>
                <a href="{{ route('coordinador.solicitudes.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-sm font-semibold transition">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Alta de Distribuidor
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Distribuidores Activos -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 class="text-lg font-bold text-white">Distribuidores en tu Sucursal</h2>
                <p class="text-slate-400 text-sm mt-1">Lista de todos los distribuidores activos bajo tu supervisión.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Nombre</th>
                        <th class="p-4 font-semibold">Categoría</th>
                        <th class="p-4 font-semibold text-right">Crédito Disp.</th>
                        <th class="p-4 font-semibold">Acciones</th>
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
                                        <div class="text-slate-500 text-xs">{{ $dist->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-medium border
                                    @if($dist->categoria_distribuidor === 'A') bg-emerald-500/10 text-emerald-400 border-emerald-500/20
                                    @elseif($dist->categoria_distribuidor === 'B') bg-sky-500/10 text-sky-400 border-sky-500/20
                                    @elseif($dist->categoria_distribuidor === 'C') bg-amber-500/10 text-amber-400 border-amber-500/20
                                    @else bg-slate-500/10 text-slate-400 border-slate-500/20 @endif">
                                    Cat. {{ $dist->categoria_distribuidor ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="text-emerald-400 font-semibold text-sm">${{ number_format($dist->creditoDisponible(), 2) }}</div>
                                <div class="text-slate-500 text-xs">de ${{ number_format($dist->limite_credito, 2) }}</div>
                            </td>
                            <td class="p-4">
                                <button class="text-sky-400 hover:text-sky-300 text-sm font-medium">Ver Detalles</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                No hay distribuidores registrados en esta sucursal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
