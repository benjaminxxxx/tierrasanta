<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CampaniaController;
use App\Http\Controllers\CampoController;
use App\Http\Controllers\CochinillaController;
use App\Http\Controllers\CuadrillaController;
use App\Http\Controllers\FdmController;
use App\Http\Controllers\GestionInsumosController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\MaquinariaController;
use App\Http\Controllers\NutrienteController;
use App\Http\Controllers\ReporteDiarioController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaPlanillaController;
use App\Http\Controllers\GastoController;
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
        return view('livewire.gestion-planilla.administrar-planillero.indice-empleados');
    })->name('inicio');

    Route::get('/dashboard', function () {
        return view('empleados');
    })->name('dashboard');

    Route::get('/planilla/asistencia/{anio?}/{mes?}', [AsistenciaPlanillaController::class, 'index'])->name('planilla.asistencia');
    Route::get('/planilla/bn', [AsistenciaPlanillaController::class, 'blanco'])->name('planilla.blanco');

    Route::get('/empleados', function () {
        return view('livewire.gestion-planilla.administrar-planillero.indice-empleados');
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

    Route::get('/riego/labores', function () {
        return view('configuracion.labores_riego');
    })->name('configuracion.labores_riego');

    Route::get('/campo/labores', function () {
        return view('configuracion.labores');
    })->name('configuracion.labores');

    Route::get('/campo/mano_obra', function () {
        return view('livewire.gestion-campo.index-mano-obra');
    })->name('campo.mano_obra');


    Route::get('/configuracion/tipos-asistencias', function () {
        return view('livewire.gestion-asistencia.tipo-asistencia-indice');
    })->name('configuracion.tipo_asistencia');


    //CUADRILLA
    Route::get('/cuadrilla/cuadrilleros', function () {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilleros');
    })->name('cuadrilla.cuadrilleros');

    Route::get('/cuadrilla/grupos', function () {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.grupos');
    })->name('cuadrilla.grupos');


    //CAMPAÑAS
    Route::get('/campanias', [CampaniaController::class, 'campanias'])->name('campanias');
    Route::get('/campo/camapania/{campo?}', [CampoController::class, 'campania'])->name('campo.campania');
    Route::get('/campanias_x_campo/{campania?}', [CampoController::class, 'campaniaxcampo'])->name('campania.x.campo');
    Route::get('/riego/estados', [CampoController::class, 'riego'])->name('campo.riego');
    Route::get('/campo/campos', [CampoController::class, 'campos'])->name('campo.campos');
    Route::get('/campo/siembras', [CampoController::class, 'siembra'])->name('campo.siembra');
    Route::post('/campo/mapa/guardar-posicion/{nombre}', [CampoController::class, 'guardarPosicion'])->name('campo.mapa.guardar-posicion');

    Route::get('/campania/costos', [CampaniaController::class, 'costos'])->name('campania.costos');
    Route::get('/campania/calendario', function () {
        return view('livewire.gestion-campania.campania-calendario-indice');
    })->name('campania.calendario');

    //Consolidados
    Route::get('/riego/resumen-diario', function () {
        return view('consolidado.riegos');
    })->name('consolidado.riego');

    //Planilla
    Route::get('/planilla/asistencia/cargar-asistencias', [AsistenciaPlanillaController::class, 'cargarAsistencias'])->name('planilla.asistencia.cargar_asistencias');

    //REPORTE
    Route::prefix('planilla')->group(function () {
        // Pantalla principal / dashboard
        Route::get('/gestion_planilla/reporte_general', function () {
            // Apunta a la nueva y más corta ubicación
            return view('livewire.gestion-planilla.reportes.reporte-general-index');
        })
            ->name('gestion_planilla.reporte_general'); // ¡Cambia el nombre de la ruta para ser más específico!
    });
    Route::prefix('cuadrilla/gestion_cuadrilleros')->group(function () {
        // Pantalla principal / dashboard
        Route::get('/', [CuadrillaController::class, 'gestion'])
            ->name('cuadrilleros.gestion');

        // Registro Diario
        Route::get('/registro-diario', [CuadrillaController::class, 'registro_diario'])
            ->name('gestion_cuadrilleros.registro-diario.index');

        // Gestión de Actividades
        Route::get('/actividades', [CuadrillaController::class, 'actividades'])
            ->name('gestion_cuadrilleros.actividades.index');

        // Grupos de Pago
        Route::get('/reporte-semanal', [CuadrillaController::class, 'reporte_semanal'])
            ->name('gestion_cuadrilleros.reporte-semanal.index');

        // Períodos Grupales
        Route::get('/periodos', [CuadrillaController::class, 'periodos'])
            ->name('gestion_cuadrilleros.periodos.index');

        // Módulo de Pagos
        Route::get('/resumen-general', [CuadrillaController::class, 'pagos'])
            ->name('gestion_cuadrilleros.resumen_general.index');

        // Bonificaciones
        Route::get('/bonificaciones', [CuadrillaController::class, 'bonificaciones'])
            ->name('gestion_cuadrilleros.bonificaciones.index');

        // Resumen anual cuadrilla
        Route::get('/resumen_anual', [CuadrillaController::class, 'resumen_anual'])
            ->name('gestion_cuadrilleros.resumen_anual');
    });
    Route::get('/reporte/actividades-diarias', [ReporteDiarioController::class, 'actividades_diarias'])->name('reporte.actividades_diarias');
    Route::get('/reporte/reporte-diario', [ReporteDiarioController::class, 'index'])->name('reporte.reporte_diario');
    Route::get('/riego/reporte-diario', [ReporteDiarioController::class, 'riego'])->name('reporte.reporte_diario_riego');
    Route::post('/reporte/reporte-diario/importar-empleados', [ReporteDiarioController::class, 'ImportarEmpleados'])->name('reporte.reporte_diario.importar_empleados');
    Route::post('/reporte/reporte-diario/guardar-empleados', [ReporteDiarioController::class, 'GuardarInformacion'])->name('reporte.reporte_diario.guardar_informacion');
    Route::post('/reporte/reporte-diario/actualizar-campos', [ReporteDiarioController::class, 'ActualizarCampos'])->name('reporte.reporte_diario.actualizar_campos');

    Route::get('/planilla/resumen-mensual', [ReporteController::class, 'ResumenPlanilla'])->name('reporte.resumen_planilla');

    //PROVEEDORES
    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');

    //PRODUCTOS
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');

    //Nutrientes
    Route::get('/nutrientes', [NutrienteController::class, 'index'])->name('nutrientes.index');
    Route::get('/nutrientes/tabla-concentracion', [NutrienteController::class, 'tabla_concentracion'])->name('tabla_concentracion.index');


    //MAQUINARIAS
    Route::get('/maquinarias', [MaquinariaController::class, 'index'])->name('maquinarias.index');

    //ALMACEN
    Route::get('/almacen/salida_de_productos', [AlmacenController::class, 'salidaProductos'])->name('almacen.salida_productos');
    Route::get('/almacen/salida_de_combustible', [AlmacenController::class, 'salidaCombustible'])->name('almacen.salida_combustible');

    //USUARIOS
    Route::get('/gestion-usuario/usuarios', [UsuarioController::class, 'index'])->middleware('permission:Usuarios Administrar')->name('usuarios');
    Route::get('/gestion-usuario/roles-y-permisos', [UsuarioController::class, 'roles_permisos'])->middleware('permission:Roles')->name('roles_permisos');


    //INSUMOS
    Route::get('/gestion_insumos/kardex', [GestionInsumosController::class, 'kardex'])->name('gestion_insumos.kardex');
    Route::get('/gestion_insumos/kardex/detalle/{insumoKardexId}', [GestionInsumosController::class, 'kardexDetalle'])->name('gestion_insumos.kardex.detalle');
    Route::get('/gestion_insumos/kardex/reportes', [GestionInsumosController::class, 'kardexReportes'])->name('gestion_insumos.kardex.reportes');
    Route::get('/gestion_insumos/kardex/reporte/{insumoKardexReporteId}', [GestionInsumosController::class, 'kardexReporte'])->name('gestion_insumos.kardex.reporte');
    Route::get('/gestion_insumos/kardex/asignacion/{productoId}/{anio}', [GestionInsumosController::class, 'kardexAsignacion'])->name('gestion_insumos.kardex_asignacion');
    //KARDEX
    Route::get('/kardex/lista', [KardexController::class, 'lista'])->name('kardex.lista');
    Route::get('/kardex/ver/{id}', [KardexController::class, 'ver'])->name('kardex.ver');

    //GASTOS
    Route::get('/contabilidad/gasto/general', [GastoController::class, 'general'])->name('gastos.general');
    Route::get('/contabilidad/costo_mensual', [GastoController::class, 'costo_mensual'])->name('contabilidad.costo_mensual');
    Route::get('/contabilidad/costos_mensuales', [GastoController::class, 'costos_mensuales'])->name('contabilidad.costos_mensuales');
    Route::get('/contabilidad/costos_generales', [GastoController::class, 'costos_generales'])->name('contabilidad.costos_generales');

    //FDM
    Route::get('/fdm/costos_generales', [FdmController::class, 'costos_generales'])->name('fdm.costos_generales');

    //REPORTE CAMPO
    Route::get('/evaluacion_campo/poblacion_planta', [ReporteCampoController::class, 'poblacion_plantas'])->name('reporte_campo.poblacion_plantas');
    Route::get('/evaluacion_campo/evaluacion_brotes', [ReporteCampoController::class, 'evaluacion_brotes'])->name('reporte_campo.evaluacion_brotes');
    Route::get('/evaluacion_campo/evaluacion_infestacion_cosecha', [ReporteCampoController::class, 'evaluacion_infestacion_cosecha'])->name('reporte_campo.evaluacion_infestacion_cosecha');
    Route::get('/evaluacion-campo/proyeccion-rendimiento-poda', [ReporteCampoController::class, 'evaluacion_proyeccion_rendimiento_poda'])->name('reporte_campo.evaluacion_proyeccion_rendimiento_poda');

    //COCHINILLA
    Route::get('/cochinilla/ingreso', [CochinillaController::class, 'ingreso'])->name('cochinilla.ingreso');
    Route::get('/cochinilla/venteado', [CochinillaController::class, 'venteado'])->name('cochinilla.venteado');
    Route::get('/cochinilla/filtrado', [CochinillaController::class, 'filtrado'])->name('cochinilla.filtrado');
    Route::get('/cochinilla/cosechamamas', [CochinillaController::class, 'cosecha_mamas'])->name('cochinilla.cosecha_mamas');
    Route::get('/cochinilla/infestacion', [CochinillaController::class, 'infestacion'])->name('cochinilla.infestacion');
    Route::get('/cochinilla/ventas', [CochinillaController::class, 'ventas'])->name('cochinilla.ventas');

    Route::get('/test/costos', [TestController::class, 'mano_obra']);
});
