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
                    <label class="block text-sm font-medium text-slate-400 mb-1">Número de Teléfono *</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" required placeholder="10 dígitos" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Fecha de Nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-slate-300 text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Lugar de Nacimiento</label>
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento') }}" placeholder="Ej: Monterrey, Nuevo León" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">CURP *</label>
                    <input type="text" name="curp" value="{{ old('curp') }}" required maxlength="18" placeholder="XXXX000000XXXXXX00" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 uppercase">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-400 mb-1">RFC *</label>
                    <input type="text" name="rfc" value="{{ old('rfc') }}" required maxlength="13" placeholder="XXXX000000XXX" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 uppercase">
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
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-6" x-data="{ familiares: [] }">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2">Información Adicional</h2>
            
            <div class="space-y-4 border border-slate-800 p-4 rounded-xl bg-slate-850/50">
                <h3 class="text-md font-semibold text-sky-400">Familiares más Cercanos</h3>
                <p class="text-xs text-slate-400">Ingresa información de familiares (Hijos, Hermanos, Esposa, Padres) como referencias.</p>
                
                <div class="space-y-3 mb-4">
                    <template x-for="(fam, index) in familiares" :key="index">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-900 rounded-xl border border-slate-850 relative">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre Completo</label>
                                <input type="text" :name="`datos_familiares[${index}][nombre]`" required class="w-full bg-slate-800 border border-slate-700 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Parentesco</label>
                                <select :name="`datos_familiares[${index}][parentesco]`" required class="w-full bg-slate-800 border border-slate-700 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                                    <option value="Hijo/a">Hijo/a</option>
                                    <option value="Hermano/a">Hermano/a</option>
                                    <option value="Esposa/o">Esposa/o</option>
                                    <option value="Padre/Madre">Padre/Madre</option>
                                </select>
                            </div>
                            <div class="flex items-end justify-between gap-2">
                                <div class="flex-grow">
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Contacto (Opcional)</label>
                                    <input type="text" :name="`datos_familiares[${index}][contacto]`" class="w-full bg-slate-800 border border-slate-700 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none" placeholder="Teléfono o Celular">
                                </div>
                                <button type="button" @click="familiares.splice(index, 1)" class="p-2 bg-rose-600/10 border border-rose-500/20 text-rose-400 rounded-lg hover:bg-rose-600/20 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="familiares.push({nombre: '', parentesco: 'Hijo/a', contacto: ''})" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600/10 hover:bg-sky-600/20 border border-sky-500/20 text-sky-400 rounded-xl text-xs font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar Familiar
                </button>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Datos de Vehículos (Opcional)</label>
                <textarea name="datos_vehiculos" rows="2" class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 placeholder-slate-600" placeholder="Ej: Nissan Versa 2018, Placas ABC-123. Si no tiene escribir 'No tengo'">{{ old('datos_vehiculos') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Datos o Descripción de la Casa *</label>
                <textarea name="datos_casa" rows="3" required class="w-full bg-slate-800 border-slate-700 rounded-lg text-white text-sm focus:ring-sky-500 focus:border-sky-500 placeholder-slate-600" placeholder="Color de fachada, plantas, distribución o tipo de propiedad (ej. rentada, propia, familiar)">{{ old('datos_casa') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Referencias Laborales (Opcional)</label>
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
