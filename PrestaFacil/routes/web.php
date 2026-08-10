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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirección inicial según estado de sesión y rol del usuario
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user()->load('rol');
    if ($user->esGerenteGeneral()) return redirect()->route('gerente-general.dashboard');
    if ($user->esGerenteSucursal()) return redirect()->route('gerente-sucursal.dashboard');
    if ($user->esDistribuidor()) return redirect()->route('distribuidor.dashboard');
    if ($user->esCajero()) return redirect()->route('cajero.dashboard');
    if ($user->esCoordinador()) return redirect()->route('autorizaciones.index');
    return redirect()->route('producto-vales.index');
});

// Todas las rutas del sistema requieren sesión activa (middleware 'auth')
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $user = Auth::user()->load('rol');
        if ($user->esGerenteGeneral()) return redirect()->route('gerente-general.dashboard');
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

    // 1. Gerente General Dashboard
    Route::prefix('gerente-general')->group(function () {
        Route::get('/dashboard', [GerenteGeneralController::class, 'index'])
            ->name('gerente-general.dashboard');
    });

    // 2. Gerente Sucursal Dashboard
    Route::prefix('gerente-sucursal')->group(function () {
        Route::get('/dashboard', [GerenteSucursalController::class, 'index'])
            ->name('gerente-sucursal.dashboard');
    });

    // 3. Distribuidor Dashboard
    Route::prefix('distribuidor')->group(function () {
        Route::get('/dashboard', [DistribuidorController::class, 'dashboard'])
            ->name('distribuidor.dashboard');
    });

    // 3.1. Cajero Dashboard y Módulos
    Route::prefix('cajero')->group(function () {
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
        
        // Notificaciones
        Route::get('/notificaciones', [CajeroController::class, 'notificaciones'])->name('cajero.notificaciones');
        Route::post('/notificaciones/{id}/leer', [CajeroController::class, 'marcarNotificacionLeida'])->name('cajero.notificaciones.leer');
    });

    // 3.2. Autorizaciones (Coordinador y Gerentes)
    Route::prefix('autorizaciones')->group(function () {
        Route::get('/', [AutorizacionController::class, 'index'])->name('autorizaciones.index');
        Route::get('/{solicitud}', [AutorizacionController::class, 'show'])->name('autorizaciones.show');
        Route::post('/{solicitud}/aprobar', [AutorizacionController::class, 'aprobar'])->name('autorizaciones.aprobar');
        Route::post('/{solicitud}/rechazar', [AutorizacionController::class, 'rechazar'])->name('autorizaciones.rechazar');
    });

    // 4. Bandeja de Solicitudes y Notificaciones de Clientes (Gerencia)
    Route::get('solicitudes-clientes', [SolicitudClienteController::class, 'index'])->name('solicitudes-clientes.index');
    Route::get('solicitudes-clientes/{solicitud}', [SolicitudClienteController::class, 'show'])->name('solicitudes-clientes.show');
    Route::post('solicitudes-clientes/{solicitud}/aprobar', [SolicitudClienteController::class, 'aprobar'])->name('solicitudes-clientes.aprobar');
    Route::post('solicitudes-clientes/{solicitud}/rechazar', [SolicitudClienteController::class, 'rechazar'])->name('solicitudes-clientes.rechazar');

    // Módulos del Sistema
    Route::resource('producto-vales', ProductoValeController::class);
    Route::resource('clientes', ClienteController::class);

    // Sistema de Préstamos, Prevales/Vales y Cobranza
    Route::get('prestamos-relacion-pdf', [PrestamoController::class, 'relacionCobranza'])->name('prestamos.relacion-pdf');
    Route::resource('prestamos', PrestamoController::class);
    Route::get('prestamos/{prestamo}/pago', [PrestamoController::class, 'pagoForm'])->name('prestamos.pago');
    Route::post('prestamos/{prestamo}/pago', [PrestamoController::class, 'registrarPago'])->name('prestamos.pago.store');

    Route::get('configuracion-general', [ConfiguracionController::class, 'edit'])->name('configuracion-general.edit');
    Route::put('configuracion-general', [ConfiguracionController::class, 'update'])->name('configuracion-general.update');

    Route::resource('usuarios', UserController::class);
});

require __DIR__.'/auth.php';
