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

            @if(!Auth::user()->esVerificador())
            <!-- Autenticación en Dos Pasos (2FA) -->
            <div class="bg-slate-900 border border-slate-800 shadow-xl sm:rounded-2xl overflow-hidden p-6 sm:p-8">
                <div class="max-w-xl">
                    <h2 class="text-lg font-extrabold text-white tracking-tight flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                        Autenticación en Dos Pasos (2FA)
                    </h2>
                    <p class="text-slate-400 text-sm mt-1 mb-6">
                        Añade una capa extra de seguridad a tu cuenta usando la aplicación Google Authenticator.
                    </p>

                    <!-- Alertas de Estado -->
                    @if (session('status') === '2fa-enabled')
                        <div class="mb-6 text-sm font-medium text-emerald-400 bg-emerald-950/40 p-4 rounded-xl border border-emerald-800/60 flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            ¡La Autenticación en Dos Pasos ha sido activada correctamente!
                        </div>
                    @endif

                    @if (session('status') === '2fa-disabled')
                        <div class="mb-6 text-sm font-medium text-amber-400 bg-amber-950/40 p-4 rounded-xl border border-amber-800/60 flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            La Autenticación en Dos Pasos ha sido desactivada.
                        </div>
                    @endif

                    @if ($errors->has('one_time_password'))
                        <div class="mb-6 text-sm font-medium text-red-400 bg-red-950/40 p-4 rounded-xl border border-red-800/60 flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            {{ $errors->first('one_time_password') }}
                        </div>
                    @endif

                    <!-- CASO 1: EL USUARIO YA TIENE EL 2FA ACTIVADO -->
                    @if ($user->google2fa_enabled)
                        <div class="space-y-4">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Protegido con Google Authenticator
                            </div>
                            
                            <p class="text-sm text-slate-400">
                                Tu cuenta cuenta con la verificación en dos pasos activa. Se te solicitará un código cada vez que inicies sesión.
                            </p>

                            <form novalidate method="POST" action="{{ route('profile.2fa.desactivar') }}" class="pt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2.5 bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white border border-red-500/30 font-medium rounded-xl text-sm transition-all duration-200">
                                    Desactivar 2FA
                                </button>
                            </form>
                        </div>

                    <!-- CASO 2: EL USUARIO AÚN NO LO ACTIVA (MUESTRA EL QR) -->
                    @else
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-slate-300">
                                    1. Escanea este código QR con la app <span class="text-indigo-400 font-semibold">Google Authenticator</span>:
                                </p>
                                <!-- Contenedor del QR en marco blanco para legibilidad del escáner -->
                                <div class="flex justify-center p-4 bg-white rounded-2xl w-fit shadow-lg border border-slate-700">
                                    {!! $qrImage !!}
                                </div>
                            </div>

                            <form novalidate method="POST" action="{{ route('profile.2fa.activar') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="one_time_password" class="block text-sm font-medium text-slate-300 mb-2">
                                        2. Confirma ingresando el código de 6 dígitos que te muestra la app:
                                    </label>
                                    <input 
                                        type="text" 
                                        name="one_time_password" 
                                        id="one_time_password" 
                                        maxlength="6" 
                                        required 
                                        placeholder="000000"
                                        autocomplete="off"
                                        class="w-48 text-center text-xl font-bold tracking-widest bg-slate-950 border-slate-700 text-white rounded-xl focus:border-indigo-500 focus:ring-indigo-500 transition-all duration-200"
                                    />
                                </div>

                                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl text-sm shadow-lg shadow-emerald-600/20 transition-all duration-200">
                                    Confirmar y Activar 2FA
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            @endif
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