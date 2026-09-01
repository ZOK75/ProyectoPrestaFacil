@extends('layouts.app')

@section('title', 'Expediente Móvil - ' . $cliente->nombre)

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="flex items-center justify-between">
        <a href="{{ route('clientes.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al catálogo
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Ficha Cliente</span>
    </div>

    <!-- Perfil del Cliente Móvil -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
        
        <div class="flex items-start justify-between gap-2 border-b border-slate-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white text-lg font-black shadow-md shrink-0">
                    {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-base font-black text-white leading-tight">{{ $cliente->nombre }}</h1>
                    <span class="text-xs font-mono text-indigo-400 block mt-0.5">CURP: {{ $cliente->curp }}</span>
                </div>
            </div>

            <div>
                @if($cliente->activo)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        Inactivo
                    </span>
                @endif
            </div>
        </div>

        @if(!$cliente->activo)
            <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
                <strong>Cliente Desactivado</strong> el {{ $cliente->desactivado_at ? $cliente->desactivado_at->format('d/m/Y H:i') : 'N/A' }}
                @if($cliente->desactivadoPor)
                    por {{ $cliente->desactivadoPor->name }}
                @endif.
            </div>
        @endif

        <!-- Botón Directo: Asignar Vale / Prevale -->
        @if($cliente->activo)
            <div>
                <a href="{{ route('prestamos.create', ['cliente_id' => $cliente->id]) }}" class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-black text-center shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Asignar Vale / Prevale a este Cliente
                </a>
            </div>
        @endif

        <!-- Información Personal -->
        <div class="space-y-2">
            <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800/80 pb-1">
                Datos Personales
            </span>
            <div class="bg-slate-950/70 rounded-xl p-3 space-y-1.5 text-xs border border-slate-800/80">
                <div class="flex justify-between">
                    <span class="text-slate-400">RFC:</span>
                    <span class="font-mono text-slate-200 font-semibold">{{ $cliente->rfc ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">F. Nacimiento:</span>
                    <span class="font-mono text-slate-200">{{ $cliente->fecha_nacimiento->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Lugar Nac.:</span>
                    <span class="text-slate-200 truncate max-w-[160px]">{{ $cliente->lugar_nacimiento }}</span>
                </div>
            </div>
        </div>

        <!-- Dirección -->
        <div class="space-y-2">
            <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800/80 pb-1">
                Domicilio Registrado
            </span>
            <div class="bg-slate-950/70 rounded-xl p-3 space-y-1.5 text-xs border border-slate-800/80">
                <div class="flex justify-between">
                    <span class="text-slate-400">Calle:</span>
                    <span class="font-semibold text-white truncate max-w-[170px]">{{ $cliente->calle }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Colonia:</span>
                    <span class="text-slate-200 truncate max-w-[170px]">{{ $cliente->colonia }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">C.P.:</span>
                    <span class="font-mono text-slate-200">{{ $cliente->codigo_postal }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Ciudad/Edo:</span>
                    <span class="text-slate-200 truncate max-w-[170px]">{{ $cliente->ciudad }}, {{ $cliente->estado }}</span>
                </div>
            </div>
        </div>

        <!-- Expedientes Digitales en PDF -->
        <div class="space-y-2">
            <span class="text-xs font-extrabold text-white uppercase tracking-wider block border-b border-slate-800/80 pb-1">
                Expedientes Digitales (PDF)
            </span>

            <div class="space-y-2">
                <!-- INE PDF Card -->
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-400 font-extrabold text-[10px]">
                            PDF
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white block">Identificación INE</span>
                            <span class="text-[10px] text-slate-400 block">
                                {{ $cliente->path_ine_pdf ? 'Documento listo' : 'Sin archivo' }}
                            </span>
                        </div>
                    </div>

                    @if($cliente->path_ine_pdf)
                        <a href="{{ route('clientes.documento', [$cliente, 'ine']) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition shrink-0 flex items-center gap-1">
                            Abrir
                        </a>
                    @endif
                </div>

                <!-- Comprobante PDF Card -->
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 font-extrabold text-[10px]">
                            PDF
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white block">Comp. Domicilio</span>
                            <span class="text-[10px] text-slate-400 block">
                                {{ $cliente->path_comprobante_pdf ? 'Documento listo' : 'Sin archivo' }}
                            </span>
                        </div>
                    </div>

                    @if($cliente->path_comprobante_pdf)
                        <a href="{{ route('clientes.documento', [$cliente, 'comprobante']) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition shrink-0 flex items-center gap-1">
                            Abrir
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Botones de Acción Móvil -->
        @if($cliente->activo && !auth()->user()->esAdministrador())
            <div class="flex items-center gap-2 pt-2 border-t border-slate-800">
                <a href="{{ route('clientes.edit', $cliente) }}" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold text-center shadow transition">
                    Editar Datos
                </a>

                <form novalidate action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Desactivar cliente {{ $cliente->nombre }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-600 hover:text-white text-xs font-bold transition">
                        Desactivar
                    </button>
                </form>
            </div>
        @endif

    </div>

</div>
@endsection
