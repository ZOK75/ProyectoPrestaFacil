<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'PrestaFácil - Vales de Préstamo')</title>
    
    <!-- Favicon PrestaFácil -->
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">
    
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
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc !important; color: #1e293b !important; }
        
        /* Tema Global Limpio: Blanco, Gris Slate Claro y Verde Emerald (Estilo Login) */
        .bg-slate-950, .bg-slate-900, .bg-slate-900\/90, .bg-slate-900\/60, .bg-slate-950\/80, .bg-slate-950\/70, .bg-slate-950\/60, .bg-slate-950\/40 {
            background-color: #ffffff !important;
        }
        .bg-slate-800, .bg-slate-800\/60, .bg-slate-800\/40 {
            background-color: #f1f5f9 !important;
        }
        
        /* Botones Primarios y Badges Emerald */
        .bg-indigo-600, .bg-indigo-500, .bg-emerald-600, .bg-emerald-500 {
            background-color: #059669 !important;
            color: #ffffff !important;
        }
        .bg-indigo-600\/20, .bg-indigo-500\/10, .bg-indigo-500\/20, .bg-emerald-500\/10, .bg-emerald-500\/20 {
            background-color: #ecfdf5 !important;
            color: #047857 !important;
        }
        
        .hover\:bg-indigo-500:hover, .hover\:bg-indigo-600:hover, .hover\:bg-indigo-700:hover, .hover\:bg-emerald-700:hover {
            background-color: #047857 !important;
            color: #ffffff !important;
        }
        .hover\:bg-slate-800:hover, .hover\:bg-slate-700:hover {
            background-color: #e2e8f0 !important;
        }
        
        /* Bordes sutiles estilo login */
        .border-slate-800, .border-slate-800\/80, .border-slate-800\/60, .border-slate-700, .border-slate-700\/50, .border-indigo-500\/30, .border-indigo-500\/20, .border-indigo-400\/50 {
            border-color: #e2e8f0 !important;
        }
        
        /* Normalización Estricta de Textos: Blanco o Negro */
        .text-white, .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-400, .text-slate-500, .text-slate-600, .text-indigo-200, .text-indigo-300, .text-indigo-400, .text-indigo-500, .text-amber-200, .text-amber-300, .text-amber-400, .text-amber-500, .text-yellow-300, .text-yellow-400, .text-emerald-300, .text-emerald-400, .text-violet-300, .text-purple-300 {
            color: #0f172a !important;
        }

        /* Excepciones de texto Blanco ÚNICAMENTE para botones verdes o oscuros */
        button.bg-emerald-600, button.bg-emerald-700, button.bg-indigo-600, a.bg-emerald-600, a.bg-emerald-700, a.bg-indigo-600, .bg-emerald-600, .bg-emerald-700, .bg-emerald-800, button[type="submit"].bg-emerald-600, button[type="submit"].bg-indigo-600, .notification-badge-dynamic {
            color: #ffffff !important;
        }
        button.bg-emerald-600 *, button.bg-emerald-700 *, a.bg-emerald-600 *, a.bg-emerald-700 * {
            color: #ffffff !important;
        }

        /* Quitar gradientes oscuros en favor de blanco limpio */
        .bg-gradient-to-r, .bg-gradient-to-tr, .bg-gradient-to-br, .bg-gradient-to-b {
            background-image: none !important;
            background-color: #ffffff !important;
        }
        .bg-clip-text {
            -webkit-background-clip: border-box !important;
            background-clip: border-box !important;
            color: #0f172a !important;
        }
        
        /* Tarjetas con sombras suaves como el Login */
        .shadow-xl, .shadow-2xl, .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        /* Inputs, Selects y Textareas */
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="date"], input[type="time"], select, textarea {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
            color-scheme: light !important;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #10b981 !important;
            outline: 2px solid rgba(16, 185, 129, 0.2) !important;
        }

        /* Contenedores de fondo claro */
        .bg-indigo-950, .bg-indigo-950\/70, .bg-indigo-950\/40, .bg-indigo-950\/20, .bg-indigo-900, .bg-purple-950, .bg-violet-950, .bg-amber-950, .bg-amber-950\/70, .bg-slate-900\/80, .bg-slate-950 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
        }

        /* Badges e indicadores suaves estilo Login */
        .bg-amber-500\/20, .bg-amber-500\/10, .bg-amber-950\/70, .bg-indigo-500\/20, .bg-indigo-500\/10, .bg-emerald-500\/20 {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .border-amber-500\/30, .border-amber-500\/20, .border-amber-500\/40, .border-amber-900\/30, .border-indigo-500\/40, .border-indigo-500\/30 {
            border-color: #cbd5e1 !important;
        }

        /* Filas seleccionadas o al hacer hover (Verde claro muy suave para excelente contraste) */
        tr:hover, tr.bg-slate-800\/40:hover, tr:focus, tr.selected, .hover\:bg-slate-800\/40:hover, .hover\:bg-slate-800\/60:hover, .hover\:bg-slate-800:hover, .hover\:bg-slate-700:hover {
            background-color: #ecfdf5 !important;
        }

        /* Insignias de Roles y Categorías en tonos verde claro sutiles con texto oscuro nítido */
        .bg-violet-500\/10, .bg-blue-500\/10, .bg-slate-500\/10, .bg-emerald-500\/10, .bg-amber-500\/10, .bg-cyan-500\/10, .bg-indigo-500\/10, .bg-rose-500\/10 {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }

        /* Botón de Simulación de Corte Verde Emerald */
        .from-amber-500, .to-orange-600, .bg-gradient-to-r.from-amber-500.to-orange-600 {
            background: #059669 !important;
            color: #ffffff !important;
        }
        .from-amber-500:hover, .to-orange-600:hover {
            background: #047857 !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body x-data="{ mobileMenuOpen: false }" class="h-full bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen">

    <!-- Header Navigation Superior -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
            
            <!-- Botón Hamburguesa y Marca -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
                
                @auth
                    <!-- Botón Hamburguesa (Móviles) -->
                    <button @click="mobileMenuOpen = true" 
                            type="button" 
                            class="md:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition border border-slate-800/80 active:scale-95"
                            aria-label="Abrir menú de módulos">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                @endauth

                <!-- Logotipo / Nombre -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-600/20 group-hover:scale-105 transition-transform shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-base sm:text-lg font-bold text-slate-900 leading-none block">PrestaFácil</span>
                        <span class="text-[10px] sm:text-xs text-emerald-600 font-medium tracking-wide block">Gestión de Vales</span>
                    </div>
                </a>
            </div>

            <!-- Navegación de Escritorio (visible en pantallas medianas y grandes: md+) -->
            <div class="hidden md:flex items-center space-x-4">
                <nav class="flex items-center space-x-1">
                    @auth
                        @if(Auth::user()->esDistribuidor())
                            <!-- Opciones para Distribuidor Desktop -->
                            <a href="{{ route('distribuidor.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('distribuidor.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Mi Panel
                            </a>
                            <a href="{{ route('clientes.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('clientes.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Clientes
                            </a>
                            <a href="{{ route('prestamos.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Préstamos
                            </a>
                            <a href="{{ route('producto-vales.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('producto-vales.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Catálogo Vales
                            </a>
                        @elseif(Auth::user()->esCajero())
                            <!-- Opciones para Cajero Desktop -->
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
                            <!-- Opciones para Coordinador Desktop y Tablet -->
                            <a href="{{ route('coordinador.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('coordinador.dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Inicio
                            </a>
                            <a href="{{ route('coordinador.prestamos') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('coordinador.prestamos') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Préstamos
                            </a>
                        @elseif(Auth::user()->esVerificador())
                            <!-- Verificador no tiene enlaces extra en el header -->
                        @else
                            <!-- Opciones para Gerentes y Administrador Desktop -->
                            @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
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

                            <!-- Módulos de Auditoría (Exclusivos para Administrador) -->
                            @if(Auth::user()->esAdministrador())
                                <a href="{{ route('clientes.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('clientes.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Clientes
                                </a>

                                <a href="{{ route('prestamos.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Préstamos
                                </a>

                                <a href="{{ route('solicitudes-clientes.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('solicitudes-clientes.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Solicitudes
                                </a>

                                <a href="{{ route('autorizaciones.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('autorizaciones.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Autorizaciones
                                </a>
                            @endif

                            @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
                                <a href="{{ route('configuracion-general.edit') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('configuracion-general.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Configuración
                                </a>
                            @endif

                            @if(Auth::user()->esAdministrador())
                                <!-- Módulo de Logs exclusivo para Administrador -->
                                <a href="{{ route('logs.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1 {{ request()->routeIs('logs.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Logs
                                </a>
                            @endif
                        @endif
                    @endauth
                </nav>
            </div>

            <!-- Controles de la Derecha: Campanas y Perfil -->
            <div class="flex items-center space-x-2 sm:space-x-3">
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
                            <span class="notification-badge-dynamic absolute -top-1 -right-1 flex h-4.5 w-4.5 min-w-[18px] min-h-[18px] items-center justify-center rounded-full bg-indigo-500 text-[10px] font-black text-white shadow-lg shadow-indigo-500/40 animate-bounce" style="{{ $conteoNotifDist > 0 ? '' : 'display: none;' }}">
                                {{ $conteoNotifDist > 9 ? '9+' : $conteoNotifDist }}
                            </span>
                        </a>
                    @endif

                    <!-- Campana de Notificaciones de Solicitudes (Exclusivo para Administrador) -->
                    @if(Auth::user()->esAdministrador())
                        @php
                            $conteoNotif = Auth::user()->conteoSolicitudesPendientes();
                        @endphp
                        <a href="{{ route('solicitudes-clientes.index') }}" 
                           class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition border border-transparent hover:border-slate-700" 
                           title="Bandeja de Solicitudes y Notificaciones">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="notification-badge-dynamic absolute -top-1 -right-1 flex h-4.5 w-4.5 min-w-[18px] min-h-[18px] items-center justify-center rounded-full bg-amber-500 text-[10px] font-black text-slate-950 shadow-lg shadow-amber-500/40 animate-bounce" style="{{ $conteoNotif > 0 ? '' : 'display: none;' }}">
                                {{ $conteoNotif > 9 ? '9+' : $conteoNotif }}
                            </span>
                        </a>
                    @endif

                    <!-- Campana de Notificaciones (Cajeros, Coordinadores, Verificadores y Gerentes) -->
                    @if(Auth::user()->esCajero() || Auth::user()->esCoordinador() || Auth::user()->esVerificador() || Auth::user()->esGerenteGeneral() || Auth::user()->esGerenteSucursal())
                        @php
                            $conteoNotifUsuario = Auth::user()->conteoNotificacionesSinLeer();
                            $rutaCampana = Auth::user()->esCajero() ? route('cajero.notificaciones') : route('notificaciones.index');
                        @endphp
                        <a href="{{ $rutaCampana }}" 
                           class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition border border-transparent hover:border-slate-700" 
                           title="Notificaciones">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="notification-badge-dynamic absolute -top-1 -right-1 flex h-4.5 w-4.5 min-w-[18px] min-h-[18px] items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-lg shadow-rose-500/40 animate-bounce" style="{{ $conteoNotifUsuario > 0 ? '' : 'display: none;' }}">
                                {{ $conteoNotifUsuario > 9 ? '9+' : $conteoNotifUsuario }}
                            </span>
                        </a>
                    @endif

                    <!-- Menu Desplegable de Usuario Desktop -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false" type="button" class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1.5 text-sm font-medium text-slate-800 hover:text-slate-900 rounded-xl bg-slate-100 hover:bg-slate-200 focus:outline-none transition border border-slate-200">
                            <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="max-w-[85px] sm:max-w-[120px] truncate text-xs sm:text-sm font-semibold hidden min-[400px]:inline text-slate-800">{{ Auth::user()->name }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                             class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-2xl bg-white border border-slate-200 shadow-2xl py-1.5 focus:outline-none"
                             style="display: none;">
                            
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <p class="text-[11px] text-slate-500">Conectado como</p>
                                <p class="text-xs font-bold text-slate-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] text-emerald-600 font-semibold uppercase mt-0.5">{{ Auth::user()->rol?->nombre ?? 'Usuario' }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Mi Perfil
                            </a>

                            <form novalidate method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-400 hover:bg-slate-800 hover:text-rose-300 transition border-t border-slate-800/60 mt-1">
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

    <!-- MENÚ LATERAL DESPLEGABLE (DRAWER MÓVIL) -->
    @auth
        <!-- Fondo Oscuro de Fondo (Overlay) -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm md:hidden"
             style="display: none;">
        </div>

        <!-- Panel Lateral Deslizable (Sidebar Drawer) -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 w-72 sm:w-80 bg-slate-900 border-r border-slate-800 shadow-2xl flex flex-col md:hidden"
             style="display: none;">
            
            <!-- Encabezado del Menú Lateral -->
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-extrabold text-white leading-none block">PrestaFácil</span>
                        <span class="text-[10px] text-indigo-400 font-medium">Menú de Módulos</span>
                    </div>
                </div>

                <!-- Botón de Cerrar (X) -->
                <button @click="mobileMenuOpen = false" 
                        type="button" 
                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        aria-label="Cerrar menú">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Ficha de Usuario Activo -->
            <div class="p-4 border-b border-slate-800/80 bg-slate-950/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-200 font-black text-sm shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-white truncate leading-tight">{{ Auth::user()->name }}</p>
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            {{ Auth::user()->rol?->nombre ?? 'Usuario' }}
                        </span>
                        @if(Auth::user()->sucursal)
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ Auth::user()->sucursal->nombre }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Lista Scrollable de Módulos -->
            <div class="flex-1 overflow-y-auto p-3 space-y-1.5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pt-2 pb-1">Módulos del Sistema</p>

                @if(Auth::user()->esDistribuidor())
                    <!-- 1. Mi Panel -->
                    <a href="{{ route('distribuidor.dashboard') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('distribuidor.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Mi Panel Principal</span>
                    </a>

                    <!-- 2. Clientes -->
                    <a href="{{ route('clientes.index') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('clientes.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Cartera de Clientes</span>
                    </a>

                    <!-- 3. Préstamos -->
                    <a href="{{ route('prestamos.index') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                        </svg>
                        <span>Préstamos y Vales</span>
                    </a>

                    <!-- 4. Catálogo de Vales -->
                    <a href="{{ route('producto-vales.index') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('producto-vales.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>Catálogo de Vales</span>
                    </a>

                    <!-- 5. Relación de Cobranza PDF -->
                    <a href="{{ route('prestamos.relacion-pdf') }}" 
                       target="_blank"
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-300 hover:text-white hover:bg-rose-950/30 border border-rose-500/20 transition">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Descargar Relación PDF</span>
                    </a>

                    <!-- 6. Notificaciones -->
                    @php
                        $conteoNotifDist = \App\Models\NotificacionCajero::where('user_id', Auth::id())->where('leida', false)->count();
                    @endphp
                    <a href="{{ route('notificaciones.index') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('notificaciones.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Avisos de Cobranza</span>
                        </div>
                        @if($conteoNotifDist > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-500 text-white">
                                {{ $conteoNotifDist }}
                            </span>
                        @endif
                    </a>

                @elseif(Auth::user()->esCajero())
                    <!-- Opciones de Cajero en Drawer -->
                    <a href="{{ route('cajero.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('cajero.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Panel de Caja</span>
                    </a>
                    <a href="{{ route('cajero.buscar-folio') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('cajero.buscar-folio') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Buscar por Folio</span>
                    </a>
                    <a href="{{ route('cajero.abonos.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('cajero.abonos.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Cobro de Abonos</span>
                    </a>
                    <a href="{{ route('cajero.conciliaciones.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('cajero.conciliaciones.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Conciliación Diaria</span>
                    </a>
                    <a href="{{ route('cajero.canje-puntos.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('cajero.canje-puntos.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        <span>Canje de Puntos</span>
                    </a>
                    <a href="{{ route('prestamos.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                        <span>Préstamos</span>
                    </a>
                    <a href="{{ route('producto-vales.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('producto-vales.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span>Catálogo de Vales</span>
                    </a>

                @elseif(Auth::user()->esCoordinador())
                    <!-- Opciones de Coordinador en Drawer -->
                    <a href="{{ route('coordinador.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('coordinador.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Inicio (Dashboard)</span>
                    </a>
                    <a href="{{ route('coordinador.prestamos') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('coordinador.prestamos') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                        <span>Préstamos</span>
                    </a>
                    <a href="{{ route('coordinador.solicitudes.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('coordinador.solicitudes.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Solicitudes de Distribuidora</span>
                    </a>
                    <a href="{{ route('autorizaciones.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('autorizaciones.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Bandeja de Autorizaciones</span>
                    </a>

                @elseif(Auth::user()->esVerificador())
                    <!-- Opciones de Verificador en Drawer -->
                    <a href="{{ route('verificador.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('verificador.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Bandeja de Evaluaciones</span>
                    </a>

                @else
                    <!-- Opciones de Gerentes y Administrador en Drawer -->
                    @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
                        <a href="{{ route('gerente-general.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('gerente-general.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Dashboard Ejecutivo</span>
                        </a>
                    @elseif(Auth::user()->esGerenteSucursal())
                        <a href="{{ route('gerente-sucursal.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('gerente-sucursal.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Dashboard Sucursal</span>
                        </a>
                    @endif

                    <a href="{{ route('producto-vales.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('producto-vales.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span>Catálogo de Vales</span>
                    </a>

                    <a href="{{ route('usuarios.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('usuarios.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Gestión de Usuarios</span>
                    </a>

                    @php
                        $conteoNotifGerente = Auth::user()->conteoNotificacionesSinLeer();
                    @endphp
                    <a href="{{ route('notificaciones.index') }}" 
                       @click="mobileMenuOpen = false" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('notificaciones.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Notificaciones</span>
                        </div>
                        @if($conteoNotifGerente > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white">
                                {{ $conteoNotifGerente > 9 ? '9+' : $conteoNotifGerente }}
                            </span>
                        @endif
                    </a>

                    @if(Auth::user()->esAdministrador())
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pt-3 pb-1">Auditoría y Supervisión</p>

                        <a href="{{ route('clientes.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('clientes.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Clientes (Auditoría)</span>
                        </a>

                        <a href="{{ route('prestamos.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('prestamos.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                            <span>Préstamos (Auditoría)</span>
                        </a>

                        <a href="{{ route('solicitudes-clientes.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('solicitudes-clientes.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Solicitudes Pendientes</span>
                        </a>

                        <a href="{{ route('autorizaciones.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('autorizaciones.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span>Autorizaciones (Lectura)</span>
                        </a>
                    @endif

                    @if(Auth::user()->esGerenteGeneral() || Auth::user()->esAdministrador())
                        <a href="{{ route('configuracion-general.edit') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('configuracion-general.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Configuración del Sistema</span>
                        </a>
                    @endif

                    @if(Auth::user()->esAdministrador())
                        <a href="{{ route('logs.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('logs.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Visor de Logs</span>
                        </a>
                    @endif
                @endif

                <div class="pt-3 mt-2 border-t border-slate-800/80">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pb-1">Cuenta</p>
                    <a href="{{ route('profile.edit') }}" 
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Mi Perfil de Usuario</span>
                    </a>
                </div>
            </div>

            <!-- Botón de Cerrar Sesión al Fondo del Drawer -->
            <div class="p-3 border-t border-slate-800 bg-slate-950/40">
                <form novalidate method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>

        </div>
    @endauth

    <!-- Main Content Container -->
    <main class="flex-grow w-full mx-auto px-3 sm:px-6 lg:px-8 py-5">
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

    @auth
        <!-- Toast Notifications Container en Tiempo Real -->
        <div x-data="realtimeNotificationApp()" x-init="init()" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none px-3 sm:px-0">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 translate-x-4 scale-95"
                     class="pointer-events-auto bg-white border border-emerald-500/30 rounded-2xl p-4 shadow-2xl flex items-start gap-3.5 text-left transition relative overflow-hidden group">
                    
                    <!-- Borde de progreso / tiempo -->
                    <div class="absolute bottom-0 left-0 h-1 bg-emerald-500 w-full"></div>

                    <!-- Icono de Notificación -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>

                    <!-- Contenido -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-[10px] font-black uppercase text-emerald-700 tracking-wider">Nueva Notificación</span>
                            <button @click="dismiss(toast.id)" class="text-slate-400 hover:text-slate-700 transition text-xs leading-none p-1 -mr-1">
                                &times;
                            </button>
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 truncate" x-text="toast.titulo"></h4>
                        <p class="text-[11px] text-slate-700 leading-tight mt-0.5 line-clamp-2" x-text="toast.mensaje"></p>
                        
                        <template x-if="toast.url">
                            <div class="mt-2.5">
                                <a :href="toast.url" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold shadow transition">
                                    <span>Ver Detalle</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('realtimeNotificationApp', () => ({
                    toasts: [],
                    knownIds: new Set(),
                    isFirstLoad: true,
                    unreadCount: {{ (Auth::user()->esAdministrador() ? Auth::user()->conteoSolicitudesPendientes() : Auth::user()->conteoNotificacionesSinLeer()) ?? 0 }},

                    init() {
                        this.poll();
                        setInterval(() => this.poll(), 3500);

                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState === 'visible') {
                                this.poll();
                            }
                        });
                    },

                    async poll() {
                        try {
                            const response = await fetch('{{ route("notificaciones.live-poll") }}', {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            if (!response.ok) return;
                            const data = await response.json();

                            this.unreadCount = data.unread_count || 0;
                            this.updateBadges(this.unreadCount);

                            if (data.notifications && Array.isArray(data.notifications)) {
                                if (this.isFirstLoad) {
                                    data.notifications.forEach(n => this.knownIds.add(n.id));
                                    this.isFirstLoad = false;
                                    return;
                                }

                                data.notifications.forEach(notif => {
                                    if (!this.knownIds.has(notif.id)) {
                                        this.knownIds.add(notif.id);
                                        if (!notif.leida) {
                                            this.showToast(notif);
                                            this.playSound();
                                            window.dispatchEvent(new CustomEvent('live-new-notification', { detail: notif }));
                                        }
                                    }
                                });
                            }
                        } catch (e) {}
                    },

                    showToast(notif) {
                        const toast = {
                            id: notif.id,
                            titulo: notif.titulo || 'Aviso del Sistema',
                            mensaje: notif.mensaje || '',
                            url: notif.url || null,
                            visible: true
                        };
                        this.toasts.unshift(toast);
                        if (this.toasts.length > 4) {
                            this.toasts.pop();
                        }

                        setTimeout(() => {
                            this.dismiss(toast.id);
                        }, 6500);
                    },

                    dismiss(id) {
                        const index = this.toasts.findIndex(t => t.id === id);
                        if (index !== -1) {
                            this.toasts[index].visible = false;
                            setTimeout(() => {
                                this.toasts = this.toasts.filter(t => t.id !== id);
                            }, 300);
                        }
                    },

                    playSound() {
                        try {
                            const AudioContext = window.AudioContext || window.webkitAudioContext;
                            if (!AudioContext) return;
                            const ctx = new AudioContext();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
                            gain.gain.setValueAtTime(0.06, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.3);
                        } catch (err) {}
                    },

                    updateBadges(count) {
                        const badges = document.querySelectorAll('.notification-badge-dynamic');
                        badges.forEach(badge => {
                            if (count > 0) {
                                badge.textContent = count > 9 ? '9+' : count;
                                badge.style.display = 'flex';
                                badge.classList.remove('hidden');
                            } else {
                                badge.style.display = 'none';
                                badge.classList.add('hidden');
                            }
                        });
                    }
                }));
            });
        </script>
    @endauth

    @stack('scripts')
</body>
</html>
