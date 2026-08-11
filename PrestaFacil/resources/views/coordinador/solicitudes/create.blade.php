@extends('layouts.app')

@section('title', 'Nueva Solicitud de Distribuidora - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Alta de Distribuidora</h1>
        <a href="{{ route('coordinador.solicitudes.index') }}" class="text-slate-400 hover:text-white text-sm font-medium transition">
            &larr; Volver a Solicitudes
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
            <div class="text-red-400 font-semibold mb-2">Por favor, corrige los siguientes errores:</div>
            <ul class="list-disc list-inside text-red-400/80 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('coordinador.solicitudes.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Datos Personales -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2">Datos Personales</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Nombres *</label>
                    <input type="text" name="nombres" value="{{ old('nombres') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Apellidos *</label>
                    <input type="text" name="apellidos" value="{{ old('apellidos') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">CURP *</label>
                    <input type="text" name="curp" value="{{ old('curp') }}" required maxlength="18" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">RFC *</label>
                    <input type="text" name="rfc" value="{{ old('rfc') }}" required maxlength="13" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Folio Acta Nacimiento</label>
                    <input type="text" name="acta_nacimiento" value="{{ old('acta_nacimiento') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Lugar de Nacimiento</label>
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>
        </div>

        <!-- Dirección -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2">Dirección</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-400 mb-1">Calle y Número *</label>
                    <input type="text" name="calle" value="{{ old('calle') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Colonia *</label>
                    <input type="text" name="colonia" value="{{ old('colonia') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Código Postal *</label>
                    <input type="text" name="codigo_postal" value="{{ old('codigo_postal') }}" required maxlength="10" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Ciudad *</label>
                    <input type="text" name="ciudad" value="{{ old('ciudad') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Estado de la República *</label>
                    <input type="text" name="estado_republica" value="{{ old('estado_republica') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>
        </div>

        <!-- Datos Extra -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2">Información Adicional (Opcional)</h2>
            
            <div class="space-y-4 border border-slate-800 p-4 rounded-xl bg-slate-800/20">
                <h3 class="text-md font-semibold text-sky-400">Datos de Familiar de Referencia</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Nombre</label>
                        <input type="text" name="datos_familiares[nombre]" value="{{ old('datos_familiares.nombre') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Apellido</label>
                        <input type="text" name="datos_familiares[apellido]" value="{{ old('datos_familiares.apellido') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">CURP</label>
                        <input type="text" name="datos_familiares[curp]" value="{{ old('datos_familiares.curp') }}" maxlength="18" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 uppercase">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Fecha de Nacimiento</label>
                        <input type="date" name="datos_familiares[fecha_nacimiento]" value="{{ old('datos_familiares.fecha_nacimiento') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-1">Dirección Completa</label>
                        <input type="text" name="datos_familiares[direccion]" value="{{ old('datos_familiares.direccion') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Ciudad</label>
                        <input type="text" name="datos_familiares[ciudad]" value="{{ old('datos_familiares.ciudad') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Estado</label>
                        <input type="text" name="datos_familiares[estado]" value="{{ old('datos_familiares.estado') }}" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Datos de Vehículos</label>
                <textarea name="datos_vehiculos" rows="2" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 placeholder-slate-600" placeholder="Ej: Nissan Versa 2018, Placas ABC-123">{{ old('datos_vehiculos') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Datos de su Casa (Propia, Rentada, etc.)</label>
                <textarea name="datos_casa" rows="2" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 placeholder-slate-600" placeholder="Ej: Casa propia, 2 pisos, color blanco">{{ old('datos_casa') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Referencias de dónde labora actualmente</label>
                <textarea name="referencias_laborales" rows="2" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 placeholder-slate-600" placeholder="Ej: Empresa XYZ, Tel: 555-9999, Jefe Directo: Pedro Gómez">{{ old('referencias_laborales') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route('coordinador.solicitudes.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-sm font-semibold transition">
                Cancelar
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-900/20 text-sm font-semibold transition">
                Guardar Solicitud
            </button>
        </div>
    </form>
</div>
@endsection
