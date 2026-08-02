<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Mis Vales') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="antialiased font-sans bg-slate-950 text-slate-100 min-h-screen selection:bg-indigo-500 selection:text-white">
        
        <!-- Header / Navbar -->
        <header class="max-w-7xl mx-auto px-6 lg:px-8 pt-6">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-6">
                
                <!-- Logo & Nombre -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 font-bold text-xl">
                        V
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">
                        Mis<span class="text-indigo-400">Vales</span>
                    </span>
                </div>

                <!-- Enlaces de Autenticación -->
                @if (Route::has('login'))
                    <nav class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition duration-200 shadow-md shadow-indigo-600/30">
                                Ir al Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-sm transition duration-200 border border-slate-700">
                                Iniciar Sesión
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition duration-200 shadow-md shadow-indigo-600/30">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <!-- Main Hero Section -->
        <main class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-24">
            
            <div class="text-center max-w-3xl mx-auto space-y-6">
                <!-- Badge superior -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    Plataforma Operativa
                </div>

                <!-- Título principal -->
                <h1 class="text-4xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                    Gestión inteligente y control total de <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400">tus vales</span>
                </h1>

                <!-- Subtítulo -->
                <p class="text-lg text-slate-400 font-normal leading-relaxed">
                    Administra coordinadores, sucursales, cajeros y verificadores desde un solo portal centralizado, rápido y seguro.
                </p>

                <!-- Botones Call to Action -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-base transition duration-200 shadow-xl shadow-indigo-600/30">
                            Acceder al Sistema
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-base transition duration-200 shadow-xl shadow-indigo-600/30">
                            Ingresar a mi Cuenta
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Tarjeta 1 -->
                <div class="p-6 bg-slate-900/60 border border-slate-800 rounded-2xl space-y-3 hover:border-slate-700 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Seguridad Multi-rol</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Acceso restringido y personalizado para Gerentes, Coordinadores, Cajeros y Verificadores.
                    </p>
                </div>

                <!-- Tarjeta 2 -->
                <div class="p-6 bg-slate-900/60 border border-slate-800 rounded-2xl space-y-3 hover:border-slate-700 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Monitoreo en Tiempo Real</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Revisa la emisión, validación y estado de los vales activos en todas las sucursales al instante.
                    </p>
                </div>

                <!-- Tarjeta 3 -->
                <div class="p-6 bg-slate-900/60 border border-slate-800 rounded-2xl space-y-3 hover:border-slate-700 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Operación Ágil</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Procesos automatizados para agilizar la dispersión y cobro de vales sin contratiempos.
                    </p>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto px-6 lg:px-8 py-8 border-t border-slate-800/80 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Mis Vales. Todos los derechos reservados.
        </footer>

    </body>
</html>