<x-guest-layout>
    <!-- Tarjeta de Registro -->
    <div class="w-full sm:max-w-md px-8 py-8 bg-white shadow-xl shadow-slate-200/50 overflow-hidden sm:rounded-2xl border border-slate-200">
        
        <!-- Encabezado / Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 mb-4 border border-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-wide">Crear una Cuenta</h2>
            <p class="text-sm text-slate-500 mt-1">Regístrate para gestionar tus vales y préstamos</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Nombre completo -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                    Nombre completo
                </label>
                <input id="name" 
                       type="text" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       autofocus 
                       autocomplete="name"
                       maxlength="50"
                       placeholder="Juan Pérez"
                       class="w-full px-4 py-3 bg-white border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-slate-800 placeholder-slate-400 text-sm transition duration-150 ease-in-out outline-none">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                    Correo electrónico
                </label>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="username"
                       placeholder="usuario@misvales.com"
                       class="w-full px-4 py-3 bg-white border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-slate-800 placeholder-slate-400 text-sm transition duration-150 ease-in-out outline-none">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                    Contraseña
                </label>
                <input id="password" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="new-password"
                       placeholder="••••••••"
                       class="w-full px-4 py-3 bg-white border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-slate-800 placeholder-slate-400 text-sm transition duration-150 ease-in-out outline-none">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirmar Contraseña -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                    Confirmar contraseña
                </label>
                <input id="password_confirmation" 
                       type="password" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password"
                       placeholder="••••••••"
                       class="w-full px-4 py-3 bg-white border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-slate-800 placeholder-slate-400 text-sm transition duration-150 ease-in-out outline-none">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <!-- Botón de Envío -->
            <div class="pt-3">
                <button type="submit" 
                        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 text-white font-semibold rounded-xl text-sm transition duration-200 shadow-lg shadow-emerald-600/20">
                    Registrarse
                </button>
            </div>

            <!-- Enlace a Login -->
            <div class="text-center pt-2">
                <a class="text-xs text-slate-600 hover:text-emerald-600 transition duration-150 ease-in-out font-medium" 
                   href="{{ route('login') }}">
                    ¿Ya tienes una cuenta? <span class="text-emerald-600 underline">Inicia sesión aquí</span>
                </a>
            </div>
        </form>

    </div>
</x-guest-layout>
