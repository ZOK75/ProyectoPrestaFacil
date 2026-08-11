<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DistribuidorController;
use App\Http\Controllers\ProductoValeController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\SolicitudClienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GerenteGeneralController;
use App\Http\Controllers\GerenteSucursalController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\CajeroController;
use App\Http\Controllers\AutorizacionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirección inicial según estado de sesión y rol del usuario
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user()->load('rol');
    if ($user->esGerenteGeneral() || $user->esAdministrador()) return redirect()->route('gerente-general.dashboard');
    if ($user->esGerenteSucursal()) return redirect()->route('gerente-sucursal.dashboard');
    if ($user->esDistribuidor()) return redirect()->route('distribuidor.dashboard');
    if ($user->esCajero()) return redirect()->route('cajero.dashboard');
    if ($user->esCoordinador()) return redirect()->route('autorizaciones.index');
    return redirect()->route('producto-vales.index');
});

// Todas las rutas del sistema requieren sesión activa (middleware 'auth')
Route::middleware(['auth'])->group(function () {

    // Dashboard universal: redirige al panel según rol
    Route::get('/dashboard', function () {
        $user = Auth::user()->load('rol');
        if ($user->esGerenteGeneral() || $user->esAdministrador()) return redirect()->route('gerente-general.dashboard');
        if ($user->esGerenteSucursal()) return redirect()->route('gerente-sucursal.dashboard');
        if ($user->esDistribuidor()) return redirect()->route('distribuidor.dashboard');
        if ($user->esCajero()) return redirect()->route('cajero.dashboard');
        if ($user->esCoordinador()) return redirect()->route('autorizaciones.index');
        return redirect()->route('producto-vales.index');
    })->name('dashboard');

    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notificaciones universales
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leer');
    Route::post('/notificaciones/marcar-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.marcar-todas');

    // ──────────────────────────────────────────
    // 0. CENTRO DE LOGS Y AUDITORÍA (Exclusivo Administrador)
    // ──────────────────────────────────────────
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('/logs', [LogViewerController::class, 'index'])->name('logs.index');
    });

    // ──────────────────────────────────────────
    // 1. MÓDULO GERENTE GENERAL Y ADMINISTRADOR
    // ──────────────────────────────────────────
    Route::middleware(['role:gerente_general,administrador'])->group(function () {
        Route::prefix('gerente-general')->group(function () {
            Route::get('/dashboard', [GerenteGeneralController::class, 'index'])
                ->name('gerente-general.dashboard');
        });

        // Configuración General del Sistema (Lectura para Administrador, Edición solo Gerente General)
        Route::get('configuracion-general', [ConfiguracionController::class, 'edit'])->name('configuracion-general.edit');
        Route::put('configuracion-general', [ConfiguracionController::class, 'update'])->name('configuracion-general.update');
    });

    // ──────────────────────────────────────────
    // 2. MÓDULO GERENTE DE SUCURSAL
    // ──────────────────────────────────────────
    Route::middleware(['role:gerente_de_sucursal'])->prefix('gerente-sucursal')->group(function () {
        Route::get('/dashboard', [GerenteSucursalController::class, 'index'])
            ->name('gerente-sucursal.dashboard');
    });

    // ──────────────────────────────────────────
    // 3. MÓDULO DISTRIBUIDOR
    // ──────────────────────────────────────────
    Route::middleware(['role:distribuidor'])->prefix('distribuidor')->group(function () {
        Route::get('/dashboard', [DistribuidorController::class, 'dashboard'])
            ->name('distribuidor.dashboard');
    });

    // ──────────────────────────────────────────
    // 4. MÓDULO CAJERO
    // ──────────────────────────────────────────
    Route::middleware(['role:cajero'])->prefix('cajero')->group(function () {
        Route::get('/dashboard', [CajeroController::class, 'dashboard'])->name('cajero.dashboard');
        
        // Búsqueda de Folio
        Route::get('/buscar-folio', [CajeroController::class, 'buscarFolio'])->name('cajero.buscar-folio');
        
        // Módulo 1: Prevale
        Route::get('/prevale/{prestamo}/verificar', [CajeroController::class, 'verificarDatosPrevale'])->name('cajero.prevale.verificar');
        Route::post('/prevale/{prestamo}/entregar', [CajeroController::class, 'entregarPrevale'])->name('cajero.prevale.entregar');
        Route::match(['get', 'post'], '/prevale/{prestamo}/solicitar-modificacion', [CajeroController::class, 'solicitarModificacionDatos'])->name('cajero.solicitar-modificacion');
        
        // Módulo 2: Vale Digital
        Route::get('/vale/{prestamo}/verificar', [CajeroController::class, 'verificarDatosVale'])->name('cajero.vale.verificar');
        Route::post('/vale/{prestamo}/entregar', [CajeroController::class, 'entregarVale'])->name('cajero.vale.entregar');
        
        // Módulo 3: Abonos y Pagos
        Route::get('/abonos', [CajeroController::class, 'indexAbonos'])->name('cajero.abonos.index');
        Route::post('/abonos/{prestamo}', [CajeroController::class, 'registrarAbono'])->name('cajero.abonos.store');
        
        // Módulo 4: Conciliación
        Route::get('/conciliaciones', [CajeroController::class, 'indexConciliaciones'])->name('cajero.conciliaciones.index');
        Route::post('/conciliaciones', [CajeroController::class, 'solicitarConciliacion'])->name('cajero.conciliaciones.store');
        Route::get('/conciliaciones/{conciliacion}', [CajeroController::class, 'mostrarConciliacion'])->name('cajero.conciliaciones.show');
        
        // Módulo 5: Canje de Puntos
        Route::get('/canje-puntos', [CajeroController::class, 'indexCanje'])->name('cajero.canje-puntos.index');
        Route::post('/canje-puntos', [CajeroController::class, 'realizarCanje'])->name('cajero.canje-puntos.store');
        
        // Notificaciones Cajero
        Route::get('/notificaciones-cajero', [CajeroController::class, 'notificaciones'])->name('cajero.notificaciones');
        Route::post('/notificaciones-cajero/{id}/leer', [CajeroController::class, 'marcarNotificacionLeida'])->name('cajero.notificaciones.leer');
    });

    // ──────────────────────────────────────────
    // 5. AUTORIZACIONES (Coordinador y Administrador)
    // ──────────────────────────────────────────
    Route::middleware(['role:coordinador,administrador'])->prefix('autorizaciones')->group(function () {
        Route::get('/', [AutorizacionController::class, 'index'])->name('autorizaciones.index');
        Route::get('/{solicitud}', [AutorizacionController::class, 'show'])->name('autorizaciones.show');
        Route::post('/{solicitud}/aprobar', [AutorizacionController::class, 'aprobar'])->name('autorizaciones.aprobar');
        Route::post('/{solicitud}/rechazar', [AutorizacionController::class, 'rechazar'])->name('autorizaciones.rechazar');
    });

    // ──────────────────────────────────────────
    // 6. SOLICITUDES DE CLIENTES (Administrador en auditoría)
    // ──────────────────────────────────────────
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('solicitudes-clientes', [SolicitudClienteController::class, 'index'])->name('solicitudes-clientes.index');
        Route::get('solicitudes-clientes/{solicitud}', [SolicitudClienteController::class, 'show'])->name('solicitudes-clientes.show');
        Route::post('solicitudes-clientes/{solicitud}/aprobar', [SolicitudClienteController::class, 'aprobar'])->name('solicitudes-clientes.aprobar');
        Route::post('solicitudes-clientes/{solicitud}/rechazar', [SolicitudClienteController::class, 'rechazar'])->name('solicitudes-clientes.rechazar');
    });

    // ──────────────────────────────────────────
    // 7. GESTIÓN DE USUARIOS (Gerente General, Gerente de Sucursal y Administrador)
    // ──────────────────────────────────────────
    Route::middleware(['role:gerente_general,gerente_de_sucursal,administrador'])->group(function () {
        Route::resource('usuarios', UserController::class);
    });

    // ──────────────────────────────────────────
    // 8. CLIENTES (Distribuidores y Administrador)
    // ──────────────────────────────────────────
    Route::middleware(['role:distribuidor,administrador'])->group(function () {
        Route::resource('clientes', ClienteController::class);
    });

    // ──────────────────────────────────────────
    // 9. PRÉSTAMOS, VALES Y COBRANZA (Distribuidores, Cajeros y Administrador)
    // ──────────────────────────────────────────
    Route::middleware(['role:distribuidor,cajero,administrador'])->group(function () {
        Route::get('prestamos-relacion-pdf', [PrestamoController::class, 'relacionCobranza'])->name('prestamos.relacion-pdf');
        Route::resource('prestamos', PrestamoController::class);
        Route::get('prestamos/{prestamo}/pago', [PrestamoController::class, 'pagoForm'])->name('prestamos.pago');
        Route::post('prestamos/{prestamo}/pago', [PrestamoController::class, 'registrarPago'])->name('prestamos.pago.store');
    });

    // ──────────────────────────────────────────
    // 10. CATÁLOGO DE PRODUCTO VALES (Lectura para todos los logueados)
    // ──────────────────────────────────────────
    Route::resource('producto-vales', ProductoValeController::class);
});

require __DIR__.'/auth.php';
