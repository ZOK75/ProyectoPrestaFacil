@extends('layouts.app')

@section('title', 'Nuevo Cliente - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="flex items-center justify-between">
        <a href="{{ route('clientes.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a clientes
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Registro Móvil</span>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="mb-4 pb-3 border-b border-slate-800">
            <h1 class="text-lg font-black text-white">Nuevo Cliente</h1>
            <p class="text-xs text-slate-400">Completa los datos y carga los expedientes PDF.</p>
        </div>

        <form novalidate action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- 1. Datos Personales -->
            <div class="space-y-3">
                <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800 pb-1">
                    1. Datos Personales
                </span>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        Nombre Completo <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="ej. María Pérez García" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('nombre') border-rose-500 @enderror">
                    @error('nombre')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                        CURP <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="curp" value="{{ old('curp') }}" placeholder="18 caracteres" maxlength="18" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white uppercase font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('curp') border-rose-500 @enderror">
                    @error('curp')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            RFC
                        </label>
                        <input type="text" name="rfc" value="{{ old('rfc') }}" placeholder="Opcional" maxlength="13"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white uppercase font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('rfc') border-rose-500 @enderror">
                        @error('rfc')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            F. Nacimiento <span class="text-rose-400">*</span>
                        </label>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required
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
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento') }}" placeholder="ej. Guadalajara, Jal." required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('lugar_nacimiento') border-rose-500 @enderror">
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
                    <input type="text" name="calle" value="{{ old('calle') }}" placeholder="ej. Av. Hidalgo #120" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('calle') border-rose-500 @enderror">
                    @error('calle')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Colonia <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="colonia" value="{{ old('colonia') }}" placeholder="ej. Centro" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('colonia') border-rose-500 @enderror">
                        @error('colonia')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            C.P. <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="codigo_postal" value="{{ old('codigo_postal') }}" placeholder="5 dígitos" maxlength="5" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('codigo_postal') border-rose-500 @enderror">
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
                        <input type="text" name="ciudad" value="{{ old('ciudad') }}" placeholder="ej. Monterrey" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('ciudad') border-rose-500 @enderror">
                        @error('ciudad')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Estado <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="estado" value="{{ old('estado') }}" placeholder="ej. Nuevo León" required
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('estado') border-rose-500 @enderror">
                        @error('estado')
                            <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 3. Carga de PDFs -->
            <div class="space-y-3 pt-2">
                <span class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider block border-b border-slate-800 pb-1">
                    3. Expedientes en PDF
                </span>

                <!-- PDF INE -->
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 space-y-1">
                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider">
                        1. Identificación INE (PDF) <span class="text-rose-400">*</span>
                    </label>
                    <input type="file" name="pdf_ine" accept=".pdf" required
                        class="block w-full text-[11px] text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-600 file:text-white cursor-pointer">
                    @error('pdf_ine')
                        <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- PDF Comprobante -->
                <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 space-y-1">
                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider">
                        2. Comprobante Domicilio (PDF) <span class="text-rose-400">*</span>
                    </label>
                    <input type="file" name="pdf_comprobante" accept=".pdf" required
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
                    Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
