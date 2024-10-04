<?php

use App\Http\Controllers\CampoController;
use App\Http\Controllers\ReporteDiarioController;
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

    Route::get('/configuracion/labores-riego', function () {
        return view('configuracion.labores_riego');
    })->name('configuracion.labores_riego');

    Route::get('/configuracion/tipos-asistencias', function () {
        return view('configuracion.tipo_asistencia');
    })->name('configuracion.tipo_asistencia');

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

    //REPORTE
    Route::get('/reporte/reporte-diario', [ReporteDiarioController::class, 'index'])->name('reporte.reporte_diario');
    Route::get('/reporte/reporte-diario-riego', [ReporteDiarioController::class, 'riego'])->name('reporte.reporte_diario_riego');
    Route::post('/reporte/reporte-diario/importar-empleados', [ReporteDiarioController::class, 'ImportarEmpleados'])->name('reporte.reporte_diario.importar_empleados');
    Route::post('/reporte/reporte-diario/guardar-empleados', [ReporteDiarioController::class, 'GuardarInformacion'])->name('reporte.reporte_diario.guardar_informacion');
    Route::post('/reporte/reporte-diario/actualizar-campos', [ReporteDiarioController::class, 'ActualizarCampos'])->name('reporte.reporte_diario.actualizar_campos');
    //Route::get('/reporte/reporte-diario/obtener-campos', [ReporteDiarioController::class, 'ObtenerCampos'])->name('reporte.reporte_diario.obtener_campos');
    //Route::get('/reporte/reporte-diario/obtener-campo', [ReporteDiarioController::class, 'ObtenerCampo'])->name('reporte.reporte_diario.obtener_campo');

});
