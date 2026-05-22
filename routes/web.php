<?php

use App\Constants\Permisos;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CampaniaController;
use App\Http\Controllers\CampoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CochinillaController;
use App\Http\Controllers\CuadrillaController;
use App\Http\Controllers\FdmController;
use App\Http\Controllers\GestionInsumosController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\MaquinariaController;
use App\Http\Controllers\NutrienteController;
use App\Http\Controllers\PermisosRolController;
use App\Http\Controllers\ReporteDiarioController;
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
        return view('livewire.dashboard.indice');
    })->name('inicio');

    Route::get('/dashboard', function () {
        return view('livewire.dashboard.indice');
    })->name('dashboard');

    Route::get('/planilla/asistencia/{anio?}/{mes?}', [AsistenciaPlanillaController::class, 'index'])->name('planilla.asistencia')->middleware('can:' . Permisos::PLANILLA_ASISTENCIA);
    Route::get('/planilla/bn', [AsistenciaPlanillaController::class, 'blanco'])->name('planilla.blanco')->middleware('can:' . Permisos::PLANILLA_BLANCO);

    Route::get('/empleados', function () {
        return view('livewire.gestion-planilla.administrar-planillero.indice-empleados');
    })->name('empleados')->middleware('can:Planilla Empleados');
    /*Route::get('/configuracion', function () {
        return view('configuracion');
    })->name('configuracion'); obsoleto*/
    Route::get('/descuentos-de-afp', function () {
        return view('descuentos_afp');
    })->name('descuentos_afp')->middleware('can:' . Permisos::PLANILLA_CONFIG_AFP);
    Route::get('/empleados/asignacion-familiar', function () {
        return view('empleados.asignacion_familiar');
    })->name('empleados.asignacion_familiar')->middleware('can:' . Permisos::PLANILLA_FAMILIAR);

    Route::get('/riego/labores', function () {
        return view('configuracion.labores_riego');
    })->name('configuracion.labores_riego')->middleware('can:' . Permisos::CAMPO_RIEGO_LABOR);

    Route::get('/campo/labores', function () {
        return view('configuracion.labores');
    })->name('configuracion.labores')->middleware('can:' . Permisos::CAMPO_LABOR);

    Route::get('/campo/mano_obra', function () {
        return view('livewire.gestion-campo.index-mano-obra');
    })->name('campo.mano_obra')->middleware('can:' . Permisos::CAMPO_MANO_OBRA);


    Route::get('/configuracion/tipos-asistencias', function () {
        return view('livewire.gestion-asistencia.tipo-asistencia-indice');
    })->name('configuracion.tipo_asistencia')->middleware('can:' . Permisos::PLANILLA_CONFIG_ASISTENCIA);


    //CUADRILLA
    Route::get('/cuadrilla/cuadrilleros', function () {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilleros');
    })->name('cuadrilla.cuadrilleros')->middleware('can:' . Permisos::CUADRILLA_LISTA);

    Route::get('/cuadrilla/grupos', function () {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.grupos');
    })->name('cuadrilla.grupos')->middleware('can:' . Permisos::CUADRILLA_GRUPO);


    //CAMPAÑAS
    Route::get('/campanias', [CampaniaController::class, 'campanias'])->name('campanias')->middleware('can:' . Permisos::CAMPAÑA_RESUMEN);
    Route::get('/campanias_x_campo/{campania?}', [CampoController::class, 'campaniaxcampo'])->name('campania.x.campo')->middleware('can:' . Permisos::CAMPAÑA_POR_CAMPO);
    Route::get('/riego/estados', [CampoController::class, 'riego'])->name('campo.riego')->middleware('can:' . Permisos::CAMPO_RIEGO_ESTADO);
    Route::get('/campo/campos', [CampoController::class, 'campos'])->name('campo.campos')->middleware('can:' . Permisos::CAMPO_PARCELA);
    Route::get('/campo/siembras', [CampoController::class, 'siembra'])->name('campo.siembra')->middleware('can:' . Permisos::CAMPO_SIEMBRA);
    
    Route::get('/campania/costos', [CampaniaController::class, 'costos'])->name('campania.costos')->middleware('can:' . Permisos::CAMPAÑA_COSTOS);
    Route::get('/campania/calendario', function () {
        return view('livewire.gestion-campania.campania-calendario-indice');
    })->name('campania.calendario')->middleware('can:' . Permisos::CAMPAÑA_CALENDARIO);

    //Consolidados
    Route::get('/riego/resumen-diario', function () {
        return view('consolidado.riegos');
    })->name('consolidado.riego')->middleware('can:' . Permisos::CAMPO_RIEGO_RESUMEN);

    //Planilla
    
    //REPORTE
    Route::prefix('planilla')->group(function () {
        // Pantalla principal / dashboard
        Route::get('/importar', function () {
            return view('livewire.gestion-planilla.importar-planilla-indice');
        })->name('planilla.importar'); //enlace para el programador aun falta arreglar cosas, comoc rear el formato, revisar bien los datos, etc

        Route::get('/contratos/{id?}', function ($id = null) {
            return view('livewire.gestion-planilla.contratos-planilla-indice', compact('id'));
        })->name('planilla.contratos')->middleware('can:' . Permisos::PERSONAL_CONTRATOS);

        Route::get('/conceptos', function () {
            return view('livewire.gestion-planilla.conceptos-planilla-indice');
        })->name('planilla.conceptos')->middleware('can:' . Permisos::PLANILLA_CONCEPTO);

        Route::get('/parametros', function () {
            return view('livewire.gestion-planilla.parametros-planilla-indice');
        })->name('planilla.parametros')->middleware('can:' . Permisos::PLANILLA_PARAMETRO);

        Route::get('/suspensiones', function () {
            return view('livewire.gestion-planilla.suspensiones-planilla-indice');
        })->name('planilla.suspensiones')->middleware('can:' . Permisos::PLANILLA_SUSPENSION);

        Route::get('/gestion_planilla/reporte_general', function () {
            // Apunta a la nueva y más corta ubicación
            return view('livewire.gestion-planilla.reportes.reporte-general-index');
        })
            ->name('gestion_planilla.reporte_general')->middleware('can:' . Permisos::PLANILLA_RESUMEN_GENERAL);
    });
    Route::prefix('cuadrilla/gestion_cuadrilleros')->group(function () {
        // Pantalla principal / dashboard
        Route::get('/', [CuadrillaController::class, 'gestion'])
            ->name('cuadrilleros.gestion')->middleware('can:' . Permisos::CUADRILLA_PANEL);

        // Registro Diario cuadrilla/gestion_cuadrilleros/registro-diario
        Route::get('/registro-diario', [CuadrillaController::class, 'registro_diario'])
            ->name('gestion_cuadrilleros.registro-diario.index')->middleware('can:' . Permisos::CUADRILLA_DIARIO);


        // Grupos de Pago
        Route::get('/reporte-semanal', [CuadrillaController::class, 'reporte_semanal'])
            ->name('gestion_cuadrilleros.reporte-semanal.index')->middleware('can:' . Permisos::CUADRILLA_SEMANAL);

        // Períodos Grupales

        // Módulo de Pagos
        Route::get('/resumen-general', [CuadrillaController::class, 'pagos'])
            ->name('gestion_cuadrilleros.resumen_general.index')->middleware('can:' . Permisos::CUADRILLA_RESUMEN_GENERAL);

        // Bonificaciones
        Route::get('/bonificaciones', [CuadrillaController::class, 'bonificaciones'])
            ->name('gestion_cuadrilleros.bonificaciones.index')->middleware('can:' . Permisos::CUADRILLA_BONIFICACION);

        // Resumen anual cuadrilla
        Route::get('/resumen_anual', [CuadrillaController::class, 'resumen_anual'])
            ->name('gestion_cuadrilleros.resumen_anual')->middleware('can:' . Permisos::CUADRILLA_RESUMEN_ANUAL);
    });
    Route::get('/reporte-general/reporte-diario', [ReporteController::class, 'reporte_diario'])->name('reporte_general.reporte_diario')->middleware('can:' . Permisos::REPORTE_DIARIO);
    Route::get('/reporte-general/reporte-mensual', [ReporteController::class, 'reporte_mensual'])->name('reporte_general.reporte_mensual')->middleware('can:' . Permisos::REPORTE_MENSUAL);
    Route::get('/reporte-general/reporte-anual', [ReporteController::class, 'reporte_anual'])->name('reporte_general.reporte_anual')->middleware('can:' . Permisos::REPORTE_ANUAL);

    Route::get('/auditoria', [ReporteController::class, 'auditoria'])->name('auditoria')->middleware('can:' . Permisos::REPORTE_AUDITORIA);
    Route::get('/reporte/reporte-diario', [ReporteDiarioController::class, 'index'])->name('reporte.reporte_diario')->middleware('can:' . Permisos::PLANILLA_ACTIVIDAD);
    Route::get('/riego/reporte-diario', [ReporteDiarioController::class, 'riego'])->name('reporte.reporte_diario_riego')->middleware('can:' . Permisos::CAMPO_RIEGO_REPORTE);
    Route::post('/reporte/reporte-diario/importar-empleados', [ReporteDiarioController::class, 'ImportarEmpleados'])->name('reporte.reporte_diario.importar_empleados');
    Route::post('/reporte/reporte-diario/guardar-empleados', [ReporteDiarioController::class, 'GuardarInformacion'])->name('reporte.reporte_diario.guardar_informacion');
    Route::post('/reporte/reporte-diario/actualizar-campos', [ReporteDiarioController::class, 'ActualizarCampos'])->name('reporte.reporte_diario.actualizar_campos');

    Route::get('/planilla/resumen-mensual', [ReporteController::class, 'ResumenPlanilla'])->name('reporte.resumen_planilla')->middleware('can:' . Permisos::PLANILLA_RESUMEN_MENSUAL);

    //PROVEEDORES
    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index')->middleware('can:' . Permisos::INSUMO_PROVEEDOR);

    //PRODUCTOS
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index')->middleware('can:' . Permisos::INSUMO_PRODUCTO);
    Route::get('/producto/usos', [ProductoController::class, 'usos'])->name('producto.usos')->middleware('can:' . Permisos::INSUMO_USO);
    Route::get('/categorias/subcategorias', [CategoriaController::class, 'subcategorias'])->name('subcategorias.index')->middleware('can:' . Permisos::INSUMO_SUBCATEGORIA);
    Route::get('/categorias/categorias', [CategoriaController::class, 'categorias'])->name('categorias.index')->middleware('can:' . Permisos::INSUMO_CATEGORIA);

    //Nutrientes
    Route::get('/nutrientes', [NutrienteController::class, 'index'])->name('nutrientes.index')->middleware('can:' . Permisos::INSUMO_NUTRIENTE);
    Route::get('/nutrientes/tabla-concentracion', [NutrienteController::class, 'tabla_concentracion'])->name('tabla_concentracion.index')->middleware('can:' . Permisos::INSUMO_CONCENTRACION);


    //MAQUINARIAS
    Route::get('/maquinarias', [MaquinariaController::class, 'index'])->name('maquinarias.index')->middleware('can:' . Permisos::CAMPO_MAQUINARIA);

    //ALMACEN
    Route::get('/almacen/compras/{producto_id?}', [AlmacenController::class, 'compraProductos'])->name('almacen.compras')->middleware('can:' . Permisos::INSUMO_COMPRA);
    Route::get('/almacen/salida_de_productos', [AlmacenController::class, 'salidaProductos'])->name('almacen.salida_productos')->middleware('can:' . Permisos::INSUMO_SALIDA);
    Route::get('/almacen/salida_de_combustible', [AlmacenController::class, 'salidaCombustible'])->name('almacen.salida_combustible')->middleware('can:' . Permisos::INSUMO_COMBUSTIBLE);
    Route::get('/almacen/distribucion_combustible', [AlmacenController::class, 'distribucionCombustible'])->name('almacen.distribucion_combustible')->middleware('can:' . Permisos::INSUMO_DISTRIBUCION);

    //USUARIOS
    Route::get('/gestion-usuario/usuarios', [UsuarioController::class, 'index'])->middleware('can:' . Permisos::SISTEMA_USUARIO)->name('usuarios');
    Route::get('/gestion-usuario/roles-y-permisos', [UsuarioController::class, 'roles_permisos'])->middleware('can:' . Permisos::SISTEMA_ROL)->name('roles_permisos');
    Route::get('/gestion-usuario/permisos-rol/{rol}', [PermisosRolController::class, 'index'])
        ->name('gestion-usuario.permisos-rol')->middleware('can:' . Permisos::SISTEMA_ROL_GESTIONAR);

    //INSUMOS
    Route::get('/gestion_insumos/kardex', [GestionInsumosController::class, 'kardex'])->name('gestion_insumos.kardex')->middleware('can:' . Permisos::INSUMO_KARDEX);
    Route::get('/gestion_insumos/kardex/crear', [GestionInsumosController::class, 'kardexCrear'])->name('gestion_insumos.kardex.crear')->middleware('can:' . Permisos::INSUMO_KARDEX);
    Route::get('/gestion_insumos/kardex/detalle/{insumoKardexId}', [GestionInsumosController::class, 'kardexDetalle'])->name('gestion_insumos.kardex.detalle')->middleware('can:' . Permisos::INSUMO_KARDEX);
    Route::get('/gestion_insumos/kardex/reportes', [GestionInsumosController::class, 'kardexReportes'])->name('gestion_insumos.kardex.reportes')->middleware('can:' . Permisos::INSUMO_KARDEX_REPORTE);
    Route::get('/gestion_insumos/kardex/reporte/{insumoKardexReporteId}', [GestionInsumosController::class, 'kardexReporte'])->name('gestion_insumos.kardex.reporte')->middleware('can:' . Permisos::INSUMO_KARDEX_REPORTE_VER);
    Route::get('/gestion_insumos/kardex/asignacion/{productoId}/{anio}', [GestionInsumosController::class, 'kardexAsignacion'])->name('gestion_insumos.kardex_asignacion')->middleware('can:' . Permisos::INSUMO_KARDEX_ASIGNAR_MOVIMIENTOS);
    //KARDEX
    Route::get('/kardex/lista', [KardexController::class, 'lista'])->name('kardex.lista');
    Route::get('/kardex/ver/{id}', [KardexController::class, 'ver'])->name('kardex.ver');

    //GASTOS
    //Route::get('/contabilidad/gasto/general', [GastoController::class, 'general'])->name('gastos.general');
    Route::get('/contabilidad/costo_mensual', [GastoController::class, 'costo_mensual'])->name('contabilidad.costo_mensual')->middleware('can:' . Permisos::CONTABILIDAD_COSTO_MENSUAL);
    Route::get('/contabilidad/costos_mensuales', [GastoController::class, 'costos_mensuales'])->name('contabilidad.costos_mensuales')->middleware('can:' . Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA);
    //Route::get('/contabilidad/costos_generales', [GastoController::class, 'costos_generales'])->name('contabilidad.costos_generales'); duplicado en costos mensuales

    //FDM
    Route::get('/fdm/costos_generales', [FdmController::class, 'costos_generales'])->name('fdm.costos_generales')->middleware('can:' . Permisos::CONTABILIDAD_FDM);

    //REPORTE CAMPO
    Route::get('/evaluacion_campo/poblacion_planta', [ReporteCampoController::class, 'poblacion_plantas'])->name('reporte_campo.poblacion_plantas')->middleware('can:' . Permisos::PLANTA_EVALUACION);
    Route::get('/evaluacion_campo/evaluacion_brotes', [ReporteCampoController::class, 'evaluacion_brotes'])->name('reporte_campo.evaluacion_brotes')->middleware('can:' . Permisos::BROTE_EVALUACION);
    Route::get('/evaluacion_campo/evaluacion_infestacion_cosecha', [ReporteCampoController::class, 'evaluacion_infestacion_cosecha'])->name('reporte_campo.evaluacion_infestacion_cosecha')->middleware('can:' . Permisos::INFESTACION_EVALUACION);
    Route::get('/evaluacion-campo/proyeccion-rendimiento-poda', [ReporteCampoController::class, 'evaluacion_proyeccion_rendimiento_poda'])->name('reporte_campo.evaluacion_proyeccion_rendimiento_poda')->middleware('can:' . Permisos::PROYECCION_EVALUACION);

    //COCHINILLA
    Route::get('/cochinilla/ingreso', [CochinillaController::class, 'ingreso'])->name('cochinilla.ingreso')->middleware('can:' . Permisos::COCHINILLA_INGRESO);
    Route::get('/cochinilla/venteado', [CochinillaController::class, 'venteado'])->name('cochinilla.venteado')->middleware('can:' . Permisos::COCHINILLA_VENTEADO);
    Route::get('/cochinilla/filtrado', [CochinillaController::class, 'filtrado'])->name('cochinilla.filtrado')->middleware('can:' . Permisos::COCHINILLA_FILTRADO);
    Route::get('/cochinilla/cosechamamas', [CochinillaController::class, 'cosecha_mamas'])->name('cochinilla.cosecha_mamas')->middleware('can:' . Permisos::COCHINILLA_COSECHA);
    Route::get('/cochinilla/infestacion', [CochinillaController::class, 'infestacion'])->name('cochinilla.infestacion')->middleware('can:' . Permisos::COCHINILLA_INFESTACION);
    Route::get('/cochinilla/ventas', [CochinillaController::class, 'ventas'])->name('cochinilla.ventas')->middleware('can:' . Permisos::COCHINILLA_VENTA);

});
