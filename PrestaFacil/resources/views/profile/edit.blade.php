@extends('layouts.app')

@section('title', 'Mi Perfil - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Encabezado de Perfil -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/20 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-black text-2xl shrink-0 shadow-inner">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">
                    Mi Perfil
                </h1>
                <p class="text-slate-400 text-sm mt-1">Gestiona tu información personal y opciones de seguridad.</p>
            </div>
        </div>
    </div>

    <!-- Secciones del Perfil -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <div class="space-y-8">
            <!-- Información del Perfil -->
            <div class="bg-slate-900 border border-slate-800 shadow-xl sm:rounded-2xl overflow-hidden p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <!-- Actualizar Contraseña -->
            <div class="bg-slate-900 border border-slate-800 shadow-xl sm:rounded-2xl overflow-hidden p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
