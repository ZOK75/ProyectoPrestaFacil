@extends('layouts.app')

@section('title', 'Editar Cliente - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="flex items-center justify-between">
        <a href="{{ route('clientes.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a clientes
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Edición Móvil</span>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="mb-4 pb-3 border-b border-slate-800">
            <h1 class="text-lg font-black text-white">
                {{ Auth::user()->esDistribuidor() ? 'Solicitar Modificación de Cliente' : 'Editar Cliente' }}
            </h1>
            <p class="text-xs text-slate-400 truncate">{{ $cliente->nombre }}</p>

            @if(Auth::user()->esDistribuidor())
                <div class="mt-3 p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs flex items-start gap-2">
                    <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>
                        <strong>Aviso:</strong> Los cambios solicitados serán enviados a tu Gerente de Sucursal y al Gerente General para su autorización antes de reflejarse en el cliente.
                    </span>
                </div>
            @endif
        </div>

        <form novalidate action="{{ route('clientes.update', $cliente) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            @if(Auth::user()->esDistribuidor())
                <!-- Motivo de la Solicitud -->
                <div>
                    <label class="block text-[11px] font-bold text-indigo-300 uppercase tracking-wider mb-1">
                        Motivo / Justificación del Cambio (Opcional)
                    </label>
                    <input type="text" name="motivo_solicitud" placeholder="Ej. Corrección de domicilio, renovación de INE..."
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
            @endif

            <!-- 1. Datos Personales -->
            <div class="space-y-3">
                <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800 pb-1">
                    1. Datos Personales
                </span>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        Nombre Completo <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('nombre') border-rose-500 @enderror">
                    @error('nombre')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        CURP <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="curp" value="{{ old('curp', $cliente->curp) }}" maxlength="18" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white uppercase font-mono focus:outline-none focus:border-indigo-500 @error('curp') border-rose-500 @enderror">
                    @error('curp')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            RFC
                        </label>
                        <input type="text" name="rfc" value="{{ old('rfc', $cliente->rfc) }}" maxlength="13"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white uppercase font-mono focus:outline-none focus:border-indigo-500 @error('rfc') border-rose-500 @enderror">
                        @error('rfc')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            F. Nacimiento <span class="text-rose-400">*</span>
                        </label>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $cliente->fecha_nacimiento->format('Y-m-d')) }}" required
                            class="w-full px-2.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('fecha_nacimiento') border-rose-500 @enderror"
                            style="color-scheme: dark;">
                        @error('fecha_nacimiento')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        Lugar de Nacimiento <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento', $cliente->lugar_nacimiento) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('lugar_nacimiento') border-rose-500 @enderror">
                    @error('lugar_nacimiento')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 2. Dirección -->
            <div class="space-y-3 pt-2">
                <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800 pb-1">
                    2. Dirección del Domicilio
                </span>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        Calle y Número <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="calle" value="{{ old('calle', $cliente->calle) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('calle') border-rose-500 @enderror">
                    @error('calle')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Colonia <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="colonia" value="{{ old('colonia', $cliente->colonia) }}" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('colonia') border-rose-500 @enderror">
                        @error('colonia')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            C.P. <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $cliente->codigo_postal) }}" maxlength="5" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono focus:outline-none focus:border-indigo-500 @error('codigo_postal') border-rose-500 @enderror">
                        @error('codigo_postal')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Ciudad <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="ciudad" value="{{ old('ciudad', $cliente->ciudad) }}" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('ciudad') border-rose-500 @enderror">
                        @error('ciudad')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Estado <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="estado" value="{{ old('estado', $cliente->estado) }}" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('estado') border-rose-500 @enderror">
                        @error('estado')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 3. Carga de PDFs Opcional -->
            <div class="space-y-3 pt-2">
                <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800 pb-1">
                    3. Reemplazar Expedientes PDF (Opcional)
                </span>

                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 space-y-1">
                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider">
                        Reemplazar INE (PDF)
                    </label>
                    <input type="file" name="pdf_ine" accept=".pdf"
                        class="block w-full text-[11px] text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-600 file:text-white cursor-pointer">
                    @error('pdf_ine')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 space-y-1">
                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider">
                        Reemplazar Comprobante (PDF)
                    </label>
                    <input type="file" name="pdf_comprobante" accept=".pdf"
                        class="block w-full text-[11px] text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-emerald-600 file:text-white cursor-pointer">
                    @error('pdf_comprobante')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Botones Móviles -->
            <div class="flex items-center gap-2 pt-3 border-t border-slate-800">
                <a href="{{ route('clientes.index') }}" class="w-1/3 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1">
                    {{ Auth::user()->esDistribuidor() ? 'Enviar Solicitud a Gerencia' : 'Actualizar Datos' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
