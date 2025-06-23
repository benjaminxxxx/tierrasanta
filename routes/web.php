<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CampaniaController;
use App\Http\Controllers\CampoController;
use App\Http\Controllers\CochinillaController;
use App\Http\Controllers\FdmController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\MaquinariaController;
use App\Http\Controllers\NutrienteController;
use App\Http\Controllers\ReporteDiarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaPlanillaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\ProductividadController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteCampoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\CheckUserStatus;

Route::middleware([
    'auth:sanctum',
    CheckUserStatus::class,
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/', function () {
        return view('empleados');
    })->name('inicio');

    Route::get('/dashboard', function () {
        return view('empleados');
    })->name('dashboard');

    Route::get('/planilla/asistencia/{anio?}/{mes?}', [AsistenciaPlanillaController::class,'index'])->name('planilla.asistencia');
    Route::get('/planilla/blanco', [AsistenciaPlanillaController::class,'blanco'])->name('planilla.blanco');

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

    Route::get('/configuracion/labores', function () {
        return view('configuracion.labores');
    })->name('configuracion.labores');

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

    //CAMPAÑAS
    Route::get('/campanias', [CampaniaController::class,'campanias'])->name('campanias');
    Route::get('/campo/camapania/{campo?}', [CampoController::class,'campania'])->name('campo.campania');

    Route::get('/campo/mapa', [CampoController::class,'mapa'])->name('campo.mapa');
    Route::get('/campo/riego', [CampoController::class,'riego'])->name('campo.riego');
    Route::get('/campo/campos', [CampoController::class,'campos'])->name('campo.campos');
    Route::get('/campo/siembras', [CampoController::class,'siembra'])->name('campo.siembra');
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

    Route::get('/reporte/pago-cuadrilla', [ReporteController::class, 'PagoCuadrilla'])->name('reporte.pago_cuadrilla');
    Route::get('/reporte/resumen-planilla', [ReporteController::class, 'ResumenPlanilla'])->name('reporte.resumen_planilla');
    //Route::get('/reporte/reporte-diario/obtener-campos', [ReporteDiarioController::class, 'ObtenerCampos'])->name('reporte.reporte_diario.obtener_campos');
    //Route::get('/reporte/reporte-diario/obtener-campo', [ReporteDiarioController::class, 'ObtenerCampo'])->name('reporte.reporte_diario.obtener_campo');

    //PROVEEDORES
    Route::get('/proveedores', [ProveedorController::class,'index'])->name('proveedores.index');

    //PRODUCTOS
    Route::get('/productos', [ProductoController::class,'index'])->name('productos.index');

    //Nutrientes
    Route::get('/nutrientes', [NutrienteController::class,'index'])->name('nutrientes.index');
    Route::get('/nutrientes/tabla-concentracion', [NutrienteController::class,'tabla_concentracion'])->name('tabla_concentracion.index');
    

    //MAQUINARIAS
    Route::get('/maquinarias', [MaquinariaController::class,'index'])->name('maquinarias.index');

    //ALMACEN
    Route::get('/almacen/salida_de_productos', [AlmacenController::class,'salidaProductos'])->name('almacen.salida_productos');
    Route::get('/almacen/salida_de_combustible', [AlmacenController::class,'salidaCombustible'])->name('almacen.salida_combustible');

     //USUARIOS
     Route::get('/usuarios', [UsuarioController::class,'index'])->name('usuarios');

    //KARDEX
    Route::get('/kardex/lista', [KardexController::class,'lista'])->name('kardex.lista');
    Route::get('/kardex/ver/{id}', [KardexController::class,'ver'])->name('kardex.ver');

    //GASTOS
    Route::get('/contabilidad/gasto/general', [GastoController::class,'general'])->name('gastos.general');
    Route::get('/contabilidad/costos_mensuales', [GastoController::class,'costos_mensuales'])->name('contabilidad.costos_mensuales');
    Route::get('/contabilidad/costos_generales', [GastoController::class,'costos_generales'])->name('contabilidad.costos_generales');

    Route::get('/productividad/avance', [ProductividadController::class,'avance'])->name('productividad.avance');

    //FDM
    Route::get('/fdm/costos_generales', [FdmController::class,'costos_generales'])->name('fdm.costos_generales');

    //REPORTE CAMPO
    Route::get('/evaluacion_campo/poblacion_planta', [ReporteCampoController::class,'poblacion_plantas'])->name('reporte_campo.poblacion_plantas');
    Route::get('/evaluacion_campo/evaluacion_brotes', [ReporteCampoController::class,'evaluacion_brotes'])->name('reporte_campo.evaluacion_brotes');
    Route::get('/evaluacion_campo/evaluacion_infestacion_cosecha', [ReporteCampoController::class,'evaluacion_infestacion_cosecha'])->name('reporte_campo.evaluacion_infestacion_cosecha');
    Route::get('/evaluacion_campo/evaluacion_proyeccion_rendimiento_poda', [ReporteCampoController::class,'evaluacion_proyeccion_rendimiento_poda'])->name('reporte_campo.evaluacion_proyeccion_rendimiento_poda');

    //COCHINILLA
    Route::get('/cochinilla/ingreso', [CochinillaController::class,'ingreso'])->name('cochinilla.ingreso');
    Route::get('/cochinilla/venteado', [CochinillaController::class,'venteado'])->name('cochinilla.venteado');
    Route::get('/cochinilla/filtrado', [CochinillaController::class,'filtrado'])->name('cochinilla.filtrado');
    Route::get('/cochinilla/cosechamamas', [CochinillaController::class,'cosecha_mamas'])->name('cochinilla.cosecha_mamas');
    Route::get('/cochinilla/infestacion', [CochinillaController::class,'infestacion'])->name('cochinilla.infestacion');
    Route::get('/cochinilla/ventas', [CochinillaController::class,'ventas'])->name('cochinilla.ventas');
    
});
