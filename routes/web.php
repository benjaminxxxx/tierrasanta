<?php

use Illuminate\Support\Facades\Route;



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/', function () {
        return view('empleados');
    })->name('inicio');

    Route::get('/dashboard', function () {
        return view('empleados');
    })->name('dashboard');

    Route::get('/empleados', function () {
        return view('empleados');
    })->name('empleados');
    Route::get('/configuracion', function () {
        return view('configuracion');
    })->name('configuracion');
    Route::get('/descuentos-de-afp', function () {
        return view('descuentos_afp');
    })->name('descuentos_afp');
    Route::get('/empleados/asignacion-familiar', function () {
        return view('empleados.asignacion_familiar');
    })->name('empleados.asignacion_familiar');
});
