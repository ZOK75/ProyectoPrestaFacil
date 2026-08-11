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
    if ($user->esCoordinador()) return redirect()->route('coordinador.dashboard');
    return redirect()->route('producto-vales.index');
});

// Todas las rutas del sistema requieren sesión activa (middleware 'auth')
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $user = Auth::user()->load('rol');
        if ($user->esGerenteGeneral()) return redirect()->route('gerente-general.dashboard');
        if ($user->esGerenteSucursal()) return redirect()->route('gerente-sucursal.dashboard');
        if ($user->esDistribuidor()) return redirect()->route('distribuidor.dashboard');
        if ($user->esCoordinador()) return redirect()->route('coordinador.dashboard');
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

    // 4. Coordinador Dashboard y Rutas
    Route::prefix('coordinador')->name('coordinador.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CoordinadorController::class, 'dashboard'])->name('dashboard');
        Route::resource('solicitudes', \App\Http\Controllers\CoordinadorController::class)->except(['show']);
        Route::post('solicitudes/{solicitud}/enviar-verificacion', [\App\Http\Controllers\CoordinadorController::class, 'enviarAVerificacion'])->name('solicitudes.enviar-verificacion');
        // Agregaremos más rutas del coordinador conforme avancemos
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
