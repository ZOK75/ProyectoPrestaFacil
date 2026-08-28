@extends('layouts.app')

@section('title', 'Solicitudes de Distribuidores - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Solicitudes de Distribuidoras</h1>
            <p class="text-slate-400 text-sm mt-1">Gestiona y consulta las solicitudes de alta para nuevas distribuidoras en tu sucursal.</p>
        </div>
        <a href="{{ route('coordinador.solicitudes.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva Solicitud Interna
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabla de solicitudes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">Candidato</th>
                        <th class="p-4 font-semibold">Teléfono</th>
                        <th class="p-4 font-semibold">Fecha de Registro</th>
                        <th class="p-4 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($solicitudes as $solicitud)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4">
                                <div class="text-slate-200 font-semibold text-sm">{{ $solicitud->nombres }} {{ $solicitud->apellidos }}</div>
                                <div class="text-slate-500 text-xs">CURP: {{ $solicitud->curp }} &bull; RFC: {{ $solicitud->rfc }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-300 font-mono">
                                {{ $solicitud->telefono }}
                            </td>
                            <td class="p-4 text-sm text-slate-400">
                                {{ $solicitud->created_at ? ($solicitud->created_at instanceof \DateTimeInterface ? $solicitud->created_at->format('d/m/Y H:i') : \Carbon\Carbon::parse($solicitud->created_at)->format('d/m/Y H:i')) : 'N/A' }}
                            </td>
                            <td class="p-4">
                                @if($solicitud->estado === 'en espera')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border bg-slate-500/20 text-slate-300 border-slate-500/30">En Espera de Envío</span>
                                @elseif($solicitud->estado === 'en espera de verificacion')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border bg-amber-500/20 text-amber-300 border-amber-500/30">En Verificación Presencial</span>
                                @elseif($solicitud->estado === 'aprobado')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border bg-emerald-500/20 text-emerald-300 border-emerald-500/30">Aprobado</span>
                                @elseif($solicitud->estado === 'rechazado')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border bg-rose-500/20 text-rose-300 border-rose-500/30">Rechazado</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border bg-indigo-500/20 text-indigo-300 border-indigo-500/30">{{ ucfirst($solicitud->estado) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                No se encontraron solicitudes registradas para tu sucursal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
