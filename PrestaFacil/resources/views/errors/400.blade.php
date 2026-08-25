<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaFácil - Solicitud Inválida</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 font-sans antialiased selection:bg-rose-500 selection:text-white">
    <div class="max-w-lg w-full text-center space-y-6 bg-slate-900 border border-slate-800 p-8 sm:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
        
        <!-- Glows decorativos de fondo -->
        <div class="absolute -top-24 -left-24 w-56 h-56 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-56 h-56 bg-orange-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Badge de Estado -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Error 400 • Solicitud Inválida
        </div>

        <!-- Icono de Advertencia -->
        <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
            <div class="w-20 h-20 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center justify-center text-amber-400 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- Título y Mensaje Informativo -->
        <div class="space-y-2.5">
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Solicitud Mal Formulada
            </h1>
            <p class="text-sm text-slate-300 leading-relaxed max-w-md mx-auto">
                {{ $exception->getMessage() ?: 'El servidor no pudo entender la solicitud debido a una sintaxis incorrecta o parámetros inválidos.' }}
            </p>
        </div>

        <!-- Tarjeta de Causas y Recomendaciones -->
        <div class="bg-slate-950/70 border border-slate-800/90 rounded-2xl p-4 text-left space-y-2 text-xs text-slate-400">
            <span class="font-bold text-slate-200 uppercase tracking-wider block text-[11px]">
                Posibles causas:
            </span>
            <ul class="space-y-1.5 list-disc list-inside text-slate-400">
                <li>El enlace o los datos del formulario enviados contienen errores o están incompletos.</li>
                <li>Se intentó acceder a una función enviando un formato de datos no compatible.</li>
                <li>Tu navegador envió una solicitud que el servidor no permite procesar.</li>
            </ul>
        </div>

        <!-- Botones de Acción -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
            <button onclick="window.history.back()" class="w-full sm:flex-1 py-3 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all duration-200 text-sm flex items-center justify-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Regresar
            </button>

            <a href="{{ url('/') }}" class="w-full sm:flex-1 py-3 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl border border-slate-700/80 transition-all duration-200 text-sm flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Ir al Inicio
            </a>
        </div>

        @if(config('app.debug') && isset($exception))
            <details class="text-left pt-2 border-t border-slate-800/80">
                <summary class="text-[11px] font-mono text-amber-400 cursor-pointer hover:underline">
                    Ver detalle técnico del error (Debug)
                </summary>
                <div class="mt-2 p-3 rounded-xl bg-black/60 border border-slate-800 text-[11px] font-mono text-rose-300 break-all space-y-1">
                    <p><strong>Clase:</strong> {{ get_class($exception) }}</p>
                    <p><strong>Mensaje:</strong> {{ $exception->getMessage() }}</p>
                </div>
            </details>
        @endif

    </div>
</body>
</html>
