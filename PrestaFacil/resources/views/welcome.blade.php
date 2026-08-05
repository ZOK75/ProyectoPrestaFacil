<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Mis Vales') }} - Préstamos y Financiamiento</title>

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
    <body class="antialiased font-sans bg-slate-50 text-slate-800 min-h-screen selection:bg-emerald-500 selection:text-white">
        
        <!-- Header / Navbar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between">
                
                <!-- Logo & Nombre -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 font-bold text-xl">
                        V
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">
                        Mis<span class="text-emerald-600">Vales</span>
                    </span>
                </div>

                <!-- Enlaces de Autenticación -->
                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition duration-200 shadow-md shadow-emerald-600/20">
                                Ir al Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-medium text-sm transition duration-200 border border-slate-300">
                                Iniciar Sesión
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition duration-200 shadow-md shadow-emerald-600/20">
                                    Solicitar Préstamo
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
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Soluciones Financieras Rápidas
                </div>

                <!-- Título principal -->
                <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Tu crédito inmediato con la <span class="text-emerald-600">confianza que necesitas</span>
                </h1>

                <!-- Subtítulo -->
                <p class="text-lg text-slate-600 font-normal leading-relaxed">
                    Gestiona tus préstamos y vales de forma transparente, sin letras chiquitas. Control total para coordinadores, distribuidores y clientes.
                </p>

                <!-- Botones Call to Action -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-base transition duration-200 shadow-lg shadow-emerald-600/25">
                            Acceder a mi Portal
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-base transition duration-200 shadow-lg shadow-emerald-600/25">
                            Ingresar a mi Cuenta
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-base transition duration-200 border border-slate-300">
                                Registrarme
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Tarjeta 1 -->
                <div class="p-6 bg-white border border-slate-200/80 rounded-2xl space-y-3 shadow-sm hover:shadow-md hover:border-emerald-200 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Aprobación Rápida</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Emisión e historial de vales al instante. Procesa préstamos de forma ágil para tus clientes o sucursales.
                    </p>
                </div>

                <!-- Tarjeta 2 -->
                <div class="p-6 bg-white border border-slate-200/80 rounded-2xl space-y-3 shadow-sm hover:shadow-md hover:border-emerald-200 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Seguridad Garantizada</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Validación segura con permisos específicos para Gerentes, Coordinadores, Cajeros y Verificadores.
                    </p>
                </div>

                <!-- Tarjeta 3 -->
                <div class="p-6 bg-white border border-slate-200/80 rounded-2xl space-y-3 shadow-sm hover:shadow-md hover:border-emerald-200 transition duration-200">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Control de Saldos</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Monitoreo en tiempo real de dispersiones, cobros y vales activos en todas las sucursales.
                    </p>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 mt-12">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Mis Vales. Todos los derechos reservados.
            </div>
        </footer>

    </body>
</html>