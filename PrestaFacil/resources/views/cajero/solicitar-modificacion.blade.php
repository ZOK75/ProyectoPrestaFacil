@extends('layouts.app')

@section('title', 'Solicitar Modificación de Datos - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="flex items-center justify-between">
        @if($prestamo->esPrevale())
            <a href="{{ route('cajero.prevale.verificar', $prestamo) }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">&larr; Volver a Verificación</a>
        @else
            <a href="{{ route('cajero.vale.verificar', $prestamo) }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">&larr; Volver a Verificación</a>
        @endif
    </div>

    <div class="bg-slate-900 border border-rose-900/50 rounded-2xl p-4 shadow-xl">
        <h1 class="text-lg font-black text-rose-400 flex items-center gap-2 mb-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Discrepancia de Datos
        </h1>
        <p class="text-xs text-slate-400">Corrige los datos del cliente según sus documentos físicos. Esta solicitud pasará a autorización antes de poder entregar el vale.</p>
    </div>

    <form novalidate action="{{ route('cajero.solicitar-modificacion', $prestamo) }}" method="POST" class="space-y-4">
        @csrf
        
        <!-- Comparador -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-800 pb-2">Datos a Corregir</h2>
            
            <div class="space-y-3">
                <!-- Nombre -->
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Nombre Completo</label>
                    <div class="flex gap-2 items-center mt-1">
                        <div class="flex-1 bg-slate-950/50 border border-slate-800 rounded-lg p-2 text-xs text-slate-400 line-through">
                            {{ $prestamo->cliente->nombre }}
                        </div>
                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        <input type="text" name="nombre" value="{{ old('nombre', $prestamo->cliente->nombre) }}" required
                            class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-2 text-xs text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- CURP -->
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase">CURP</label>
                    <div class="flex gap-2 items-center mt-1">
                        <div class="flex-1 bg-slate-950/50 border border-slate-800 rounded-lg p-2 text-xs text-slate-400 line-through font-mono">
                            {{ $prestamo->cliente->curp }}
                        </div>
                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        <input type="text" name="curp" value="{{ old('curp', $prestamo->cliente->curp) }}" required
                            class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-2 text-xs text-white font-mono uppercase focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- RFC -->
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase">RFC</label>
                    <div class="flex gap-2 items-center mt-1">
                        <div class="flex-1 bg-slate-950/50 border border-slate-800 rounded-lg p-2 text-xs text-slate-400 line-through font-mono">
                            {{ $prestamo->cliente->rfc }}
                        </div>
                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        <input type="text" name="rfc" value="{{ old('rfc', $prestamo->cliente->rfc) }}" required
                            class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-2 text-xs text-white font-mono uppercase focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Dirección -->
                @if($prestamo->esPrevale())
                    <div class="pt-2 border-t border-slate-800/60">
                        <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Calle y Número (Original: {{ $prestamo->cliente->calle }} {{ $prestamo->cliente->numero_exterior }})</label>
                        <div class="flex gap-2">
                            <input type="text" name="calle" value="{{ old('calle', $prestamo->cliente->calle) }}" class="flex-[3] bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="Calle">
                            <input type="text" name="numero_exterior" value="{{ old('numero_exterior', $prestamo->cliente->numero_exterior) }}" class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="Num Ext">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Colonia y C.P. (Original: {{ $prestamo->cliente->colonia }}, CP: {{ $prestamo->cliente->codigo_postal }})</label>
                        <div class="flex gap-2">
                            <input type="text" name="colonia" value="{{ old('colonia', $prestamo->cliente->colonia) }}" class="flex-[3] bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="Colonia">
                            <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $prestamo->cliente->codigo_postal) }}" class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="C.P.">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Ciudad y Estado (Original: {{ $prestamo->cliente->ciudad }}, {{ $prestamo->cliente->estado }})</label>
                        <div class="flex gap-2">
                            <input type="text" name="ciudad" value="{{ old('ciudad', $prestamo->cliente->ciudad) }}" class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="Ciudad">
                            <input type="text" name="estado" value="{{ old('estado', $prestamo->cliente->estado) }}" class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-3 text-xs text-white" placeholder="Estado">
                        </div>
                    </div>
                @endif
                
                <!-- Fecha de Nacimiento -->
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Fecha de Nacimiento</label>
                    <div class="flex gap-2 items-center mt-1">
                        <div class="flex-1 bg-slate-950/50 border border-slate-800 rounded-lg p-2 text-xs text-slate-400 line-through">
                            {{ $prestamo->cliente->fecha_nacimiento?->format('d/m/Y') }}
                        </div>
                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $prestamo->cliente->fecha_nacimiento?->format('Y-m-d')) }}" required
                            class="flex-1 bg-slate-950 border border-indigo-500/50 rounded-lg py-2 px-2 text-xs text-white focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <input type="hidden" name="lugar_nacimiento" value="{{ $prestamo->cliente->lugar_nacimiento }}">
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Motivo de la corrección</label>
            <textarea name="motivo" required rows="3" placeholder="Ej: La dirección en el comprobante es diferente a la capturada por la distribuidora..."
                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-3 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('motivo') }}</textarea>
        </div>

        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm rounded-xl shadow-lg shadow-indigo-600/30 transition-colors">
            Enviar Solicitud a Coordinación
        </button>

    </form>
</div>
@endsection
