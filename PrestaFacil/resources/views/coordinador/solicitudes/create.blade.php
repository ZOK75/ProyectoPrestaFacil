@extends('layouts.app')

@section('title', 'Nueva Solicitud de Distribuidora - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Alta de Distribuidora</h1>
            <p class="text-slate-400 text-xs mt-1">Registra la información completa de la candidata para iniciar el proceso de verificación presencial.</p>
        </div>
        <a href="{{ route('coordinador.solicitudes.index') }}" class="text-slate-400 hover:text-white text-sm font-medium transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a Solicitudes
        </a>
    </div>

    @if ($errors->any())
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 shadow-lg">
            <div class="flex items-center gap-2 text-rose-400 font-bold mb-2">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Por favor, corrige los siguientes campos de la solicitud:</span>
            </div>
            <ul class="list-disc list-inside text-rose-300 text-xs space-y-1 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('coordinador.solicitudes.store') }}" method="POST" class="space-y-6" novalidate>
        @csrf
        
        <!-- Datos Personales -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-lg font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Datos Personales
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Nombres <span class="text-rose-400">*</span></label>
                    <input type="text" name="nombres" value="{{ old('nombres') }}" required placeholder="Ej: María Elena" 
                           class="w-full bg-slate-950 border @error('nombres') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('nombres')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Apellidos <span class="text-rose-400">*</span></label>
                    <input type="text" name="apellidos" value="{{ old('apellidos') }}" required placeholder="Ej: González López" 
                           class="w-full bg-slate-950 border @error('apellidos') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('apellidos')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Número de Teléfono Celular <span class="text-rose-400">*</span></label>
                    <input type="tel" name="telefono" value="{{ old('telefono') }}" required maxlength="10" placeholder="10 dígitos (ej: 8112345678)" 
                           class="w-full bg-slate-950 border @error('telefono') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('telefono')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Fecha de Nacimiento <span class="text-rose-400">*</span></label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required 
                           class="w-full bg-slate-950 border @error('fecha_nacimiento') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('fecha_nacimiento')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Lugar de Nacimiento</label>
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento') }}" placeholder="Ej: Monterrey, Nuevo León" 
                           class="w-full bg-slate-950 border @error('lugar_nacimiento') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('lugar_nacimiento')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">CURP (18 caracteres) <span class="text-rose-400">*</span></label>
                    <input type="text" name="curp" value="{{ old('curp') }}" required maxlength="18" placeholder="XXXX000000XXXXXX00" 
                           class="w-full bg-slate-950 border @error('curp') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none uppercase font-mono transition">
                    @error('curp')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">RFC (12 o 13 caracteres) <span class="text-rose-400">*</span></label>
                    <input type="text" name="rfc" value="{{ old('rfc') }}" required maxlength="13" placeholder="XXXX000000XXX" 
                           class="w-full bg-slate-950 border @error('rfc') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none uppercase font-mono transition">
                    @error('rfc')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Dirección -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-lg font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Dirección Domiciliaria
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Calle y Número <span class="text-rose-400">*</span></label>
                    <input type="text" name="calle" value="{{ old('calle') }}" required placeholder="Calle, número exterior e interior" 
                           class="w-full bg-slate-950 border @error('calle') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('calle')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Colonia <span class="text-rose-400">*</span></label>
                    <input type="text" name="colonia" value="{{ old('colonia') }}" required placeholder="Colonia o Fraccionamiento" 
                           class="w-full bg-slate-950 border @error('colonia') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('colonia')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Código Postal (5 dígitos) <span class="text-rose-400">*</span></label>
                    <input type="text" name="codigo_postal" value="{{ old('codigo_postal') }}" required maxlength="5" placeholder="64000" 
                           class="w-full bg-slate-950 border @error('codigo_postal') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono transition">
                    @error('codigo_postal')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Ciudad o Municipio <span class="text-rose-400">*</span></label>
                    <input type="text" name="ciudad" value="{{ old('ciudad') }}" required placeholder="Ej: Monterrey" 
                           class="w-full bg-slate-950 border @error('ciudad') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('ciudad')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Estado de la República <span class="text-rose-400">*</span></label>
                    <input type="text" name="estado_republica" value="{{ old('estado_republica') }}" required placeholder="Ej: Nuevo León" 
                           class="w-full bg-slate-950 border @error('estado_republica') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('estado_republica')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6" 
             x-data="{ familiares: {{ Js::from(old('datos_familiares', [])) }} }">
            <h2 class="text-lg font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Información del Hogar y Referencias
            </h2>
            
            <div class="space-y-4 border border-slate-800 p-4 rounded-xl bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-indigo-300">Familiares de Referencia</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ingresa información de familiares cercanos (Hijos, Hermanos, Esposo/a, Padres).</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <template x-for="(fam, index) in familiares" :key="index">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-900 rounded-xl border border-slate-800 relative">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre Completo <span class="text-rose-400">*</span></label>
                                <input type="text" :name="`datos_familiares[${index}][nombre]`" x-model="fam.nombre" required placeholder="Nombre del familiar"
                                       class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Parentesco <span class="text-rose-400">*</span></label>
                                <select :name="`datos_familiares[${index}][parentesco]`" x-model="fam.parentesco" required 
                                        class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                    <option value="Hijo/a">Hijo/a</option>
                                    <option value="Hermano/a">Hermano/a</option>
                                    <option value="Esposa/o">Esposa/o</option>
                                    <option value="Padre/Madre">Padre/Madre</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="flex items-end justify-between gap-2">
                                <div class="flex-grow">
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Teléfono de Contacto (Opcional)</label>
                                    <input type="text" :name="`datos_familiares[${index}][contacto]`" x-model="fam.contacto" placeholder="10 dígitos"
                                           class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                </div>
                                <button type="button" @click="familiares.splice(index, 1)" 
                                        class="p-2 bg-rose-500/10 border border-rose-500/30 text-rose-400 rounded-lg hover:bg-rose-500/20 transition shrink-0" title="Eliminar referencia">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="familiares.push({nombre: '', parentesco: 'Hijo/a', contacto: ''})" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 rounded-xl text-xs font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Agregar Familiar de Referencia
                </button>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Datos de Vehículos (Opcional)</label>
                <textarea name="datos_vehiculos" rows="2" 
                          class="w-full bg-slate-950 border @error('datos_vehiculos') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none placeholder-slate-600 transition" 
                          placeholder="Ej: Nissan Versa 2018, Placas ABC-123. Si no tiene escribir 'No tengo'">{{ old('datos_vehiculos') }}</textarea>
                @error('datos_vehiculos')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Descripción de la Casa o Vivienda <span class="text-rose-400">*</span></label>
                <textarea name="datos_casa" rows="3" required 
                          class="w-full bg-slate-950 border @error('datos_casa') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none placeholder-slate-600 transition" 
                          placeholder="Color de fachada, número de plantas, características de la vivienda y tipo de propiedad (propia, rentada, familiar)">{{ old('datos_casa') }}</textarea>
                @error('datos_casa')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Referencias Laborales (Opcional)</label>
                <textarea name="referencias_laborales" rows="2" 
                          class="w-full bg-slate-950 border @error('referencias_laborales') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none placeholder-slate-600 transition" 
                          placeholder="Ej: Comercio propio / Empleo anterior en Empresa XYZ, Tel: 81-5555-9999, Jefe Directo: Pedro Gómez">{{ old('referencias_laborales') }}</textarea>
                @error('referencias_laborales')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-2">
            <a href="{{ route('coordinador.solicitudes.index') }}" 
               class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-sm font-semibold transition">
                Cancelar
            </a>
            <button type="submit" 
                    class="w-full sm:w-auto px-7 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/30 text-sm font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Guardar Solicitud
            </button>
        </div>
    </form>
</div>
@endsection
