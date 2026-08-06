<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoValeController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GerenteGeneralController;
use App\Http\Controllers\GerenteSucursalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirección inicial según estado de sesión
Route::get('/', function () {
    return Auth::check() ? redirect()->route('producto-vales.index') : redirect()->route('login');
});

// Todas las rutas del sistema requieren sesión activa (middleware 'auth')
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
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

    // Módulos del Sistema
    Route::resource('producto-vales', ProductoValeController::class);
    Route::resource('clientes', ClienteController::class);

    Route::get('configuracion-general', [ConfiguracionController::class, 'edit'])->name('configuracion-general.edit');
    Route::put('configuracion-general', [ConfiguracionController::class, 'update'])->name('configuracion-general.update');

    Route::resource('usuarios', UserController::class);
});

require __DIR__.'/auth.php';
