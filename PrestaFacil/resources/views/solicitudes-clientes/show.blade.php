@extends('layouts.app')

@section('title', 'Revisión de Solicitud #SOL-' . str_pad($solicitud->id, 5, '0', STR_PAD_LEFT) . ' - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Encabezado con estado y navegación -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('solicitudes-clientes.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition mb-2">
                &larr; Volver a la Bandeja de Solicitudes
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">
                    Solicitud #SOL-{{ str_pad($solicitud->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
                @if($solicitud->esActualizacion())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        Actualización de Datos
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        Baja / Desactivación
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($solicitud->esPendiente())
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-amber-500/10 text-amber-300 border border-amber-500/30 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    Pendiente de Autorización
                </span>
            @elseif($solicitud->estado === 'aprobada')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 text-xs font-bold">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Aprobada por {{ $solicitud->aprobadoPor?->name ?? 'Gerencia' }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/30 text-xs font-bold">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Rechazada por {{ $solicitud->rechazadoPor?->name ?? 'Gerencia' }}
                </span>
            @endif
        </div>
    </div>

    <!-- Metadatos de la Solicitud -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <span class="text-xs text-slate-400 block uppercase font-semibold">Cliente Involucrado</span>
            <div class="text-white font-bold text-base mt-1">{{ $solicitud->cliente?->nombre }}</div>
            <div class="text-xs text-slate-500 font-mono">CURP: {{ $solicitud->cliente?->curp }}</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <span class="text-xs text-slate-400 block uppercase font-semibold">Distribuidor Solicitante</span>
            <div class="text-white font-bold text-base mt-1">{{ $solicitud->distribuidor?->name }}</div>
            <div class="text-xs text-indigo-400">Sucursal: {{ $solicitud->sucursal?->nombre ?? 'N/A' }}</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <span class="text-xs text-slate-400 block uppercase font-semibold">Fecha de Emisión</span>
            <div class="text-white font-bold text-base mt-1">{{ $solicitud->created_at->format('d/m/Y H:i:s') }}</div>
            <div class="text-xs text-slate-500">{{ $solicitud->created_at->diffForHumans() }}</div>
        </div>
    </div>

    @if($solicitud->motivo)
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Motivo / Justificación del Distribuidor:</span>
            <p class="text-sm text-slate-200">{{ $solicitud->motivo }}</p>
        </div>
    @endif

    <!-- Comparativa de Datos (Si es Actualización) -->
    @if($solicitud->esActualizacion())
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Comparativa de Datos: Originales vs Nuevos Solicitados
            </h2>

            @php
                $orig = $solicitud->datos_originales ?? [];
                $solic = $solicitud->datos_solicitados ?? [];

                $campos = [
                    'nombre' => 'Nombre Completo',
                    'curp' => 'CURP',
                    'rfc' => 'RFC',
                    'fecha_nacimiento' => 'Fecha de Nacimiento',
                    'lugar_nacimiento' => 'Lugar de Nacimiento',
                    'calle' => 'Calle y Número',
                    'colonia' => 'Colonia',
                    'codigo_postal' => 'Código Postal',
                    'ciudad' => 'Ciudad / Municipio',
                    'estado' => 'Estado',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3">Campo</th>
                            <th class="px-4 py-3">Valor Actual</th>
                            <th class="px-4 py-3">Valor Solicitado</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($campos as $key => $label)
                            @php
                                $valOrig = $orig[$key] ?? 'N/A';
                                $valSolic = $solic[$key] ?? $valOrig;
                                $cambio = ($valOrig != $valSolic);
                            @endphp
                            <tr class="{{ $cambio ? 'bg-indigo-950/20' : '' }}">
                                <td class="px-4 py-3 font-semibold text-slate-400 text-xs">{{ $label }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $valOrig }}</td>
                                <td class="px-4 py-3 {{ $cambio ? 'text-indigo-300 font-bold' : 'text-slate-400' }}">{{ $valSolic }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($cambio)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">Modificado</span>
                                    @else
                                        <span class="text-slate-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Comparativa de Expedientes PDF -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-800">
                <!-- INE PDF -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-300">Expediente INE (PDF)</span>
                        @if(!empty($solicitud->pdf_ine_nuevo))
                            <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-500/30">NUEVO ADJUNTO</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($solicitud->cliente?->path_ine_pdf)
                            <a href="{{ asset('storage/' . $solicitud->cliente->path_ine_pdf) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700 transition">
                                Ver INE Actual
                            </a>
                        @endif
                        @if(!empty($solicitud->pdf_ine_nuevo))
                            <a href="{{ asset('storage/' . $solicitud->pdf_ine_nuevo) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs text-white font-semibold transition">
                                Ver INE Nuevo Solicitado &rarr;
                            </a>
                        @else
                            <span class="text-xs text-slate-500">Sin cambios en el INE</span>
                        @endif
                    </div>
                </div>

                <!-- Comprobante Domicilio PDF -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-300">Comprobante de Domicilio (PDF)</span>
                        @if(!empty($solicitud->pdf_comprobante_nuevo))
                            <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-500/30">NUEVO ADJUNTO</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($solicitud->cliente?->path_comprobante_pdf)
                            <a href="{{ asset('storage/' . $solicitud->cliente->path_comprobante_pdf) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700 transition">
                                Ver Comprobante Actual
                            </a>
                        @endif
                        @if(!empty($solicitud->pdf_comprobante_nuevo))
                            <a href="{{ asset('storage/' . $solicitud->pdf_comprobante_nuevo) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs text-white font-semibold transition">
                                Ver Comprobante Nuevo &rarr;
                            </a>
                        @else
                            <span class="text-xs text-slate-500">Sin cambios en el comprobante</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Detalle de Desactivación -->
        <div class="bg-rose-950/20 border border-rose-900/40 rounded-2xl p-6 shadow-xl">
            <h2 class="text-base font-bold text-rose-300 flex items-center gap-2 mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Solicitud de Baja / Desactivación Definitiva del Cliente
            </h2>
            <p class="text-sm text-slate-300 leading-relaxed">
                Al autorizar esta solicitud, el cliente <strong class="text-white">{{ $solicitud->cliente?->nombre }}</strong> pasará al estado inactivo. No podrá solicitar nuevos préstamos ni se le podrán expedir vales a menos que sea reactivado por un Gerente.
            </p>
        </div>
    @endif

    <!-- Acciones de Gerencia (Solo si es Pendiente y el usuario es Gerente) -->
    @if($solicitud->esPendiente() && ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()))
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-base font-bold text-white mb-4">Resolución de Gerencia</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Formulario de Aprobación -->
                <form method="POST" action="{{ route('solicitudes-clientes.aprobar', $solicitud) }}" class="space-y-4 bg-emerald-950/10 border border-emerald-900/30 rounded-xl p-5">
                    @csrf
                    <div>
                        <h4 class="text-sm font-bold text-emerald-300 mb-1">Aprobar Solicitud</h4>
                        <p class="text-xs text-slate-400 mb-3">Los cambios se aplicarán de inmediato al cliente.</p>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones (Opcional)</label>
                        <input type="text" name="observaciones_resolucion" placeholder="Comentarios de aprobación..." 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-lg shadow-emerald-600/20 transition"
                            onclick="return confirm('¿Confirmas que deseas APROBAR esta solicitud?');">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Autorizar y Aplicar Cambios
                    </button>
                </form>

                <!-- Formulario de Rechazo -->
                <form method="POST" action="{{ route('solicitudes-clientes.rechazar', $solicitud) }}" class="space-y-4 bg-rose-950/10 border border-rose-900/30 rounded-xl p-5">
                    @csrf
                    <div>
                        <h4 class="text-sm font-bold text-rose-300 mb-1">Rechazar Solicitud</h4>
                        <p class="text-xs text-slate-400 mb-3">Se descartarán los cambios propuestos y se informará el motivo al distribuidor.</p>
                        <label class="block text-xs text-slate-400 mb-1">Motivo del Rechazo <span class="text-rose-400">*</span></label>
                        <input type="text" name="motivo_rechazo" required placeholder="Ingresa el motivo del rechazo..." 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500">
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-lg shadow-rose-600/20 transition"
                            onclick="return confirm('¿Confirmas que deseas RECHAZAR esta solicitud?');">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Rechazar Solicitud
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
