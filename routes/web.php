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
});
