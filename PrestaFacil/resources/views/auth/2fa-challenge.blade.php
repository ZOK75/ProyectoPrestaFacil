<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
        <!-- Tarjeta estilo PrestaFacil -->
        <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-xl overflow-hidden sm:rounded-2xl border border-gray-100 text-center">
            
            <!-- Icono superior -->
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>

            <!-- Título y Subtítulo -->
            <h2 class="text-2xl font-bold text-gray-900 mb-1">
                Verificación 2FA
            </h2>
            <p class="text-sm text-gray-500 mb-6">
                Ingresa el código de 6 dígitos de tu app Google Authenticator
            </p>

            <!-- Errores de validación -->
            @if ($errors->any())
                <div class="mb-4 text-sm text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form novalidate method="POST" action="{{ route('2fa.verify') }}" class="space-y-6">
                @csrf

                <!-- Campo del código -->
                <div class="text-left">
                    <label for="one_time_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Código de seguridad
                    </label>
                    <input 
                        id="one_time_password" 
                        type="text" 
                        name="one_time_password" 
                        required 
                        maxlength="6" 
                        autofocus 
                        autocomplete="one-time-code"
                        placeholder="000000"
                        class="w-full text-center text-2xl font-semibold tracking-widest py-3 px-4 rounded-xl border border-gray-200 bg-blue-50/50 text-gray-800 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                    />
                </div>

                <!-- Botón Iniciar Sesión / Verificar -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-md shadow-emerald-600/20 hover:shadow-lg transition-all duration-200"
                    >
                        Verificar Código
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</x-guest-layout>