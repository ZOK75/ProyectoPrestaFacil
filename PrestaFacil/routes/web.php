<?php

use App\Http\Controllers\ProductoValeController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('producto-vales', ProductoValeController::class);

Route::get('configuracion-general', [ConfiguracionController::class, 'edit'])->name('configuracion-general.edit');
Route::put('configuracion-general', [ConfiguracionController::class, 'update'])->name('configuracion-general.update');

Route::resource('usuarios', UserController::class);