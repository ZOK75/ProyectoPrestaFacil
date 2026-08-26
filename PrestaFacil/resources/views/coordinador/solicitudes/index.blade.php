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
                        <th class="p-4 font-semibold text-right">Acciones</th>
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
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-slate-500/10 text-slate-400 border-slate-500/20">En Espera</span>
                                @elseif($solicitud->estado === 'en espera de verificacion')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-amber-500/10 text-amber-400 border-amber-500/20">En Verificación</span>
                                @elseif($solicitud->estado === 'aprobado')
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aprobado</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium border bg-red-500/10 text-red-400 border-red-500/20">Rechazado</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('coordinador.solicitudes.show', $solicitud) }}" 
                                       x-data="{ clicked: false }" @click="clicked = true" :class="{ 'pointer-events-none opacity-50': clicked }"
                                       class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-medium transition">
                                        Ver Detalle
                                    </a>
                                    @if($solicitud->estado === 'en espera')
                                        <form novalidate action="{{ route('coordinador.solicitudes.enviar-verificacion', $solicitud) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                x-data="{ clicked: false }" 
                                                @click="if(confirm('¿Confirmas que los datos son legítimos y deseas enviar la solicitud a verificación presencial?')) { clicked = true; return true; } else { return false; }" 
                                                :class="{ 'pointer-events-none opacity-50': clicked }"
                                                class="px-3 py-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/20 rounded-lg text-xs font-medium transition">
                                                Validar y Enviar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500 text-sm">
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
