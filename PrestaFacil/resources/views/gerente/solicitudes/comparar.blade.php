@extends('layouts.app')

@section('title', 'Evaluación Comparativa de Solicitud - PrestaFácil')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 sm:space-y-8" x-data="{
    decision: 'aprobar',
    curpVal: '{{ $solicitud->getDatoVerificado('curp') }}',
    limiteCredito: '30000',
    emailVal: '{{ old('email', strtolower(str_replace(' ', '.', trim($solicitud->getDatoVerificado('nombres')))) . '.' . strtolower(str_replace(' ', '', trim($solicitud->getDatoVerificado('apellidos')))) . '@prestafacil.com') }}'
}">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                            Gerencia General Corporativa
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            Gerencia de Sucursal
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        Sucursal: {{ $solicitud->sucursal?->nombre }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase tracking-wider">
                        Expediente Comparativo No Editable
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Evaluación de Candidata: {{ $solicitud->nombre_completo }}
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">
                    Compara lado a lado los datos capturados por el Coordinador contra las correcciones y verificaciones de campo del Verificador.
                </p>
            </div>

            @php
                $volverRuta = (Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador()) 
                    ? route('gerente-general.dashboard') 
                    : route('gerente-sucursal.dashboard');
            @endphp
            <a href="{{ $volverRuta }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition shrink-0">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Panel
            </a>
        </div>
    </div>

    <!-- Dictamen y Notas del Verificador -->
    <div class="bg-slate-900 border {{ $solicitud->dictamen_verificador === 'aceptado' ? 'border-emerald-500/40 bg-emerald-950/10' : 'border-rose-500/40 bg-rose-950/10' }} rounded-2xl p-5 sm:p-6 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Dictamen Emitido por Verificador de Campo</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Verificador: <strong class="text-slate-200">{{ $solicitud->verificador?->name ?? 'Verificador Asignado' }}</strong></span>
                @if($solicitud->dictamen_verificador === 'aceptado')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wide">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aceptado / Favorable
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase tracking-wide">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Rechazado en Campo
                    </span>
                @endif
            </div>
        </div>

        <div>
            <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Notas de la Visita Domiciliaria:</span>
            <p class="text-xs sm:text-sm text-slate-200 italic bg-slate-950/60 p-3.5 rounded-xl border border-slate-800 leading-relaxed">
                "{{ $solicitud->comentarios_verificador ?? 'Sin comentarios adicionales.' }}"
            </p>
        </div>
    </div>

    @if (session('error'))
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 shadow-lg text-rose-300 text-sm font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 shadow-lg text-emerald-300 text-sm font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('warning'))
        <div class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 shadow-lg text-amber-300 text-sm font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 shadow-lg">
            <div class="flex items-center gap-2 text-rose-400 font-bold mb-2 text-sm">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Por favor, revisa los errores en la resolución:</span>
            </div>
            <ul class="list-disc list-inside text-rose-300 text-xs space-y-1 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- TABLA COMPARATIVA LADO A LADO -->
    <div class="space-y-6">

        <!-- 1. Datos Personales -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    1. Datos Personales e Identificación
                </h2>
                <span class="text-xs text-slate-400 font-medium">Comparativa Coordinador vs. Verificador</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-800">
                <!-- Columna Coordinador -->
                <div class="p-5 space-y-4 bg-slate-950/30">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                        <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Captura del Coordinador</span>
                        <span class="text-[11px] text-slate-500">{{ $solicitud->coordinador?->name }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500 block">Nombres:</span>
                            <span class="font-semibold text-slate-200">{{ $solicitud->nombres }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Apellidos:</span>
                            <span class="font-semibold text-slate-200">{{ $solicitud->apellidos }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Teléfono:</span>
                            <span class="font-mono text-slate-200">{{ $solicitud->telefono }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Fecha de Nacimiento:</span>
                            @php
                                $fnC = $solicitud->fecha_nacimiento;
                                $fnCFormatted = 'N/A';
                                if ($fnC) {
                                    if ($fnC instanceof \DateTimeInterface) {
                                        $fnCFormatted = $fnC->format('d/m/Y');
                                    } else {
                                        try {
                                            $fnCFormatted = \Carbon\Carbon::parse($fnC)->format('d/m/Y');
                                        } catch (\Throwable $e) {
                                            $fnCFormatted = $fnC;
                                        }
                                    }
                                }
                            @endphp
                            <span class="text-slate-200">{{ $fnCFormatted }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Lugar de Nacimiento:</span>
                            <span class="text-slate-200">{{ $solicitud->lugar_nacimiento ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">CURP:</span>
                            <span class="font-mono uppercase font-bold text-slate-200">{{ $solicitud->curp }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-500 block">RFC:</span>
                            <span class="font-mono uppercase font-bold text-slate-200">{{ $solicitud->rfc }}</span>
                        </div>
                    </div>
                </div>

                <!-- Columna Verificador -->
                <div class="p-5 space-y-4 bg-slate-900/80">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                        <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">Verificación en Campo</span>
                        <span class="text-[11px] text-slate-400">{{ $solicitud->verificador?->name ?? 'Verificador' }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500 block">Nombres:</span>
                            <span class="font-semibold {{ $solicitud->isCampoModificado('nombres') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('nombres') }}
                                @if($solicitud->isCampoModificado('nombres')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Apellidos:</span>
                            <span class="font-semibold {{ $solicitud->isCampoModificado('apellidos') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('apellidos') }}
                                @if($solicitud->isCampoModificado('apellidos')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Teléfono:</span>
                            <span class="font-mono {{ $solicitud->isCampoModificado('telefono') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('telefono') }}
                                @if($solicitud->isCampoModificado('telefono')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Fecha de Nacimiento:</span>
                            @php
                                $fnV = $solicitud->getDatoVerificado('fecha_nacimiento');
                                $fnVFormatted = 'N/A';
                                if ($fnV) {
                                    if ($fnV instanceof \DateTimeInterface) {
                                        $fnVFormatted = $fnV->format('d/m/Y');
                                    } else {
                                        try {
                                            $fnVFormatted = \Carbon\Carbon::parse($fnV)->format('d/m/Y');
                                        } catch (\Throwable $e) {
                                            $fnVFormatted = $fnV;
                                        }
                                    }
                                }
                            @endphp
                            <span class="{{ $solicitud->isCampoModificado('fecha_nacimiento') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $fnVFormatted }}
                                @if($solicitud->isCampoModificado('fecha_nacimiento')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Lugar de Nacimiento:</span>
                            <span class="text-slate-200">{{ $solicitud->getDatoVerificado('lugar_nacimiento') ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">CURP:</span>
                            <span class="font-mono uppercase font-bold {{ $solicitud->isCampoModificado('curp') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('curp') }}
                                @if($solicitud->isCampoModificado('curp')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-500 block">RFC:</span>
                            <span class="font-mono uppercase font-bold {{ $solicitud->isCampoModificado('rfc') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('rfc') }}
                                @if($solicitud->isCampoModificado('rfc')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Dirección Domiciliaria -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    2. Dirección Domiciliaria
                </h2>
                <span class="text-xs text-slate-400 font-medium">Comparativa de Domicilio</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-800">
                <!-- Coordinador -->
                <div class="p-5 space-y-3 bg-slate-950/30 text-xs">
                    <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider block pb-1 border-b border-slate-800/60">Datos Coordinador</span>
                    <div>
                        <span class="text-slate-500 block">Calle y Número:</span>
                        <span class="text-slate-200 font-medium">{{ $solicitud->calle }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-500 block">Colonia:</span>
                            <span class="text-slate-200">{{ $solicitud->colonia }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Código Postal:</span>
                            <span class="font-mono text-slate-200">{{ $solicitud->codigo_postal }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Ciudad:</span>
                            <span class="text-slate-200">{{ $solicitud->ciudad }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Estado:</span>
                            <span class="text-slate-200">{{ $solicitud->estado_republica }}</span>
                        </div>
                    </div>
                </div>

                <!-- Verificador -->
                <div class="p-5 space-y-3 bg-slate-900/80 text-xs">
                    <span class="text-xs font-bold text-amber-300 uppercase tracking-wider block pb-1 border-b border-slate-800/60">Datos Verificados en Domicilio</span>
                    <div>
                        <span class="text-slate-500 block">Calle y Número:</span>
                        <span class="font-medium {{ $solicitud->isCampoModificado('calle') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                            {{ $solicitud->getDatoVerificado('calle') }}
                            @if($solicitud->isCampoModificado('calle')) <span class="text-[9px] text-amber-400 font-bold">(Corregido)</span> @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-500 block">Colonia:</span>
                            <span class="{{ $solicitud->isCampoModificado('colonia') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('colonia') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Código Postal:</span>
                            <span class="font-mono {{ $solicitud->isCampoModificado('codigo_postal') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('codigo_postal') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Ciudad:</span>
                            <span class="{{ $solicitud->isCampoModificado('ciudad') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('ciudad') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Estado:</span>
                            <span class="{{ $solicitud->isCampoModificado('estado_republica') ? 'text-amber-300 bg-amber-500/10 px-1 rounded' : 'text-slate-200' }}">
                                {{ $solicitud->getDatoVerificado('estado_republica') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Vivienda, Vehículos y Referencias -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    3. Vivienda, Familiares y Referencias
                </h2>
                <span class="text-xs text-slate-400 font-medium">Inspección de Vivienda</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-800">
                <!-- Coordinador -->
                <div class="p-5 space-y-4 bg-slate-950/30 text-xs">
                    <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider block pb-1 border-b border-slate-800/60">Declaración Coordinador</span>
                    <div>
                        <span class="text-slate-500 block font-semibold">Descripción de la Casa:</span>
                        <p class="text-slate-300 bg-slate-950 p-2.5 rounded-lg border border-slate-800/80 mt-1 italic">
                            {{ $solicitud->datos_casa ?? 'Sin descripción' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 block font-semibold">Vehículos:</span>
                        <p class="text-slate-300 bg-slate-950 p-2.5 rounded-lg border border-slate-800/80 mt-1">
                            {{ $solicitud->datos_vehiculos ?? 'No reportados' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 block font-semibold">Referencias Laborales:</span>
                        <p class="text-slate-300 bg-slate-950 p-2.5 rounded-lg border border-slate-800/80 mt-1">
                            {{ $solicitud->referencias_laborales ?? 'No reportadas' }}
                        </p>
                    </div>
                </div>

                <!-- Verificador -->
                <div class="p-5 space-y-4 bg-slate-900/80 text-xs">
                    <span class="text-xs font-bold text-amber-300 uppercase tracking-wider block pb-1 border-b border-slate-800/60">Constatación Verificador</span>
                    <div>
                        <span class="text-slate-500 block font-semibold">Descripción de la Casa Verificada:</span>
                        <p class="text-slate-200 bg-slate-950 p-2.5 rounded-lg border border-slate-800 mt-1 italic {{ $solicitud->isCampoModificado('datos_casa') ? 'border-amber-500/40 text-amber-200' : '' }}">
                            {{ $solicitud->getDatoVerificado('datos_casa') ?? 'Sin descripción' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 block font-semibold">Vehículos Constatados:</span>
                        <p class="text-slate-200 bg-slate-950 p-2.5 rounded-lg border border-slate-800 mt-1">
                            {{ $solicitud->getDatoVerificado('datos_vehiculos') ?? 'No reportados' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 block font-semibold">Referencias Laborales:</span>
                        <p class="text-slate-200 bg-slate-950 p-2.5 rounded-lg border border-slate-800 mt-1">
                            {{ $solicitud->getDatoVerificado('referencias_laborales') ?? 'No reportadas' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- PANEL DE DECISIÓN GERENCIAL Y ALTA DE CUENTA -->
    @if($solicitud->estado === 'en espera')
        <div class="bg-slate-900 border border-indigo-500/50 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-white flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        Resolución Final de Gerencia y Activación de Cuenta
                    </h2>
                    <p class="text-slate-400 text-xs sm:text-sm mt-0.5">
                        Dictamina sobre el resultado de la solicitud. Si decides aprobar, se dará de alta la cuenta de distribuidora de inmediato.
                    </p>
                </div>
            </div>

            <form novalidate action="{{ route('gerente.solicitudes.decidir-con-cuenta', $solicitud->id) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="accion" :value="decision">

                <!-- Opciones de Decisión -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="decision === 'aprobar' ? 'bg-emerald-500/10 border-emerald-500 text-white shadow-lg shadow-emerald-950/20' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" value="aprobar" x-model="decision" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-sm block" :class="decision === 'aprobar' ? 'text-emerald-300' : 'text-slate-300'">APROBAR Y DAR DE ALTA</span>
                            <span class="text-xs text-slate-400 block mt-0.5">Acredita a la distribuidora y genera sus credenciales de acceso al sistema.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="decision === 'rechazar' ? 'bg-rose-500/10 border-rose-500 text-white shadow-lg shadow-rose-950/20' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" value="rechazar" x-model="decision" class="w-4 h-4 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-sm block" :class="decision === 'rechazar' ? 'text-rose-300' : 'text-slate-300'">RECHAZAR SOLICITUD</span>
                            <span class="text-xs text-slate-400 block mt-0.5">Descarta definitivamente la postulación de esta candidata.</span>
                        </div>
                    </label>
                </div>

                <!-- Formulario Dinámico al Aprobar -->
                <div x-show="decision === 'aprobar'" class="space-y-4 bg-slate-950/80 border border-emerald-500/30 p-5 rounded-2xl" x-transition>
                    <h3 class="text-sm font-bold text-emerald-300 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Parámetros de la Cuenta de Distribuidora
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Correo Electrónico Institucional *</label>
                            <input type="email" name="email" x-model="emailVal" :required="decision === 'aprobar'" placeholder="ejemplo@prestafacil.com"
                                   class="w-full bg-slate-900 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Línea de Crédito Inicial ($ MXN) *</label>
                            <input type="number" name="limite_credito" x-model="limiteCredito" step="1000" min="5000" max="500000" :required="decision === 'aprobar'"
                                   class="w-full bg-slate-900 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm font-bold font-mono text-emerald-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                        </div>
                    </div>

                    <!-- Ficha Resumen de Parámetros Automáticos -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-900/90 rounded-xl border border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-500 block uppercase font-bold text-[10px]">Contraseña Inicial:</span>
                            <span class="font-mono font-bold text-amber-300 text-xs">Misma que su CURP (<span x-text="curpVal"></span>)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block uppercase font-bold text-[10px]">Rol de Usuario:</span>
                            <span class="font-bold text-indigo-300">Distribuidora</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block uppercase font-bold text-[10px]">Sucursal y Coordinador:</span>
                            <span class="text-slate-200 font-semibold">{{ $solicitud->sucursal?->nombre }} &bull; Coord: {{ $solicitud->coordinador?->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Observaciones Gerenciales -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1 uppercase tracking-wider">Observaciones o Justificación de Gerencia (Opcional)</label>
                    <textarea name="observaciones_resolucion" rows="2" placeholder="Notas de la resolución gerencial..."
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"></textarea>
                </div>

                <!-- Botón de Acción -->
                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit" x-show="decision === 'rechazar'" 
                            onclick="return confirm('¿Confirmas el RECHAZO definitivo de esta solicitud?')"
                            class="inline-flex items-center gap-1.5 px-6 py-3 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Confirmar Rechazo Definitivo
                    </button>
                    <button type="submit" x-show="decision === 'aprobar'"
                            onclick="return confirm('¿Aprobar y dar de alta inmediatamente la cuenta de distribuidora con contraseña igual a la CURP?')"
                            class="px-7 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 text-xs font-bold transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aprobar y Activar Cuenta de Distribuidora
                    </button>
                </div>
            </form>
        </div>
    @elseif($solicitud->estado === 'aprobado')
        <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-6 shadow-xl text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Solicitud Aprobada y Cuenta Activada
            </span>
            <p class="text-xs text-slate-300">
                Esta solicitud ya fue aprobada y cuenta con un usuario activo en el sistema.
            </p>
        </div>
    @else
        <div class="bg-slate-900 border border-rose-500/30 rounded-2xl p-6 shadow-xl text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase tracking-wide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Solicitud Rechazada
            </span>
            <p class="text-xs text-slate-400 italic">
                "{{ $solicitud->observaciones_resolucion ?? 'Sin motivo registrado.' }}"
            </p>
        </div>
    @endif

</div>
@endsection