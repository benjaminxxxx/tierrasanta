<?php

use App\Http\Controllers\CampoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaPlanillaController;


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

    Route::get('/planilla/asistencia', [AsistenciaPlanillaController::class,'index'])->name('planilla.asistencia');

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

    //CUADRILLA
    Route::get('/cuadrilla/cuadrilleros', function () {
        return view('cuadrilla.cuadrilleros');
    })->name('cuadrilla.cuadrilleros');

    Route::get('/cuadrilla/grupos', function () {
        return view('cuadrilla.grupos');
    })->name('cuadrilla.grupos');

    Route::get('/cuadrilla/asistencia', function () {
        return view('cuadrilla.asistencia');
    })->name('cuadrilla.asistencia');

    Route::get('/campo/mapa', [CampoController::class,'mapa'])->name('campo.mapa');
    Route::get('/campo/riego', [CampoController::class,'riego'])->name('campo.riego');
    Route::get('/campo/detalleriego', [CampoController::class,'detalleriego'])->name('campo.detalle_riego');
    Route::post('/campo/mapa/guardar-posicion/{nombre}', [CampoController::class,'guardarPosicion'])->name('campo.mapa.guardar-posicion');

    //Consolidados
    Route::get('/consolidado/riego', function () {
        return view('consolidado.riegos');
    })->name('consolidado.riego');

    //Planilla
    Route::get('/planilla/asistencia/cargar-asistencias', [AsistenciaPlanillaController::class, 'cargarAsistencias'])->name('planilla.asistencia.cargar_asistencias');
});
