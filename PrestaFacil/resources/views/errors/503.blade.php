<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaFácil - Servicio no Disponible</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 font-sans antialiased">
    <div class="max-w-md w-full text-center space-y-6 bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-16 h-16 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center justify-center mx-auto text-amber-400 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.67 2.67 0 0 0 21 17.25l-5.83-5.83M11.42 15.17l2.496-3.03c.315-.382.72-.676 1.182-.857l5.228-2.046a2.67 2.67 0 0 0-3.327-3.327l-2.046 5.228a2.67 2.67 0 0 0-.857 1.182l-3.03 2.496M11.42 15.17l-4.57 4.57a2.67 2.67 0 0 1-3.774-3.774l4.57-4.57m0 0a6 6 0 1 1 8.486-8.486 6 6 0 0 1-8.486 8.486Z" />
            </svg>
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-black tracking-tight text-white">Servicio Temporalmente No Disponible</h1>
            <p class="text-sm text-slate-400 leading-relaxed">
                {{ $exception->getMessage() ?: 'El sistema se encuentra temporalmente en mantenimiento o el servidor externo está inaccesible. Por favor, reintenta en unos instantes.' }}
            </p>
        </div>

        <div class="pt-4 flex items-center gap-3">
            <button onclick="window.location.reload()" class="flex-1 py-3 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all duration-200 text-sm">
                Reintentar
            </button>
            <a href="{{ url('/') }}" class="flex-1 py-3 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl border border-slate-700 transition-all duration-200 text-sm">
                Inicio
            </a>
        </div>
    </div>
</body>
</html>
