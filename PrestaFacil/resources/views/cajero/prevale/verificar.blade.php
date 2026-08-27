@extends('layouts.app')

@section('title', 'Verificar Prevale - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8" x-data="{ ineCoincide: false, comprobanteCoincide: false }">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.buscar-folio', ['referencia' => $prestamo->referencia]) }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver
        </a>
    </div>

    <!-- Panel de Validaciones del Sistema -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-800 pb-2">Validaciones del Sistema y Monto del Vale</h2>
        
        <!-- Tarjeta Resumen Financiero del Vale -->
        <div class="bg-slate-950 p-3 rounded-xl border border-indigo-500/30 space-y-1.5 text-xs">
            <div class="flex justify-between items-center">
                <span class="text-slate-400 font-medium">Monto del Préstamo:</span>
                <span class="font-mono font-black text-emerald-400 text-sm">${{ number_format($prestamo->monto_prestamo, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-400 font-medium">Total a Pagar (Capital + Interés):</span>
                <span class="font-mono font-black text-white text-sm">${{ number_format($prestamo->monto_total_pagar, 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-1 border-t border-slate-800 text-[11px]">
                <span class="text-slate-400">Cuota Quincenal: <strong class="text-indigo-300 font-mono">${{ number_format($prestamo->cuota_quincenal, 2) }}</strong></span>
                <span class="text-slate-400">Plazo: <strong class="text-white font-mono">{{ $prestamo->pagos_totales }} quincenas</strong></span>
            </div>
        </div>
        
        @if(empty($erroresNegocio))
            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="text-xs font-semibold text-emerald-300 leading-tight">
                    El vale cumple con todas las reglas de negocio (Límite, Morosidad, Activos).
                </div>
            </div>
        @else
            <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl space-y-2">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="text-xs font-black text-rose-400">Atención: Reglas Incumplidas</span>
                </div>
                <ul class="text-[11px] text-rose-300 space-y-1 list-disc pl-4">
                    @foreach($erroresNegocio as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($prestamo->limite_credito_anterior && $prestamo->limite_credito_anterior < $prestamo->createdBy->limite_credito)
            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl">
                <span class="block text-[10px] font-bold text-amber-400 uppercase mb-1">Incremento de Crédito Detectado</span>
                <div class="text-xs text-slate-300">
                    Crédito Anterior: <strong class="text-white">${{ number_format($prestamo->limite_credito_anterior, 2) }}</strong> &rarr; 
                    Actual: <strong class="text-emerald-400">${{ number_format($prestamo->createdBy->limite_credito, 2) }}</strong>
                </div>
                <div class="text-[10px] text-amber-300 mt-1">La regla del 50% se aplicó sobre el crédito anterior.</div>
            </div>
        @endif
    </div>

    <!-- Datos Físicos a Verificar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-800 pb-2">Verificación de Documentos Físicos</h2>
        <p class="text-[11px] text-slate-500">Solicita al cliente su INE y comprobante de domicilio ORIGINALES y compáralos con estos datos:</p>
        
        <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800 space-y-2 text-xs">
            <div class="flex justify-between items-start border-b border-slate-800/60 pb-2">
                <span class="text-slate-400">Nombre Completo:</span>
                <span class="font-bold text-white text-right">{{ $prestamo->cliente->nombre }}</span>
            </div>
            <div class="flex justify-between items-start border-b border-slate-800/60 pb-2">
                <span class="text-slate-400">CURP:</span>
                <span class="font-mono font-bold text-indigo-300 text-right">{{ $prestamo->cliente->curp }}</span>
            </div>
            <div class="flex justify-between items-start border-b border-slate-800/60 pb-2">
                <span class="text-slate-400">RFC:</span>
                <span class="font-mono font-bold text-indigo-300 text-right">{{ $prestamo->cliente->rfc }}</span>
            </div>
            <div class="flex justify-between items-start border-b border-slate-800/60 pb-2">
                <span class="text-slate-400">Fecha Nacimiento:</span>
                <span class="font-bold text-slate-300 text-right">{{ $prestamo->cliente->fecha_nacimiento->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between items-start pb-1">
                <span class="text-slate-400">Dirección:</span>
                <span class="font-bold text-white text-right max-w-[60%]">{{ $prestamo->cliente->calle }} {{ $prestamo->cliente->numero_exterior }}, {{ $prestamo->cliente->colonia }}, C.P. {{ $prestamo->cliente->codigo_postal }}, {{ $prestamo->cliente->ciudad }}, {{ $prestamo->cliente->estado }}</span>
            </div>
        </div>

        <!-- Expedientes Digitales PDF (INE y Comprobante) -->
        <div class="pt-1 border-t border-slate-800/80 space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase block">Expedientes Digitales Adjuntos:</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @if($prestamo->cliente->path_ine_pdf)
                    <a href="{{ Storage::url($prestamo->cliente->path_ine_pdf) }}" target="_blank" class="flex items-center justify-between p-2.5 bg-slate-950 border border-slate-700 hover:border-indigo-500 rounded-xl text-xs text-indigo-300 font-bold transition">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Ver PDF INE
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @else
                    <div class="p-2 bg-slate-950/60 border border-slate-800 rounded-xl text-[10px] text-slate-500 italic">
                        Sin PDF INE
                    </div>
                @endif

                @if($prestamo->cliente->path_comprobante_pdf)
                    <a href="{{ Storage::url($prestamo->cliente->path_comprobante_pdf) }}" target="_blank" class="flex items-center justify-between p-2.5 bg-slate-950 border border-slate-700 hover:border-indigo-500 rounded-xl text-xs text-indigo-300 font-bold transition">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Ver Comprobante
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @else
                    <div class="p-2 bg-slate-950/60 border border-slate-800 rounded-xl text-[10px] text-slate-500 italic">
                        Sin Comprobante
                    </div>
                @endif
            </div>
        </div>

        <!-- Checkboxes de Verificación -->
        <div class="space-y-3 pt-2">
            <label class="flex items-start gap-3 p-3 border border-slate-800 rounded-xl cursor-pointer hover:bg-slate-800/50 transition-colors" :class="{'bg-emerald-900/20 border-emerald-500/30': ineCoincide}">
                <div class="flex items-center h-5">
                    <input type="checkbox" x-model="ineCoincide" class="w-4 h-4 text-emerald-500 bg-slate-900 border-slate-700 rounded focus:ring-emerald-500 focus:ring-2">
                </div>
                <div class="text-sm">
                    <span class="font-bold text-white block">La INE coincide 100%</span>
                    <span class="text-[10px] text-slate-400 block">Fotografía, nombre, CURP y fecha de nacimiento.</span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 border border-slate-800 rounded-xl cursor-pointer hover:bg-slate-800/50 transition-colors" :class="{'bg-emerald-900/20 border-emerald-500/30': comprobanteCoincide}">
                <div class="flex items-center h-5">
                    <input type="checkbox" x-model="comprobanteCoincide" class="w-4 h-4 text-emerald-500 bg-slate-900 border-slate-700 rounded focus:ring-emerald-500 focus:ring-2">
                </div>
                <div class="text-sm">
                    <span class="font-bold text-white block">El Comprobante coincide</span>
                    <span class="text-[10px] text-slate-400 block">Dirección exacta y no mayor a 3 meses.</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Acciones -->
    <div class="space-y-3 pt-2">
        @if(empty($erroresNegocio))
            <form novalidate action="{{ route('cajero.prevale.entregar', $prestamo) }}" method="POST" x-show="ineCoincide && comprobanteCoincide" x-transition>
                @csrf
                <div class="bg-emerald-900/20 border border-emerald-500/30 rounded-2xl p-4 space-y-4">
                    <h3 class="text-xs font-bold text-emerald-400 uppercase">Confirmar Transferencia</h3>
                    
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Transferir</label>
                        <input type="number" name="monto_depositado" value="{{ $prestamo->monto_prestamo }}" step="0.01" required readonly
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl py-2 px-3 text-emerald-400 font-mono text-lg font-black mt-1">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">No. Referencia o Clave de Rastreo (SPEI)</label>
                        <input type="text" name="numero_transferencia" required placeholder="Ej: 20260809123456"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-3 text-white font-mono text-sm mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-600/20 transition-colors">
                        Registrar Entrega Exitosamente
                    </button>
                </div>
            </form>
        @endif

        <a href="{{ route('cajero.solicitar-modificacion', $prestamo) }}" x-show="!ineCoincide || !comprobanteCoincide" x-transition class="flex items-center justify-center gap-2 w-full py-3 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-white text-center font-bold text-sm rounded-xl transition-colors">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Los datos NO coinciden (Solicitar Corrección)
        </a>
    </div>

</div>
@endsection
