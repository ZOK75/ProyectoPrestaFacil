<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaFácil - Error del Servidor</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 font-sans antialiased selection:bg-rose-500 selection:text-white">
    <div class="max-w-lg w-full text-center space-y-6 bg-slate-900 border border-slate-800 p-8 sm:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
        
        <!-- Glows decorativos de fondo -->
        <div class="absolute -top-24 -left-24 w-56 h-56 bg-rose-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-56 h-56 bg-fuchsia-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Badge de Estado -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30">
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
            Error 500 • Error Interno del Servidor
        </div>

        <!-- Icono de Advertencia -->
        <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
            <div class="w-20 h-20 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex items-center justify-center text-rose-400 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-slate-900 text-rose-400 font-black rounded-full flex items-center justify-center shadow-lg border-2 border-rose-500/30 text-sm">
                !
            </div>
        </div>

        <!-- Título y Mensaje Informativo -->
        <div class="space-y-2.5">
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                ¡Ops! Algo salió mal
            </h1>
            
            @php
                $mensajeSeguro = 'Ha ocurrido un problema inesperado de nuestro lado al procesar tu solicitud. Nuestro equipo técnico ha sido notificado.';
                if (isset($exception) && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && !empty(trim($exception->getMessage()))) {
                    $mensajeSeguro = $exception->getMessage();
                } elseif (isset($message) && !empty(trim($message))) {
                    $mensajeSeguro = $message;
                }
            @endphp

            <p class="text-sm text-slate-300 leading-relaxed max-w-md mx-auto">
                {{ $mensajeSeguro }}
            </p>
        </div>

        <!-- Tarjeta de Causas y Recomendaciones -->
        <div class="bg-slate-950/70 border border-slate-800/90 rounded-2xl p-4 text-left space-y-2 text-xs text-slate-400">
            <span class="font-bold text-slate-200 uppercase tracking-wider block text-[11px]">
                Posibles causas / Recomendaciones:
            </span>
            <ul class="space-y-1.5 list-disc list-inside text-slate-400">
                <li>El sistema está experimentando una carga alta de solicitudes.</li>
                <li>Se ejecutó una acción inesperada que causó un fallo interno en el servidor.</li>
                <li>Por favor, recarga la página o inténtalo de nuevo más tarde.</li>
            </ul>
        </div>

        <!-- Botones de Acción -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:flex-1 py-3 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all duration-200 text-sm flex items-center justify-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Reintentar
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
