<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'PrestaFácil - Vales de Préstamo')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased flex flex-col min-h-screen">

    <!-- Header Navigation -->
    <header class="bg-slate-900/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('producto-vales.index') }}" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold bg-gradient-to-r from-white via-slate-200 to-indigo-300 bg-clip-text text-transparent">PrestaFácil</span>
                        <span class="block text-xs text-indigo-400 font-medium tracking-wide">Gestión de Vales</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center space-x-4">
                <nav class="flex items-center space-x-1">
                    @auth
                        @if(Auth::user()->esDistribuidor())
                            <!-- Opciones para Distribuidor -->
                            <a href="{{ route('distribuidor.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('distribuidor.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Mi Panel
                            </a>
                            <a href="{{ route('clientes.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('clientes.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Clientes
                            </a>
                            <a href="{{ route('prestamos.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Préstamos
                            </a>
                        @elseif(Auth::user()->esCajero())
                            <!-- Opciones para Cajero -->
                            <a href="{{ route('cajero.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('cajero.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Caja
                            </a>
                            <a href="{{ route('cajero.buscar-folio') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('cajero.buscar-folio') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Buscar Folio
                            </a>
                            <a href="{{ route('cajero.abonos.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('cajero.abonos.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Abonos
                            </a>
                            <a href="{{ route('cajero.conciliaciones.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('cajero.conciliaciones.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Conciliación
                            </a>
                            <a href="{{ route('cajero.canje-puntos.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('cajero.canje-puntos.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Canje
                            </a>
                        @elseif(Auth::user()->esCoordinador())
                            <!-- Opciones para Coordinador -->
                            <a href="{{ route('autorizaciones.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('autorizaciones.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Autorizaciones
                            </a>
                        @else
                            <!-- Opciones para Gerentes y Administradores -->
                            @if(Auth::user()->esGerenteGeneral())
                                <a href="{{ route('gerente-general.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('gerente-general.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Dashboard
                                </a>
                            @elseif(Auth::user()->esGerenteSucursal())
                                <a href="{{ route('gerente-sucursal.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('gerente-sucursal.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Dashboard
                                </a>
                            @endif

                            <a href="{{ route('producto-vales.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('producto-vales.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Vales
                            </a>
                            <a href="{{ route('usuarios.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('usuarios.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Usuarios
                            </a>
                            @if(Auth::user()->esGerenteGeneral())
                                <a href="{{ route('configuracion-general.edit') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('configuracion-general.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Configuración
                                </a>
                            @endif
                        @endif
                    @endauth
                </nav>

                @auth
                    <!-- Campana de Notificaciones para Distribuidor -->
                    @if(Auth::user()->esDistribuidor())
                        @php
                            $conteoNotifDist = \App\Models\NotificacionCajero::where('user_id', Auth::id())->where('leida', false)->count();
                        @endphp
                        <a href="{{ route('notificaciones.index') }}" 
                           class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition border border-transparent hover:border-slate-700" 
                           title="Notificaciones y Avisos de Cobranza">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($conteoNotifDist > 0)
                                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-500 text-[10px] font-black text-white shadow-lg shadow-indigo-500/40 animate-bounce">
                                    {{ $conteoNotifDist > 9 ? '9+' : $conteoNotifDist }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <!-- Campana de Notificaciones de Solicitudes (Gerentes) -->
                    @if(Auth::user()->esGerenteGeneral() || Auth::user()->esGerenteSucursal())
                        @php
                            $conteoNotif = Auth::user()->conteoSolicitudesPendientes();
                        @endphp
                        <a href="{{ route('solicitudes-clientes.index') }}" 
                           class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition border border-transparent hover:border-slate-700" 
                           title="Bandeja de Solicitudes y Notificaciones">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($conteoNotif > 0)
                                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-black text-slate-950 shadow-lg shadow-amber-500/40 animate-bounce">
                                    {{ $conteoNotif > 9 ? '9+' : $conteoNotif }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <!-- Campana de Notificaciones (Cajeros y Coordinadores) -->
                    @if(Auth::user()->esCajero() || Auth::user()->esCoordinador())
                        @php
                            $conteoNotifCajero = Auth::user()->conteoNotificacionesSinLeer();
                        @endphp
                        <a href="{{ route('cajero.notificaciones') }}" 
                           class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition border border-transparent hover:border-slate-700" 
                           title="Notificaciones">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($conteoNotifCajero > 0)
                                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-lg shadow-rose-500/40 animate-bounce">
                                    {{ $conteoNotifCajero > 9 ? '9+' : $conteoNotifCajero }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <!-- Menu Desplegable de Usuario -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false" type="button" class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-slate-800 focus:outline-none transition border border-slate-700/50">
                            <div class="w-7 h-7 rounded-full bg-indigo-600/40 border border-indigo-400/50 flex items-center justify-center text-indigo-200 font-bold text-xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="max-w-[100px] truncate hidden sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-1 focus:outline-none"
                             style="display: none;">
                            
                            <div class="px-4 py-2.5 border-b border-slate-800">
                                <p class="text-xs text-slate-400">Conectado como</p>
                                <p class="text-xs font-semibold text-slate-200 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] text-indigo-400 font-medium">{{ Auth::user()->rol?->nombre ?? 'Usuario' }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Perfil
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-400 hover:bg-slate-800 hover:text-rose-300 transition border-t border-slate-800/60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow w-full mx-auto px-3 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="max-w-md mx-auto mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-between shadow-lg shadow-emerald-950/20">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400/60 hover:text-emerald-400 text-lg leading-none">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-md mx-auto mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-between shadow-lg shadow-rose-950/20">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-rose-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-400/60 hover:text-rose-400 text-lg leading-none">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-4 mt-auto">
        <div class="max-w-md mx-auto px-4 text-center text-[11px] text-slate-500">
            PrestaFácil &copy; {{ date('Y') }} - Sistema de Vales Móvil
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
