<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaFácil - Base de Datos no Disponible</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 font-sans antialiased selection:bg-rose-500 selection:text-white">
    <div class="max-w-lg w-full text-center space-y-6 bg-slate-900 border border-slate-800 p-8 sm:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
        
        <!-- Glows decorativos de fondo -->
        <div class="absolute -top-24 -left-24 w-56 h-56 bg-rose-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-56 h-56 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Badge de Estado -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30">
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
            Error 503 • Servidor No Responde
        </div>

        <!-- Icono de Servidor / Base de Datos con Alerta -->
        <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
            <div class="w-20 h-20 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex items-center justify-center text-rose-400 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </div>
            <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-amber-500 text-slate-950 font-black rounded-full flex items-center justify-center shadow-lg border-2 border-slate-900 text-sm">
                !
            </div>
        </div>

        <!-- Título y Mensaje Informativo -->
        <div class="space-y-2.5">
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Sin Conexión con la Base de Datos
            </h1>
            <p class="text-sm text-slate-300 leading-relaxed max-w-md mx-auto">
                No pudimos establecer comunicación con el servidor remoto de la base de datos.
            </p>
        </div>

        <!-- Tarjeta de Causas y Recomendaciones -->
        <div class="bg-slate-950/70 border border-slate-800/90 rounded-2xl p-4 text-left space-y-2 text-xs text-slate-400">
            <span class="font-bold text-slate-200 uppercase tracking-wider block text-[11px]">
                Posibles causas:
            </span>
            <ul class="space-y-1.5 list-disc list-inside text-slate-400">
                <li>El servidor de base de datos externo está temporalmente apagado o reiniciándose.</li>
                <li>Tiempo de espera agotado (timeout) por interrupciones en la red o VPN.</li>
                <li>El firewall o proveedor de hosting está bloqueando el puerto de conexión.</li>
            </ul>
        </div>

        <!-- Botones de Acción -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:flex-1 py-3 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all duration-200 text-sm flex items-center justify-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Reintentar Conexión
            </button>

            <a href="{{ url('/') }}" class="w-full sm:flex-1 py-3 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl border border-slate-700/80 transition-all duration-200 text-sm flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Ir al Inicio
            </a>
        </div>

    </div>
</body>
</html>
