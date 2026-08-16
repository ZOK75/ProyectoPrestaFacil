@extends('layouts.app')

@section('title', 'Detalle de Solicitud de Distribuidora - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Detalle de Solicitud</h1>
            <p class="text-slate-400 text-xs mt-1">Revisa y valida la información del aspirante a distribuidora.</p>
        </div>
        <a href="{{ route('coordinador.solicitudes.index') }}" class="text-slate-400 hover:text-white text-sm font-medium transition flex items-center gap-1.5">
            &larr; Volver a Solicitudes
        </a>
    </div>

    <!-- Tarjeta Principal de Información -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Estado y Control -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Estado de Solicitud</h3>
                <div>
                    @if($solicitud->estado === 'en espera')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-500/30 uppercase tracking-wide">
                            En Espera
                        </span>
                    @elseif($solicitud->estado === 'en espera de verificacion')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wide animate-pulse">
                            En Verificación
                        </span>
                    @elseif($solicitud->estado === 'aprobado')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wide">
                            Aprobado
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase tracking-wide">
                            Rechazado
                        </span>
                    @endif
                </div>

                <div class="text-xs text-slate-400 space-y-1.5 pt-2 border-t border-slate-800/60">
                    <div><span class="text-slate-500">Registrado:</span> {{ $solicitud->created_at->format('d/m/Y H:i') }}</div>
                    <div><span class="text-slate-500">Sucursal:</span> {{ $solicitud->sucursal?->nombre }}</div>
                    @if($solicitud->coordinador)
                        <div><span class="text-slate-500">Coordinador:</span> {{ $solicitud->coordinador->name }}</div>
                    @endif
                    @if($solicitud->verificador)
                        <div><span class="text-slate-500">Verificador:</span> {{ $solicitud->verificador->name }}</div>
                    @endif
                </div>

                @if($solicitud->estado === 'en espera')
                    <div class="pt-4 border-t border-slate-800">
                        <form action="{{ route('coordinador.solicitudes.enviar-verificacion', $solicitud) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-sky-950/20 transition"
                                    onclick="return confirm('¿Confirmas que los datos ingresados son correctos y deseas turnar la solicitud al verificador?')">
                                Validar y Enviar a Verificación
                            </button>
                        </form>
                    </div>
                @endif
            </div>


        </div>

        <!-- Información Detallada -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Datos Personales y Dirección -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                <div>
                    <h2 class="text-base font-bold text-white mb-3">Información del Solicitante</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block text-xs text-slate-500">Nombre Completo</span>
                            <span class="text-slate-200 font-semibold">{{ $solicitud->nombres }} {{ $solicitud->apellidos }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Número de Teléfono</span>
                            <span class="text-slate-200 font-mono">{{ $solicitud->telefono }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">CURP</span>
                            <span class="text-slate-200 font-mono uppercase font-semibold">{{ $solicitud->curp }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">RFC</span>
                            <span class="text-slate-200 font-mono uppercase font-semibold">{{ $solicitud->rfc }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Fecha de Nacimiento</span>
                            <span class="text-slate-200 font-semibold">{{ $solicitud->fecha_nacimiento?->format('d/m/Y') ?? 'No registrada' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Lugar de Nacimiento</span>
                            <span class="text-slate-200">{{ $solicitud->lugar_nacimiento ?? 'No proporcionado' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-800/60">
                    <h2 class="text-base font-bold text-white mb-3">Dirección Domiciliaria</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="col-span-2">
                            <span class="block text-xs text-slate-500">Calle y Número</span>
                            <span class="text-slate-200">{{ $solicitud->calle }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Colonia</span>
                            <span class="text-slate-200">{{ $solicitud->colonia }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Código Postal</span>
                            <span class="text-slate-200 font-mono">{{ $solicitud->codigo_postal }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Ciudad o Municipio</span>
                            <span class="text-slate-200">{{ $solicitud->ciudad }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Estado</span>
                            <span class="text-slate-200">{{ $solicitud->estado_republica }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Familiares Cercanos -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-white">Familiares más Cercanos</h2>
                @if(!empty($solicitud->datos_familiares) && is_array($solicitud->datos_familiares))
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($solicitud->datos_familiares as $fam)
                            <div class="p-3.5 bg-slate-950 rounded-xl border border-slate-800/80 flex items-center justify-between text-sm">
                                <div>
                                    <span class="font-semibold text-slate-200 block">{{ $fam['nombre'] ?? 'Sin Nombre' }}</span>
                                    <span class="text-xs text-slate-500">Parentesco: <strong class="text-slate-400">{{ $fam['parentesco'] ?? 'Familiar' }}</strong></span>
                                </div>
                                @if(!empty($fam['contacto']))
                                    <div class="text-right text-xs text-indigo-400 font-mono">
                                        {{ $fam['contacto'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic">No se registraron datos de familiares cercanos.</p>
                @endif
            </div>

            <!-- Datos Extra y Hogar -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                <div>
                    <h2 class="text-base font-bold text-white mb-2">Automóviles</h2>
                    <p class="text-sm text-slate-300 bg-slate-950 p-4 rounded-xl border border-slate-800 whitespace-pre-line leading-relaxed">
                        {{ $solicitud->datos_vehiculos ?? 'No se proporcionaron datos de vehículos.' }}
                    </p>
                </div>

                <div class="pt-6 border-t border-slate-800/60">
                    <h2 class="text-base font-bold text-white mb-2">Descripción de la Casa</h2>
                    <p class="text-sm text-slate-300 bg-slate-950 p-4 rounded-xl border border-slate-800 whitespace-pre-line leading-relaxed">
                        {{ $solicitud->datos_casa ?? 'No se proporcionó descripción de la casa.' }}
                    </p>
                </div>

                <div class="pt-6 border-t border-slate-800/60">
                    <h2 class="text-base font-bold text-white mb-2">Referencias Laborales</h2>
                    <p class="text-sm text-slate-300 bg-slate-950 p-4 rounded-xl border border-slate-800 whitespace-pre-line leading-relaxed">
                        {{ $solicitud->referencias_laborales ?? 'No se proporcionaron referencias laborales.' }}
                    </p>
                </div>
            </div>

            <!-- Notas de resolución -->
            @if($solicitud->estado === 'aprobado' || $solicitud->estado === 'rechazado')
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-white">Detalles del Cierre</h2>
                    <div class="p-4 rounded-xl border {{ $solicitud->estado === 'aprobado' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400' }}">
                        <div class="text-xs font-semibold uppercase tracking-wider mb-2">Resolución</div>
                        <p class="text-sm leading-relaxed whitespace-pre-line font-medium text-slate-200">
                            {{ $solicitud->observaciones_resolucion ?? 'Sin observaciones anotadas por el verificador.' }}
                        </p>
                        @if($solicitud->resolved_at)
                            <span class="block text-[10px] text-slate-500 mt-3 font-semibold">Resuelto el {{ $solicitud->resolved_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
