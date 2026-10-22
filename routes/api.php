<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Libros Excel con macro (botones Actualizar / Subir). Acceso: correo + clave personal (LibroExcelApiAcceso).
Route::prefix('libros/{libro}')
    ->middleware(['throttle:libros-excel', \App\Http\Middleware\LibroExcelApiAcceso::class])
    ->controller(\App\Http\Controllers\Api\LibroExcelApiController::class)
    ->group(function () {
        Route::get('/excel', 'excel')->name('api.libros.excel');
        Route::post('/revisar', 'revisar')->name('api.libros.revisar');
        Route::post('/guardar/{id}', 'guardar')->whereUuid('id')->name('api.libros.guardar');
    });
