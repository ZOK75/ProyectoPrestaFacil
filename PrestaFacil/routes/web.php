<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GerenteGeneralController;
use App\Http\Controllers\GerenteSucursalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// 1. Gerente General
    Route::middleware(['auth'])
    ->prefix('gerente-general')
    ->group(function () {
        
        Route::get('/dashboard', [GerenteGeneralController::class, 'index'])
        ->name('gerente-general.dashboard');
    });

// 2. Gerente Sucursal
    Route::middleware(['auth'])
    ->prefix('gerente-sucursal')
    ->group(function () {

        Route::get('/dashboard',[GerenteSucursalController::class,'index'])
        ->name('gerente-sucursal.dashboard');   
        });

require __DIR__.'/auth.php'; 

