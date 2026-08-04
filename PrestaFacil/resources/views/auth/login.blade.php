<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-900">
        
        <!-- Tarjeta de Login -->
        <div class="w-full sm:max-w-md mt-6 px-8 py-8 bg-slate-800 shadow-2xl overflow-hidden sm:rounded-2xl border border-slate-700">
            
            <!-- Encabezado / Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-600/20 text-indigo-400 mb-4 border border-indigo-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-wide">Mis Vales</h2>
                <p class="text-sm text-slate-400 mt-1">Ingresa tus credenciales para acceder</p>
            </div>

            <!-- Estado de Sesión -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Correo Electrónico -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1">
                        Correo electrónico
                    </label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           placeholder="usuario@misvales.com"
                           class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-slate-200 placeholder-slate-500 text-sm transition duration-150 ease-in-out">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1">
                        Contraseña
                    </label>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-slate-200 placeholder-slate-500 text-sm transition duration-150 ease-in-out">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Recordarme y Olvidó Contraseña -->
                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" 
                               type="checkbox" 
                               name="remember" 
                               class="rounded bg-slate-900 border-slate-700 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-slate-800">
                        <span class="ms-2 text-xs text-slate-400">Recordarme</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-xs text-indigo-400 hover:text-indigo-300 transition duration-150 ease-in-out font-medium" 
                           href="{{ route('password.request') }}">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <!-- Botón de Envío -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-800 text-white font-semibold rounded-xl text-sm transition duration-200 shadow-lg shadow-indigo-600/30">
                        Iniciar Sesión
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-guest-layout>