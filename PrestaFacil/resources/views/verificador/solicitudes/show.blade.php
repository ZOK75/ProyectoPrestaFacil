@extends('layouts.app')

@section('title', 'Verificación y Corrección Presencial de Solicitud - PrestaFácil')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ 
    dictamen: '{{ old('dictamen_verificador', $solicitud->dictamen_verificador !== 'pendiente' ? $solicitud->dictamen_verificador : 'aceptado') }}',
    checks: {{ Js::from(old('checks', $solicitud->datos_verificacion['checks'] ?? [
        'personales' => true,
        'direccion' => true,
        'hogar' => true,
        'familiares' => true
    ])) }},
    toggleCheck(key) {
        this.checks[key] = !this.checks[key];
    }
}">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                    Verificación Presencial de Campo
                </span>
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                    Sucursal: {{ $solicitud->sucursal?->nombre }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight mt-2">
                Expediente de Verificación: {{ $solicitud->nombre_completo }}
            </h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                Registrado originalmente por: <strong class="text-indigo-300">{{ $solicitud->coordinador?->name ?? 'Coordinador' }}</strong> &bull; Confirma los datos con un check o edita los campos si encontraste inconsistencias en la visita física.
            </p>
        </div>
        <a href="{{ route('verificador.dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver al Panel
        </a>
    </div>

    @if ($errors->any())
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 shadow-lg">
            <div class="flex items-center gap-2 text-rose-400 font-bold mb-2 text-sm">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Por favor, revisa y corrige los siguientes campos:</span>
            </div>
            <ul class="list-disc list-inside text-rose-300 text-xs space-y-1 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form novalidate action="{{ route('verificador.solicitudes.procesar', $solicitud->id) }}" method="POST" class="space-y-6" novalidate>
        @csrf

        <!-- 1. Datos Personales -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <h2 class="text-base sm:text-lg font-bold text-white">1. Datos Personales e Identificación</h2>
                </div>
                <label class="flex items-center gap-2 cursor-pointer select-none bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-emerald-500/50 transition">
                    <input type="checkbox" name="checks[personales]" value="1" x-model="checks.personales" class="w-4 h-4 text-emerald-600 rounded bg-slate-900 border-slate-700 focus:ring-emerald-500">
                    <span class="text-xs font-bold" :class="checks.personales ? 'text-emerald-400' : 'text-slate-400'">
                        <span x-text="checks.personales ? 'Identificación Validada' : 'Pendiente de Validar'"></span>
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Nombres <span class="text-rose-400">*</span></label>
                    <input type="text" name="nombres" value="{{ old('nombres', $solicitud->getDatoVerificado('nombres')) }}" required 
                           class="w-full bg-slate-950 border @error('nombres') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('nombres')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Apellidos <span class="text-rose-400">*</span></label>
                    <input type="text" name="apellidos" value="{{ old('apellidos', $solicitud->getDatoVerificado('apellidos')) }}" required 
                           class="w-full bg-slate-950 border @error('apellidos') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('apellidos')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Teléfono Celular Verificado <span class="text-rose-400">*</span></label>
                    <input type="tel" name="telefono" value="{{ old('telefono', $solicitud->getDatoVerificado('telefono')) }}" required maxlength="10" 
                           class="w-full bg-slate-950 border @error('telefono') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono transition">
                    @error('telefono')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Fecha de Nacimiento <span class="text-rose-400">*</span></label>
                    @php
                        $fn = $solicitud->getDatoVerificado('fecha_nacimiento');
                        if ($fn instanceof \Carbon\Carbon || $fn instanceof \DateTimeInterface) {
                            $fn = $fn->format('Y-m-d');
                        }
                    @endphp
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $fn) }}" required 
                           class="w-full bg-slate-950 border @error('fecha_nacimiento') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('fecha_nacimiento')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Lugar de Nacimiento</label>
                    <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento', $solicitud->getDatoVerificado('lugar_nacimiento')) }}" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">CURP (18 caracteres) <span class="text-rose-400">*</span></label>
                    <input type="text" name="curp" value="{{ old('curp', $solicitud->getDatoVerificado('curp')) }}" required maxlength="18" 
                           class="w-full bg-slate-950 border @error('curp') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none uppercase font-mono transition">
                    @error('curp')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">RFC (12 o 13 caracteres) <span class="text-rose-400">*</span></label>
                    <input type="text" name="rfc" value="{{ old('rfc', $solicitud->getDatoVerificado('rfc')) }}" required maxlength="13" 
                           class="w-full bg-slate-950 border @error('rfc') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none uppercase font-mono transition">
                    @error('rfc')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 2. Dirección Domiciliaria -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <h2 class="text-base sm:text-lg font-bold text-white">2. Dirección Domiciliaria (Comprobante Físico)</h2>
                </div>
                <label class="flex items-center gap-2 cursor-pointer select-none bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-emerald-500/50 transition">
                    <input type="checkbox" name="checks[direccion]" value="1" x-model="checks.direccion" class="w-4 h-4 text-emerald-600 rounded bg-slate-900 border-slate-700 focus:ring-emerald-500">
                    <span class="text-xs font-bold" :class="checks.direccion ? 'text-emerald-400' : 'text-slate-400'">
                        <span x-text="checks.direccion ? 'Domicilio Verificado en Campo' : 'Pendiente de Validar'"></span>
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Calle y Número <span class="text-rose-400">*</span></label>
                    <input type="text" name="calle" value="{{ old('calle', $solicitud->getDatoVerificado('calle')) }}" required 
                           class="w-full bg-slate-950 border @error('calle') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('calle')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Colonia <span class="text-rose-400">*</span></label>
                    <input type="text" name="colonia" value="{{ old('colonia', $solicitud->getDatoVerificado('colonia')) }}" required 
                           class="w-full bg-slate-950 border @error('colonia') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('colonia')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Código Postal (5 dígitos) <span class="text-rose-400">*</span></label>
                    <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $solicitud->getDatoVerificado('codigo_postal')) }}" required maxlength="5" 
                           class="w-full bg-slate-950 border @error('codigo_postal') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono transition">
                    @error('codigo_postal')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Ciudad o Municipio <span class="text-rose-400">*</span></label>
                    <input type="text" name="ciudad" value="{{ old('ciudad', $solicitud->getDatoVerificado('ciudad')) }}" required 
                           class="w-full bg-slate-950 border @error('ciudad') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('ciudad')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Estado de la República <span class="text-rose-400">*</span></label>
                    <input type="text" name="estado_republica" value="{{ old('estado_republica', $solicitud->getDatoVerificado('estado_republica')) }}" required 
                           class="w-full bg-slate-950 border @error('estado_republica') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    @error('estado_republica')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 3. Información del Hogar, Vehículos y Referencias -->
        @php
            $familiaresData = old('datos_familiares', $solicitud->getDatoVerificado('datos_familiares', []));
            if (!is_array($familiaresData)) {
                $familiaresData = [];
            }
        @endphp
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6" 
             x-data="{ familiares: {{ Js::from($familiaresData) }} }">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <h2 class="text-base sm:text-lg font-bold text-white">3. Vivienda, Referencias y Vehículos</h2>
                </div>
                <label class="flex items-center gap-2 cursor-pointer select-none bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-emerald-500/50 transition">
                    <input type="checkbox" name="checks[hogar]" value="1" x-model="checks.hogar" class="w-4 h-4 text-emerald-600 rounded bg-slate-900 border-slate-700 focus:ring-emerald-500">
                    <span class="text-xs font-bold" :class="checks.hogar ? 'text-emerald-400' : 'text-slate-400'">
                        <span x-text="checks.hogar ? 'Vivienda e Inspección Acreditadas' : 'Pendiente de Validar'"></span>
                    </span>
                </label>
            </div>

            <!-- Familiares -->
            <div class="space-y-4 border border-slate-800 p-4 rounded-xl bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-indigo-300">Familiares de Referencia</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Confirma o ajusta los contactos de familiares verificados durante la visita.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(fam, index) in familiares" :key="index">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 bg-slate-900 rounded-xl border border-slate-800 relative">
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
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Teléfono de Contacto</label>
                                    <input type="text" :name="`datos_familiares[${index}][contacto]`" x-model="fam.contacto" placeholder="10 dígitos"
                                           class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none font-mono">
                                </div>
                                <button type="button" @click="familiares.splice(index, 1)" 
                                        class="p-2 bg-rose-500/10 border border-rose-500/30 text-rose-400 rounded-lg hover:bg-rose-500/20 transition shrink-0" title="Eliminar familiar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="familiares.push({nombre: '', parentesco: 'Hijo/a', contacto: ''})" 
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 rounded-xl text-xs font-semibold transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Agregar Familiar
                </button>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Descripción de la Casa o Vivienda Verificada <span class="text-rose-400">*</span></label>
                <textarea name="datos_casa" rows="3" required 
                          class="w-full bg-slate-950 border @error('datos_casa') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('datos_casa', $solicitud->getDatoVerificado('datos_casa')) }}</textarea>
                @error('datos_casa')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Datos de Vehículos</label>
                <textarea name="datos_vehiculos" rows="2" 
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('datos_vehiculos', $solicitud->getDatoVerificado('datos_vehiculos')) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Referencias Laborales / Comercio</label>
                <textarea name="referencias_laborales" rows="2" 
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('referencias_laborales', $solicitud->getDatoVerificado('referencias_laborales')) }}</textarea>
            </div>
        </div>

        <!-- 4. Dictamen del Verificador y Notas de Visita -->
        <div class="bg-slate-900 border border-indigo-500/40 rounded-2xl p-6 shadow-2xl space-y-6">
            <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                <h2 class="text-base sm:text-lg font-bold text-white">4. Dictamen Final del Verificador y Observaciones</h2>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Dictamen de la Verificación Presencial <span class="text-rose-400">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition select-none"
                           :class="dictamen === 'aceptado' ? 'bg-emerald-500/10 border-emerald-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="dictamen_verificador" value="aceptado" x-model="dictamen" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-sm block" :class="dictamen === 'aceptado' ? 'text-emerald-300' : 'text-slate-300'">ACEPTADO (Recomendado)</span>
                            <span class="text-xs text-slate-400 block mt-0.5">La candidata cumple con los requisitos y el domicilio fue acreditado satisfactoriamente.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition select-none"
                           :class="dictamen === 'rechazado' ? 'bg-rose-500/10 border-rose-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="dictamen_verificador" value="rechazado" x-model="dictamen" class="w-4 h-4 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-sm block" :class="dictamen === 'rechazado' ? 'text-rose-300' : 'text-slate-300'">RECHAZADO</span>
                            <span class="text-xs text-slate-400 block mt-0.5">Se encontraron inconsistencias graves o falsedad en el domicilio / información.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Notas de Visita Domiciliaria y Justificación del Dictamen <span class="text-rose-400">*</span></label>
                <textarea name="comentarios_verificador" rows="3" required placeholder="Describe las condiciones observadas en la visita, legitimidad de identificación y comprobante de domicilio presentado..."
                          class="w-full bg-slate-950 border @error('comentarios_verificador') border-rose-500 ring-1 ring-rose-500 @else border-slate-800 @enderror rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('comentarios_verificador', $solicitud->comentarios_verificador) }}</textarea>
                @error('comentarios_verificador')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('verificador.dashboard') }}" 
                   class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-sm font-semibold transition">
                    Cancelar
                </a>
                <button type="submit" 
                        class="w-full sm:w-auto px-7 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/30 text-sm font-bold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Enviar Corrección y Dictamen a Gerencia
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

