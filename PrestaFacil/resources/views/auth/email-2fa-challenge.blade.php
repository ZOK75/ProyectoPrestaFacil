<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaFacil - Código por Correo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 w-full max-w-sm text-center">
        
        <!-- Icono Mail -->
        <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="text-xl font-bold text-gray-800 mb-1">Verificación por Correo</h1>
        <p class="text-xs text-gray-500 mb-6">Hemos enviado un código de seguridad a tu correo electrónico registrado.</p>

        @if (session('status'))
            <div class="mb-4 text-xs font-medium text-emerald-600">
                {{ session('status') }}
            </div>
        @endif

        <form novalidate method="POST" action="{{ route('auth.email-2fa.verify') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-xs font-semibold text-gray-600 mb-2 text-left">Código de Verificación</label>
                <input id="code" type="text" name="code" required autofocus placeholder="123456" maxlength="6"
                    class="w-full text-center tracking-widest text-lg font-bold px-3.5 py-2.5 border border-emerald-400 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                @error('code')
                    <span class="text-red-500 text-xs mt-1 block text-left">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm shadow-md shadow-emerald-600/20 transition duration-200">
                Verificar Código
            </button>
        </form>

        <form novalidate method="POST" action="{{ route('auth.email-2fa.resend') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-xs text-emerald-600 hover:underline bg-transparent border-0 cursor-pointer">
                ¿No recibiste el código? Reenviar
            </button>
        </form>

    </div>

</body>
</html>