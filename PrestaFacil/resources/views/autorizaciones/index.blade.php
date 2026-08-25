@extends('layouts.app')

@section('title', 'Bandeja de Autorizaciones')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-black text-white">Bandeja de Autorizaciones Aceptadas (Histórico y Auditoría)</h1>
                <p class="text-xs text-slate-400 mt-0.5">Histórico consolidado de todas las solicitudes aceptadas o aprobadas en el sistema (procesos intermedios y resoluciones finales).</p>
            </div>
        </div>
    </div>

    <!-- Tabla Principal de Autorizaciones Aceptadas -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        @if($items->isEmpty())
            <div class="p-12 text-center text-slate-500 text-xs">
                No hay solicitudes autorizadas o aceptadas registradas en el historial.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Categoría / Fecha</th>
                            <th class="px-6 py-4">Usuario Emisor</th>
                            <th class="px-6 py-4">Usuario Aprobador / Aceptó</th>
                            <th class="px-6 py-4">Comentario / Dictamen</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4">Sucursal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($items as $item)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        {{ $item['tipo_categoria'] }}
                                    </span>
                                    <span class="block text-xs text-slate-400 font-mono mt-1">{{ $item['fecha'] ? $item['fecha']->format('d/m/Y H:i:s') : 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">{{ $item['usuario_envio'] }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $item['rol_envio'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-emerald-400">{{ $item['usuario_acepto'] }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $item['rol_acepto'] }}</div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-xs text-slate-300 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 leading-relaxed italic">
                                        "{{ $item['comentario'] }}"
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> {{ ucfirst(str_replace('_', ' ', $item['estado'])) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-indigo-300">
                                    {{ $item['sucursal'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
