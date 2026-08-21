<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
        <!-- Tarjeta estilo PrestaFacil -->
        <div class="w-full sm:max-w-md mt-6 px-8 py-8 bg-white shadow-xl overflow-hidden sm:rounded-2xl border border-gray-100 text-center">
            
            <!-- Icono superior -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                </svg>
            </div>

            <!-- Título y Subtítulo -->
            <h2 class="text-xl font-extrabold text-gray-900 mb-1">
                Configuración Inicial 2FA
            </h2>
            <p class="text-xs text-gray-500 mb-4">
                Escanea el código QR con tu app <strong class="text-indigo-600">Google Authenticator</strong> e ingresa el código generado para vincular tu cuenta.
            </p>

            <!-- Errores de validación -->
            @if ($errors->any())
                <div class="mb-4 text-xs text-red-600 bg-red-50 p-3 rounded-xl border border-red-200 text-left">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Código QR -->
            <div class="flex justify-center p-3 bg-white rounded-2xl w-fit mx-auto shadow-md border border-gray-200 mb-5">
                {!! $qrImage !!}
            </div>

            <form method="POST" action="{{ route('2fa.setup.confirm') }}" class="space-y-4">
                @csrf

                <!-- Campo del código -->
                <div class="text-left">
                    <label for="one_time_password" class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Ingresa el código de 6 dígitos
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
                        class="w-full text-center text-2xl font-black tracking-widest py-2.5 px-4 rounded-xl border border-gray-300 bg-indigo-50/30 text-gray-800 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-all duration-200"
                    />
                </div>

                <!-- Botón Activar -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-600/20 hover:shadow-lg transition-all duration-200 text-sm"
                    >
                        Vincular y Continuar
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</x-guest-layout>
