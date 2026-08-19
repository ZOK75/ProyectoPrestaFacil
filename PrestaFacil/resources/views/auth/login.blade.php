<x-guest-layout>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
        
        <!-- Tarjeta de Login -->
        <div class="w-full sm:max-w-md mt-6 px-8 py-8 bg-white shadow-xl shadow-slate-200/50 overflow-hidden sm:rounded-2xl border border-slate-200">
            
            <!-- Encabezado / Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 mb-4 border border-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-wide">PrestaFacil</h2>
                <p class="text-sm text-slate-500 mt-1">Ingresa tus credenciales para acceder</p>
            </div>




            <!-- Estado de Sesión -->
            <x-auth-session-status class="mb-4" :status="session('status')" />





            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf











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
                           autofocus 
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
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="w-full px-4 py-3 bg-white border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-slate-800 placeholder-slate-400 text-sm transition duration-150 ease-in-out outline-none">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Google reCAPTCHA v2 Widget -->
                <div class="mt-4 flex flex-col items-center">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                </div>
                @error('g-recaptcha-response')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror


      

                <!-- Botón de Envío -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 text-white font-semibold rounded-xl text-sm transition duration-200 shadow-lg shadow-emerald-600/20">
                        Iniciar Sesión
                    </button>
                </div>
            </form>






        </div>

    </div>
</x-guest-layout>