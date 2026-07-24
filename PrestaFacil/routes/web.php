<?php

use App\Http\Controllers\ProductoValeController;
use Illuminate\Support\Facades\Route;

Route::get('/producto-vales', function () {
    return redirect()->route('producto-vales.index');
});

Route::resource('producto-vales', ProductoValeController::class);
