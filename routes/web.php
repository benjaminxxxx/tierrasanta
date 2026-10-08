<?php

use App\Constants\Permisos;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Almacén
use App\Livewire\Almacen\Kardex\InsumoKardexComponent;
use App\Livewire\Almacen\Kardex\InsumoKardexCrearComponent;
use App\Livewire\Almacen\Kardex\KardexCargaComponent;
use App\Livewire\Almacen\Kardex\InsumoKardexDetalleComponent;
use App\Livewire\Almacen\Kardex\InsumoKardexReporteComponent;
use App\Livewire\Almacen\Kardex\InsumoKardexReporteDetalleComponent;
use App\Livewire\Almacen\ListaComprasComponent;
use App\Livewire\Almacen\ProveedoresComponent;
// Campaña
use App\Livewire\Campania\CampaniaCampoSelectorComponent;
use App\Livewire\Campania\CampaniasComponent;
// Campo
use App\Livewire\Campo\CamposComponent;
use App\Livewire\Campo\ServiciosCampoComponent;
// Cochinilla
use App\Livewire\Cochinilla\Venta\CochinillaVentasComponent;
// Costos
use App\Livewire\Costos\CampoCostosComponent;
use App\Livewire\Costos\ContabilidadCostosMensualesComponent;
use App\Livewire\Costos\FdmComponent;
// Cuadrilla
use App\Livewire\Cuadrilla\GestionCuadrillaBonificacionesComponent;
use App\Livewire\Cuadrilla\GestionCuadrillaReporteDiarioComponent;
use App\Livewire\Cuadrilla\Pago\GestionCuadrillaPagoComponent;
use App\Livewire\Cuadrilla\Pago\GestionCuadrillaPagosComponent;
// Evaluación
use App\Livewire\Evaluacion\EvaluacionInfestacionCosechaComponent;
use App\Livewire\Planilla\PlanillaOficinaComponent;
use App\Livewire\Evaluacion\ProyeccionRendimientoPodaComponent;
// Planilla
use App\Livewire\Planilla\Asistencia\AsistenciaMensualComponent;
use App\Livewire\Planilla\Asistencia\TipoAsistenciaComponent;
use App\Livewire\Planilla\ConfiguracionPrimasComisionesComponent;
use App\Livewire\Planilla\DerechoHabiente\DerechoHabienteListaComponent;
use App\Livewire\Planilla\Empleado\CargosComponent;
use App\Livewire\Planilla\Empleado\PanelContratoComponent;
use App\Livewire\Planilla\ResumenPlanillaComponent;
// Producto
use App\Livewire\Producto\CategoriasComponent;
use App\Livewire\Producto\NutrientesComponent;
use App\Livewire\Producto\SubcategoriasComponent;
use App\Livewire\Producto\TablaConcentracionComponent;
use App\Livewire\Producto\UsosComponent;
// Reporte
use App\Livewire\Reporte\AuditoriaComponent;
use App\Livewire\Reporte\ReporteAnualComponent;
use App\Livewire\Reporte\ReporteDiarioComponent;
use App\Livewire\Reporte\ReporteMensualComponent;
// Riego
use App\Livewire\Riego\ReporteDiarioRiegoComponent;
// Sistema
use App\Livewire\Sistema\PermisosRolComponent;
use App\Livewire\Sistema\RolesPermisosFormComponent;

/*
|--------------------------------------------------------------------------
| Rutas web, un grupo por dominio: URL /<dominio>/... y nombre <dominio>.*
|--------------------------------------------------------------------------
| - Pantalla de un solo componente → la ruta apunta al componente (página completa).
| - Pantalla que junta varios componentes → Route::view (sin controlador; los
|   parámetros de la ruta llegan a la vista como variables).
| - Las URL antiguas redirigen a las nuevas (ver final del archivo).
*/

