<div class="text-center p-6 bg-white rounded-2xl shadow-md">
    <h3 class="text-lg font-bold text-gray-800 mb-2">Configurar Google Authenticator</h3>
    <p class="text-sm text-gray-600 mb-4">
        Abre tu aplicación Google Authenticator en tu celular y escanea el siguiente código QR:
    </p>

    <!-- AQUÍ SE RENDERIZA EL QR -->
    <div class="flex justify-center mb-6">
        {!! $qrImage !!}
    </div>

    <!-- Formulario para confirmar el primer código y activar el 2FA -->
    <form novalidate method="POST" action="{{ route('2fa.activar') }}">
        @csrf
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Ingresa el código de 6 dígitos de la app para confirmar:
        </label>
        <input type="text" name="one_time_password" maxlength="6" class="border rounded-lg p-2 text-center text-xl tracking-widest mb-4" required>
        
        <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium">
            Activar 2FA
        </button>
    </form>
</div>