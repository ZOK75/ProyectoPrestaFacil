<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Postulación como Nueva Distribuidora - PrestaFácil</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased flex flex-col min-h-screen">

    <!-- Navbar Minimalista -->
    <header class="bg-slate-900/90 backdrop-blur-md border-b border-slate-800 py-4 px-4 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-md font-bold bg-gradient-to-r from-white to-indigo-300 bg-clip-text text-transparent">PrestaFácil</span>
            </div>
            <div class="text-xs text-slate-400 font-semibold bg-slate-800 px-3 py-1 rounded-full border border-slate-700">
                Sucursal: {{ $coordinador->sucursal?->nombre ?? 'General' }}
            </div>
        </div>
    </header>

    <!-- Contenido -->
    <main class="flex-grow w-full max-w-4xl mx-auto px-4 py-8">
        
        <!-- Tarjeta de Éxito -->
        @if(session('success_postulacion'))
            <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-6 sm:p-8 shadow-2xl text-center space-y-6 max-w-xl mx-auto mt-8">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/30">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-white">¡Postulación Enviada!</h2>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        {{ session('success_postulacion') }}
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-800 text-xs text-slate-500">
                    Puedes cerrar esta ventana de forma segura.
                </div>
            </div>
        @else

            <!-- Banner Informativo -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/20 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl mb-8">
                <div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Solicitud de Registro
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2.5">
                        Únete como Distribuidora
                    </h1>
                    <p class="text-slate-400 text-sm mt-1.5 leading-relaxed">
                        Completa el formulario a continuación con tus datos reales. Tu información será procesada por tu coordinador asignado, <strong>{{ $coordinador->name }}</strong>, y posteriormente validada en una visita de verificación presencial.
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 mb-6">
                    <div class="text-rose-400 font-semibold mb-2 text-sm">Por favor, corrige los siguientes errores:</div>
                    <ul class="list-disc list-inside text-rose-400/80 text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form novalidate action="{{ route('postulacion.store', $coordinador->id) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Datos Personales -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span> Datos Personales
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Nombres *</label>
                            <input type="text" name="nombres" value="{{ old('nombres') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Apellidos *</label>
                            <input type="text" name="apellidos" value="{{ old('apellidos') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Número de Teléfono *</label>
                            <input type="text" name="telefono" value="{{ old('telefono') }}" required placeholder="10 dígitos" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Fecha de Nacimiento *</label>
                            <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Lugar de Nacimiento</label>
                            <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento') }}" placeholder="Ej: Monterrey, Nuevo León" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">CURP (18 caracteres) *</label>
                            <input type="text" name="curp" value="{{ old('curp') }}" required maxlength="18" placeholder="XXXX000000XXXXXX00" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition uppercase">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">RFC (13 caracteres) *</label>
                            <input type="text" name="rfc" value="{{ old('rfc') }}" required maxlength="13" placeholder="XXXX000000XXX" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition uppercase">
                        </div>
                    </div>
                </div>

                <!-- Dirección -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span> Dirección Domiciliaria
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Calle y Número Domiciliario *</label>
                            <input type="text" name="calle" value="{{ old('calle') }}" required placeholder="Calle, número exterior e interior" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Colonia *</label>
                            <input type="text" name="colonia" value="{{ old('colonia') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Código Postal *</label>
                            <input type="text" name="codigo_postal" value="{{ old('codigo_postal') }}" required maxlength="10" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Ciudad o Municipio *</label>
                            <input type="text" name="ciudad" value="{{ old('ciudad') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Estado *</label>
                            <input type="text" name="estado_republica" value="{{ old('estado_republica') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- Familiares más cercanos -->
                <div x-data="{ familiares: [] }" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-white mb-2 border-b border-slate-800 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span> Familiares más Cercanos
                    </h2>
                    <p class="text-xs text-slate-400 mb-4">Ingresa información de tus familiares (Hijos, Hermanos, Esposa, Padres) como referencias.</p>
                    
                    <div class="space-y-3 mb-4">
                        <template x-for="(fam, index) in familiares" :key="index">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-950/60 rounded-xl border border-slate-800 relative">
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre Completo</label>
                                    <input type="text" :name="`datos_familiares[${index}][nombre]`" required class="w-full bg-slate-900 border border-slate-800 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Parentesco</label>
                                    <select :name="`datos_familiares[${index}][parentesco]`" required class="w-full bg-slate-900 border border-slate-800 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                        <option value="Hijo/a">Hijo/a</option>
                                        <option value="Hermano/a">Hermano/a</option>
                                        <option value="Esposa/o">Esposa/o</option>
                                        <option value="Padre/Madre">Padre/Madre</option>
                                    </select>
                                </div>
                                <div class="flex items-end justify-between gap-2">
                                    <div class="flex-grow">
                                        <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Contacto (Opcional)</label>
                                        <input type="text" :name="`datos_familiares[${index}][contacto]`" class="w-full bg-slate-900 border border-slate-800 rounded-lg text-white px-3 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none" placeholder="Teléfono o Celular">
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

                    <button type="button" @click="familiares.push({nombre: '', parentesco: 'Hijo/a', contacto: ''})" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/20 text-indigo-400 rounded-xl text-xs font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar Familiar
                    </button>
                </div>

                <!-- Automóviles y Casa -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h2 class="text-lg font-bold text-white mb-2 border-b border-slate-800 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span> Información del Hogar y Automóviles
                    </h2>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Datos de sus Automóviles</label>
                        <textarea name="datos_vehiculos" rows="2" placeholder="Marca, modelo, año, placas (si posee vehículos). Si no cuenta con auto, favor de indicar 'No tengo'." class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">{{ old('datos_vehiculos') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Datos y Descripción de la Casa *</label>
                        <textarea name="datos_casa" rows="3" required placeholder="Color de fachada, plantas, distribución o tipo de propiedad (ej. rentada, propia, familiar)" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">{{ old('datos_casa') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Referencias Laborales</label>
                        <textarea name="referencias_laborales" rows="2" placeholder="Nombre de empresa actual o previa, giro, teléfono de contacto y jefe directo (opcional)" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition">{{ old('referencias_laborales') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/30 text-sm font-bold tracking-wide transition">
                        Enviar Solicitud de Postulación
                    </button>
                </div>
            </form>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-4 mt-auto">
        <div class="max-w-4xl mx-auto px-4 text-center text-[11px] text-slate-500">
            PrestaFácil &copy; {{ date('Y') }} - Sistema de Registro de Distribuidoras
        </div>
    </footer>

</body>
</html>