Route::middleware([
    'auth:sanctum',
    CheckUserStatus::class,
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::view('/', 'livewire.sistema.dashboard-indice')->name('inicio');
    Route::view('/dashboard', 'livewire.sistema.dashboard-indice')->name('dashboard');

    // ================================================================ SISTEMA
    Route::prefix('sistema')->name('sistema.')->group(function () {
        Route::get('/configuracion', \App\Livewire\Sistema\ConfiguracionSistemaComponent::class)
            ->name('configuracion')->middleware('can:' . Permisos::SISTEMA_CONFIGURACION);
        Route::view('/usuarios', 'livewire.sistema.usuarios-indice')
            ->name('usuarios')->middleware('can:' . Permisos::SISTEMA_USUARIO);
        Route::get('/roles', RolesPermisosFormComponent::class)
            ->name('roles')->middleware('can:' . Permisos::SISTEMA_ROL);
        Route::get('/roles/{rol}/permisos', PermisosRolComponent::class)
            ->name('roles.permisos')->middleware('can:' . Permisos::SISTEMA_ROL_GESTIONAR);
    });

    // ================================================================ PLANILLA
    Route::prefix('planilla')->name('planilla.')->group(function () {
        Route::view('/empleados', 'livewire.planilla.empleado.indice-empleados')
            ->name('empleados')->middleware('can:Planilla Empleados');
        Route::view('/contratos/{id?}', 'livewire.planilla.empleado.contratos-planilla-indice', ['id' => null])
            ->name('contratos')->middleware('can:' . Permisos::PERSONAL_CONTRATOS);
        Route::get('/panel-contrato', PanelContratoComponent::class)
            ->name('panel_contrato')->middleware('can:' . Permisos::PERSONAL_CONTRATOS);
        Route::get('/cargos', CargosComponent::class)
            ->name('cargos')->middleware('can:' . Permisos::PERSONAL_CARGOS);
        Route::get('/derecho-habiente', DerechoHabienteListaComponent::class)
            ->name('derecho_habiente')->middleware('can:' . Permisos::PLANILLA_FAMILIAR);

        Route::view('/registro-diario', 'livewire.planilla.registro-diario.indice-reporte-diario-planilla')
            ->name('registro_diario')->middleware('can:' . Permisos::PLANILLA_ACTIVIDAD);
        // LEGACY: la versión antigua (GestionPlanillaAsistenciasComponent) está en legacy/; esta era la beta /asistencias
        Route::get('/asistencia/{anio?}/{mes?}', AsistenciaMensualComponent::class)
            ->name('asistencia')->middleware('can:' . Permisos::PLANILLA_ASISTENCIA);
        Route::redirect('/asistencias', '/planilla/asistencia')->name('asistencias');
        Route::view('/suspensiones', 'livewire.planilla.asistencia.suspensiones-planilla-indice')
            ->name('suspensiones')->middleware('can:' . Permisos::PLANILLA_SUSPENSION);

        Route::get('/resumen-mensual', ResumenPlanillaComponent::class)
            ->name('resumen_mensual')->middleware('can:' . Permisos::PLANILLA_RESUMEN_MENSUAL);
        Route::view('/resumen-general', 'livewire.planilla.reporte-general-index')
            ->name('resumen_general')->middleware('can:' . Permisos::PLANILLA_RESUMEN_GENERAL);
        // Planilla agraria (antes "Planilla B+N"); la ruta /bn se mantiene
        Route::view('/bn', 'livewire.planilla.planilla-blanco-indice')
            ->name('blanco')->middleware('can:' . Permisos::PLANILLA_BLANCO);
        Route::get('/oficina', PlanillaOficinaComponent::class)
            ->name('oficina')->middleware('can:' . Permisos::PLANILLA_OFICINA);

        // Configuración
        Route::view('/conceptos', 'livewire.planilla.conceptos-planilla-indice')
            ->name('conceptos')->middleware('can:' . Permisos::PLANILLA_CONCEPTO);
        Route::view('/parametros', 'livewire.planilla.parametros-planilla-indice')
            ->name('parametros')->middleware('can:' . Permisos::PLANILLA_PARAMETRO);
        Route::get('/descuentos-afp', ConfiguracionPrimasComisionesComponent::class)
            ->name('descuentos_afp')->middleware('can:' . Permisos::PLANILLA_CONFIG_AFP);
        Route::get('/tipos-asistencia', TipoAsistenciaComponent::class)
            ->name('tipos_asistencia')->middleware('can:' . Permisos::PLANILLA_CONFIG_ASISTENCIA);
        // Enlace para el programador: aún falta el formato y revisar bien los datos
        Route::view('/importar', 'livewire.planilla.importar-planilla-indice')->name('importar');
    });

    // ================================================================ CUADRILLA
    Route::prefix('cuadrilla')->name('cuadrilla.')->group(function () {
        Route::view('/panel', 'livewire.cuadrilla.panel-cuadrilleros-indice')
            ->name('panel')->middleware('can:' . Permisos::CUADRILLA_PANEL);
        Route::view('/cuadrilleros', 'livewire.cuadrilla.cuadrillero.cuadrilleros')
            ->name('cuadrilleros')->middleware('can:' . Permisos::CUADRILLA_LISTA);
        Route::view('/grupos', 'livewire.cuadrilla.cuadrillero.grupos')
            ->name('grupos')->middleware('can:' . Permisos::CUADRILLA_GRUPO);
        Route::get('/registro-diario', GestionCuadrillaReporteDiarioComponent::class)
            ->name('registro_diario')->middleware('can:' . Permisos::CUADRILLA_DIARIO);
        Route::view('/reporte-semanal', 'livewire.cuadrilla.reporte_semanal')
            ->name('reporte_semanal')->middleware('can:' . Permisos::CUADRILLA_SEMANAL);
        Route::get('/bonificaciones', GestionCuadrillaBonificacionesComponent::class)
            ->name('bonificaciones')->middleware('can:' . Permisos::CUADRILLA_BONIFICACION);
        Route::get('/resumen-general', GestionCuadrillaPagosComponent::class)
            ->name('resumen_general')->middleware('can:' . Permisos::CUADRILLA_RESUMEN_GENERAL);
        Route::get('/pagos', GestionCuadrillaPagoComponent::class)
            ->name('pagos')->middleware('can:' . Permisos::CUADRILLA_RESUMEN_GENERAL);
        Route::view('/resumen-anual', 'livewire.cuadrilla.resumen_anual')
            ->name('resumen_anual')->middleware('can:' . Permisos::CUADRILLA_RESUMEN_ANUAL);
    });

    // ================================================================ RIEGO
    Route::prefix('riego')->name('riego.')->group(function () {
        Route::get('/reporte-diario', ReporteDiarioRiegoComponent::class)
            ->name('reporte_diario')->middleware('can:' . Permisos::CAMPO_RIEGO_REPORTE);
        Route::view('/labores', 'livewire.riego.labores-riego-indice')
            ->name('labores')->middleware('can:' . Permisos::CAMPO_RIEGO_LABOR);
        Route::view('/estados', 'livewire.riego.estado-riego-indice')
            ->name('estados')->middleware('can:' . Permisos::CAMPO_RIEGO_ESTADO);
        Route::view('/resumen-diario', 'livewire.riego.resumen-diario-riego-indice')
            ->name('resumen_diario')->middleware('can:' . Permisos::CAMPO_RIEGO_RESUMEN);
    });

    // ================================================================ CAMPO
    Route::prefix('campo')->name('campo.')->group(function () {
        Route::get('/campos', CamposComponent::class)
            ->name('campos')->middleware('can:' . Permisos::CAMPO_PARCELA);
        Route::view('/labores', 'livewire.campo.labores-indice')
            ->name('labores')->middleware('can:' . Permisos::CAMPO_LABOR);
        Route::view('/siembras', 'livewire.campo.siembra-indice')
            ->name('siembras')->middleware('can:' . Permisos::CAMPO_SIEMBRA);
        Route::get('/servicios', ServiciosCampoComponent::class)
            ->name('servicios')->middleware('can:' . Permisos::CAMPO);
        Route::view('/maquinarias', 'livewire.campo.maquinarias-indice')
            ->name('maquinarias')->middleware('can:' . Permisos::CAMPO_MAQUINARIA);
    });

    // ================================================================ CAMPAÑA
    Route::prefix('campania')->name('campania.')->group(function () {
        Route::get('/resumen', CampaniasComponent::class)
            ->name('resumen')->middleware('can:' . Permisos::CAMPAÑA_RESUMEN);
        Route::get('/por-campo/{campania?}', CampaniaCampoSelectorComponent::class)
            ->name('por_campo')->middleware('can:' . Permisos::CAMPAÑA_POR_CAMPO);
        Route::view('/calendario', 'livewire.campania.campania-calendario-indice')
            ->name('calendario')->middleware('can:' . Permisos::CAMPAÑA_CALENDARIO);
    });

    // ================================================================ CAJA
    Route::prefix('caja')->name('caja.')->group(function () {
        Route::get('/movimientos', \App\Livewire\Caja\CajaMovimientosComponent::class)
            ->name('movimientos')->middleware('can:' . Permisos::CAJA_MOVIMIENTO);
        Route::get('/oficina', \App\Livewire\Caja\CajaOficinaComponent::class)
            ->name('oficina')->middleware('can:' . Permisos::CAJA_OFICINA);
        Route::get('/historial', \App\Livewire\Caja\CajaHistorialComponent::class)
            ->name('historial')->middleware('can:' . Permisos::CAJA_HISTORIAL);
    });

    // ================================================================ COSTOS
    Route::prefix('costos')->name('costos.')->group(function () {
        Route::get('/campo', CampoCostosComponent::class)->name('campo');
        Route::view('/mano-obra', 'livewire.costos.index-mano-obra')
            ->name('mano_obra')->middleware('can:' . Permisos::CAMPO_MANO_OBRA);
        Route::view('/campania/{campaniaId?}', 'livewire.costos.indice-costos', ['campaniaId' => null])
            ->name('campania')->middleware('can:' . Permisos::CAMPAÑA_COSTOS);
        Route::get('/mensual', ContabilidadCostosMensualesComponent::class)
            ->name('mensual')->middleware('can:' . Permisos::CONTABILIDAD_COSTO_MENSUAL);
        Route::view('/mensuales', 'livewire.costos.costos-mensuales')
            ->name('mensuales')->middleware('can:' . Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA);
        Route::get('/fdm', FdmComponent::class)
            ->name('fdm')->middleware('can:' . Permisos::CONTABILIDAD_FDM);
    });

    // ================================================================ COCHINILLA
    Route::prefix('cochinilla')->name('cochinilla.')->group(function () {
        Route::view('/ingreso', 'livewire.cochinilla.cochinilla-ingreso-indice')
            ->name('ingreso')->middleware('can:' . Permisos::COCHINILLA_INGRESO);
        Route::view('/venteado', 'livewire.cochinilla.cochinilla-venteado-indice')
            ->name('venteado')->middleware('can:' . Permisos::COCHINILLA_VENTEADO);
        Route::view('/filtrado', 'livewire.cochinilla.cochinilla-filtrado-indice')
            ->name('filtrado')->middleware('can:' . Permisos::COCHINILLA_FILTRADO);
        Route::view('/cosecha-mamas', 'livewire.cochinilla.cosecha-mamas-indice')
            ->name('cosecha_mamas')->middleware('can:' . Permisos::COCHINILLA_COSECHA);
        Route::view('/infestacion', 'livewire.cochinilla.cochinilla-infestacion-indice')
            ->name('infestacion')->middleware('can:' . Permisos::COCHINILLA_INFESTACION);
        Route::get('/ventas', CochinillaVentasComponent::class)
            ->name('ventas')->middleware('can:' . Permisos::COCHINILLA_VENTA);
    });

    // ================================================================ EVALUACIÓN
    Route::prefix('evaluacion')->name('evaluacion.')->group(function () {
        Route::view('/poblacion-plantas', 'livewire.evaluacion.evaluacion-poblacion-plantas')
            ->name('poblacion_plantas')->middleware('can:' . Permisos::PLANTA_EVALUACION);
        Route::view('/brotes/{campaniaId?}', 'livewire.evaluacion.evaluacion_brotes', ['campaniaId' => null])
            ->name('brotes')->middleware('can:' . Permisos::BROTE_EVALUACION);
        Route::get('/infestacion-cosecha', EvaluacionInfestacionCosechaComponent::class)
            ->name('infestacion_cosecha')->middleware('can:' . Permisos::INFESTACION_EVALUACION);
        Route::get('/proyeccion-rendimiento-poda', ProyeccionRendimientoPodaComponent::class)
            ->name('proyeccion_rendimiento_poda')->middleware('can:' . Permisos::PROYECCION_EVALUACION);
    });

    // ================================================================ PRODUCTO
    Route::prefix('productos')->name('producto.')->group(function () {
        Route::view('/', 'livewire.producto.productos-indice')
            ->name('index')->middleware('can:' . Permisos::INSUMO_PRODUCTO);
        Route::get('/usos', UsosComponent::class)
            ->name('usos')->middleware('can:' . Permisos::INSUMO_USO);
        Route::get('/categorias', CategoriasComponent::class)
            ->name('categorias')->middleware('can:' . Permisos::INSUMO_CATEGORIA);
        Route::get('/subcategorias', SubcategoriasComponent::class)
            ->name('subcategorias')->middleware('can:' . Permisos::INSUMO_SUBCATEGORIA);
        Route::get('/nutrientes', NutrientesComponent::class)
            ->name('nutrientes')->middleware('can:' . Permisos::INSUMO_NUTRIENTE);
        Route::get('/tabla-concentracion', TablaConcentracionComponent::class)
            ->name('tabla_concentracion')->middleware('can:' . Permisos::INSUMO_CONCENTRACION);
    });

    // ================================================================ ALMACÉN
    Route::prefix('almacen')->name('almacen.')->group(function () {
        Route::get('/proveedores', ProveedoresComponent::class)
            ->name('proveedores')->middleware('can:' . Permisos::INSUMO_PROVEEDOR);
        Route::get('/compras/{producto_id?}', ListaComprasComponent::class)
            ->name('compras')->middleware('can:' . Permisos::INSUMO_COMPRA);
        Route::view('/salida-productos', 'livewire.almacen.salida-productos-indice')
            ->name('salida_productos')->middleware('can:' . Permisos::INSUMO_SALIDA);
        Route::view('/salida-combustible', 'livewire.almacen.salida-combustible-indice')
            ->name('salida_combustible')->middleware('can:' . Permisos::INSUMO_COMBUSTIBLE);
        Route::view('/distribucion-combustible', 'livewire.almacen.distribucion-combustible')
            ->name('distribucion_combustible')->middleware('can:' . Permisos::INSUMO_DISTRIBUCION);

        Route::prefix('kardex')->name('kardex')->group(function () {
            Route::get('/', InsumoKardexComponent::class)
                ->middleware('can:' . Permisos::INSUMO_KARDEX);
            Route::get('/crear', InsumoKardexCrearComponent::class)
                ->name('.crear')->middleware('can:' . Permisos::INSUMO_KARDEX);
            Route::get('/carga-anual', KardexCargaComponent::class)
                ->name('.carga')->middleware('can:' . Permisos::INSUMO_KARDEX);
            Route::get('/detalle/{insumoKardexId}', InsumoKardexDetalleComponent::class)
                ->name('.detalle')->middleware('can:' . Permisos::INSUMO_KARDEX);
            Route::get('/reportes', InsumoKardexReporteComponent::class)
                ->name('.reportes')->middleware('can:' . Permisos::INSUMO_KARDEX_REPORTE);
            Route::get('/reportes/{insumoKardexReporteId}', InsumoKardexReporteDetalleComponent::class)
                ->name('.reporte')->middleware('can:' . Permisos::INSUMO_KARDEX_REPORTE_VER);
        });
    });

    // ================================================================ REPORTE
    Route::prefix('reporte')->name('reporte.')->group(function () {
        Route::get('/diario', ReporteDiarioComponent::class)
            ->name('diario')->middleware('can:' . Permisos::REPORTE_DIARIO);
        Route::get('/mensual', ReporteMensualComponent::class)
            ->name('mensual')->middleware('can:' . Permisos::REPORTE_MENSUAL);
        Route::get('/anual', ReporteAnualComponent::class)
            ->name('anual')->middleware('can:' . Permisos::REPORTE_ANUAL);
        Route::get('/auditoria', AuditoriaComponent::class)
            ->name('auditoria')->middleware('can:' . Permisos::REPORTE_AUDITORIA);
    });

    // ================================================================ URL ANTIGUAS
    // Favoritos y enlaces guardados (ej. tareas pendientes) con las URL de antes de la
    // reorganización por dominios: redirigen (301) conservando parámetros y query string.
    $urlsAntiguas = [
        '/gestion-usuario/usuarios' => 'sistema.usuarios',
        '/gestion-usuario/roles-y-permisos' => 'sistema.roles',
        '/gestion-usuario/permisos-rol/{rol}' => 'sistema.roles.permisos',
        '/empleados' => 'planilla.empleados',
        '/planilla/panel_contrato' => 'planilla.panel_contrato',
        '/reporte/reporte-diario' => 'planilla.registro_diario',
        '/planilla/gestion_planilla/reporte_general' => 'planilla.resumen_general',
        '/descuentos-de-afp' => 'planilla.descuentos_afp',
        '/configuracion/tipos-asistencias' => 'planilla.tipos_asistencia',
        '/cuadrilla/gestion_cuadrilleros' => 'cuadrilla.panel',
        '/cuadrilla/gestion_cuadrilleros/registro-diario' => 'cuadrilla.registro_diario',
        '/cuadrilla/gestion_cuadrilleros/reporte-semanal' => 'cuadrilla.reporte_semanal',
        '/cuadrilla/gestion_cuadrilleros/bonificaciones' => 'cuadrilla.bonificaciones',
        '/cuadrilla/gestion_cuadrilleros/resumen-general' => 'cuadrilla.resumen_general',
        '/cuadrilla/gestion_cuadrilleros/pagos' => 'cuadrilla.pagos',
        '/cuadrilla/gestion_cuadrilleros/resumen_anual' => 'cuadrilla.resumen_anual',
        '/maquinarias' => 'campo.maquinarias',
        '/campanias' => 'campania.resumen',
        '/campanias_x_campo/{campania?}' => 'campania.por_campo',
        '/campo/costos' => 'costos.campo',
        '/campo/mano_obra' => 'costos.mano_obra',
        '/campania/costos/{campaniaId?}' => 'costos.campania',
        '/contabilidad/costo_mensual' => 'costos.mensual',
        '/contabilidad/costos_mensuales' => 'costos.mensuales',
        '/fdm/costos_generales' => 'costos.fdm',
        '/cochinilla/cosechamamas' => 'cochinilla.cosecha_mamas',
        '/evaluacion_campo/poblacion_planta' => 'evaluacion.poblacion_plantas',
        '/evaluacion_campo/evaluacion_brotes/{campaniaId?}' => 'evaluacion.brotes',
        '/evaluacion_campo/evaluacion_infestacion_cosecha' => 'evaluacion.infestacion_cosecha',
        '/evaluacion-campo/proyeccion-rendimiento-poda' => 'evaluacion.proyeccion_rendimiento_poda',
        '/producto/usos' => 'producto.usos',
        '/categorias/categorias' => 'producto.categorias',
        '/categorias/subcategorias' => 'producto.subcategorias',
        '/nutrientes' => 'producto.nutrientes',
        '/nutrientes/tabla-concentracion' => 'producto.tabla_concentracion',
        '/proveedores' => 'almacen.proveedores',
        '/almacen/salida_de_productos' => 'almacen.salida_productos',
        '/almacen/salida_de_combustible' => 'almacen.salida_combustible',
        '/almacen/distribucion_combustible' => 'almacen.distribucion_combustible',
        '/gestion_insumos/kardex' => 'almacen.kardex',
        '/gestion_insumos/kardex/crear' => 'almacen.kardex.crear',
        '/kardex/detalle/{insumoKardexId}' => 'almacen.kardex.detalle',
        '/gestion_insumos/kardex/reportes' => 'almacen.kardex.reportes',
        '/gestion_insumos/kardex/reporte/{insumoKardexReporteId}' => 'almacen.kardex.reporte',
        '/reporte-general/reporte-diario' => 'reporte.diario',
        '/reporte-general/reporte-mensual' => 'reporte.mensual',
        '/reporte-general/reporte-anual' => 'reporte.anual',
        '/auditoria' => 'reporte.auditoria',
    ];
    foreach ($urlsAntiguas as $uri => $nombre) {
        Route::get($uri, function (Request $request) use ($nombre) {
            $url = route($nombre, array_filter($request->route()->parameters(), fn($v) => $v !== null));
            $query = $request->getQueryString();
            return redirect()->to($query ? "{$url}?{$query}" : $url, 301);
        });
    }
});
