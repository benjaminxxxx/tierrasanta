<?php

namespace App\View\Components\Layouts;

use App\Constants\Permisos;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public array $menu = [];

    // Permisos/secciones que Administrador NO puede ver.
    // Super Admin sí los ve siempre.
    // Agrega aquí el 'can' del ítem que quieras excluir.
    private const EXCEPCIONES_ADMINISTRADOR = [
        'Auditoria Ver',
        // 'Configuración Parámetros Ver',  // ejemplo: si quisieras ocultarle parámetros
    ];

    public function __construct()
    {
        $this->menu = $this->buildMenu();

    }

    /**
     * Menú por flujo de trabajo (7 grupos). Cada opción tiene su permiso ('can') y el del módulo de donde viene
     * ('modulo'): para los roles comunes se piden los dos, así nadie ve más que antes. Un grupo no tiene permiso
     * propio: aparece si el usuario ve alguna de sus opciones. 'seccion' agrupa las opciones dentro del grupo.
     * Catálogos = lo que se carga con los Excel (recursos, insumos, empleados).
     */
    private function buildMenu(): array
    {
        $op = fn(string $seccion, string $title, string $route, ?string $can, ?string $modulo = null) =>
            array_filter(compact('seccion', 'title', 'route', 'can', 'modulo'), fn($v) => $v !== null);

        $rawMenu = [
            [
                'title' => 'Personal',
                'icon' => 'fa fa-users',
                'children' => [
                    $op('Registro diario', 'Actividades diarias', 'planilla.registro_diario', Permisos::PLANILLA_ACTIVIDAD, Permisos::PLANILLA),
                    $op('Registro diario', 'Cuadrilla: actividades del día', 'cuadrilla.registro_diario', Permisos::CUADRILLA_DIARIO, Permisos::CUADRILLA),
                    $op('Registro diario', 'Cuadrilla: horas de la semana', 'cuadrilla.reporte_semanal', Permisos::CUADRILLA_SEMANAL, Permisos::CUADRILLA),
                    $op('Asistencia', 'Asistencia mensual', 'planilla.asistencia', Permisos::PLANILLA_ASISTENCIA, Permisos::PLANILLA),
                    $op('Asistencia', 'Permisos y suspensiones', 'planilla.suspensiones', Permisos::PLANILLA_SUSPENSION, Permisos::PLANILLA),
                    $op('Planillas', 'Planilla agraria', 'planilla.blanco', Permisos::PLANILLA_BLANCO, Permisos::PLANILLA),
                    $op('Planillas', 'Planilla oficina', 'planilla.oficina', Permisos::PLANILLA_OFICINA, Permisos::PLANILLA),
                    $op('Planillas', 'Resumen mensual', 'planilla.resumen_mensual', Permisos::PLANILLA_RESUMEN_MENSUAL, Permisos::PLANILLA),
                    $op('Planillas', 'Resumen general', 'planilla.resumen_general', Permisos::PLANILLA_RESUMEN_GENERAL, Permisos::PLANILLA),
                    $op('Cuadrilla', 'Panel de cuadrilleros', 'cuadrilla.panel', Permisos::CUADRILLA_PANEL, Permisos::CUADRILLA),
                    $op('Cuadrilla', 'Lista de cuadrilleros', 'cuadrilla.cuadrilleros', Permisos::CUADRILLA_LISTA, Permisos::CUADRILLA),
                    $op('Cuadrilla', 'Bonificaciones', 'cuadrilla.bonificaciones', Permisos::CUADRILLA_BONIFICACION, Permisos::CUADRILLA),
                    $op('Cuadrilla', 'Pagos (desglose)', 'cuadrilla.pagos', Permisos::CUADRILLA, Permisos::CUADRILLA),
                    $op('Cuadrilla', 'Resumen general de cuadrilla', 'cuadrilla.resumen_general', Permisos::CUADRILLA_RESUMEN_GENERAL, Permisos::CUADRILLA),
                    $op('Cuadrilla', 'Resumen anual de cuadrilla', 'cuadrilla.resumen_anual', Permisos::CUADRILLA_RESUMEN_ANUAL, Permisos::CUADRILLA),
                    $op('Empleados', 'Empleados', 'planilla.empleados', Permisos::PERSONAL, Permisos::PLANILLA),
                    $op('Empleados', 'Contratos', 'planilla.panel_contrato', Permisos::PERSONAL_CONTRATOS, Permisos::PLANILLA),
                    $op('Empleados', 'Derecho habientes', 'planilla.derecho_habiente', Permisos::PLANILLA_FAMILIAR, Permisos::PLANILLA),
                ],
            ],
            [
                'title' => 'Producción',
                'icon' => 'fa fa-seedling',
                'children' => [
                    $op('Campañas', 'Campañas', 'campania.resumen', Permisos::CAMPAÑA_RESUMEN, Permisos::CAMPAÑA),
                    $op('Campañas', 'Campañas por campo', 'campania.por_campo', Permisos::CAMPAÑA_POR_CAMPO, Permisos::CAMPAÑA),
                    $op('Campañas', 'Informes mensuales', 'campania.informes_mensuales', Permisos::CAMPAÑA_INFORME_MENSUAL, Permisos::CAMPAÑA),
                    $op('Cochinilla', 'Ingreso', 'cochinilla.ingreso', Permisos::COCHINILLA_INGRESO, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Venteado', 'cochinilla.venteado', Permisos::COCHINILLA_VENTEADO, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Filtrado', 'cochinilla.filtrado', Permisos::COCHINILLA_FILTRADO, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Cosecha mamás', 'cochinilla.cosecha_mamas', Permisos::COCHINILLA_COSECHA, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Infestación', 'cochinilla.infestacion', Permisos::COCHINILLA_INFESTACION, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Por recuperar', 'cochinilla.recuperacion', Permisos::COCHINILLA_RECUPERACION, Permisos::COCHINILLA),
                    $op('Cochinilla', 'Venta', 'cochinilla.ventas', Permisos::COCHINILLA_VENTA, Permisos::COCHINILLA),
                    $op('Evaluaciones', 'Población de plantas', 'evaluacion.poblacion_plantas', Permisos::PLANTA_EVALUACION, Permisos::EVALUACION),
                    $op('Evaluaciones', 'Brotes por piso', 'evaluacion.brotes', Permisos::BROTE_EVALUACION, Permisos::EVALUACION),
                    $op('Evaluaciones', 'Infestación a la cosecha', 'evaluacion.infestacion_cosecha', Permisos::INFESTACION_EVALUACION, Permisos::EVALUACION),
                    $op('Evaluaciones', 'Proyección rendimiento poda', 'evaluacion.proyeccion_rendimiento_poda', Permisos::PROYECCION_EVALUACION, Permisos::EVALUACION),
                    $op('Riego', 'Reporte diario de regadores', 'riego.reporte_diario', Permisos::CAMPO_RIEGO_REPORTE, Permisos::CAMPO_RIEGO),
                    $op('Riego', 'Estado de riegos', 'riego.estados', Permisos::CAMPO_RIEGO_ESTADO, Permisos::CAMPO_RIEGO),
                    $op('Riego', 'Resumen diario de riegos', 'riego.resumen_diario', Permisos::CAMPO_RIEGO_RESUMEN, Permisos::CAMPO_RIEGO),
                    $op('Campo', 'Siembras', 'campo.siembras', Permisos::CAMPO_SIEMBRA, Permisos::CAMPO),
                    $op('Campo', 'Servicios en campo', 'campo.servicios', Permisos::CAMPO, Permisos::CAMPO),
                ],
            ],
            [
                'title' => 'Almacén',
                'icon' => 'fa fa-warehouse',
                'children' => [
                    $op('Movimientos', 'Entradas (compras)', 'almacen.compras', Permisos::INSUMO_COMPRA, Permisos::INSUMO),
                    $op('Movimientos', 'Salidas: pesticidas y fertilizantes', 'almacen.salida_productos', Permisos::INSUMO_SALIDA, Permisos::INSUMO),
                    $op('Movimientos', 'Salidas: combustible', 'almacen.salida_combustible', Permisos::INSUMO_COMBUSTIBLE, Permisos::INSUMO),
                    $op('Movimientos', 'Distribución de combustible', 'almacen.distribucion_combustible', Permisos::INSUMO_DISTRIBUCION, Permisos::INSUMO),
                    $op('Kardex', 'Kardex de insumos', 'almacen.kardex', Permisos::INSUMO_KARDEX, Permisos::INSUMO),
                    $op('Kardex', 'Kardex por insumo', 'almacen.kardex.crear', Permisos::INSUMO_KARDEX, Permisos::INSUMO),
                    $op('Kardex', 'Carga de kardex anual', 'almacen.kardex.carga', Permisos::INSUMO_KARDEX, Permisos::INSUMO),
                    $op('Kardex', 'Reporte de kardex', 'almacen.kardex.reportes', Permisos::INSUMO_KARDEX_REPORTE, Permisos::INSUMO),
                ],
            ],
            [
                'title' => 'Costos y caja',
                'icon' => 'fa fa-calculator',
                'children' => [
                    $op('Costos', 'Costos (BDD)', 'costos.campo', null),
                    $op('Costos', 'Costos mensuales', 'costos.mensuales', Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA, Permisos::CONTABILIDAD),
                    $op('Costos', 'Costo mensual', 'costos.mensual', Permisos::CONTABILIDAD_COSTO_MENSUAL, Permisos::CONTABILIDAD),
                    $op('Costos', 'Mano de obra indirecta', 'costos.mano_obra_indirecta', Permisos::CONTABILIDAD_COSTO_MENSUAL, Permisos::CONTABILIDAD),
                    $op('Costos', 'Costos por campaña', 'costos.campania', Permisos::CAMPAÑA_COSTOS, Permisos::CONTABILIDAD),
                    $op('Caja', 'Movimientos de caja', 'caja.movimientos', Permisos::CAJA_MOVIMIENTO, Permisos::CAJA),
                    $op('Caja', 'Caja de oficina', 'caja.oficina', Permisos::CAJA_OFICINA, Permisos::CAJA),
                    $op('Caja', 'Historial de caja', 'caja.historial', Permisos::CAJA_HISTORIAL, Permisos::CAJA),
                ],
            ],
            [
                'title' => 'Reportes',
                'icon' => 'fa fa-chart-line',
                'children' => [
                    $op('Reportes', 'Reporte diario', 'reporte.diario', Permisos::REPORTE_DIARIO, Permisos::REPORTE),
                    $op('Reportes', 'Reporte mensual', 'reporte.mensual', Permisos::REPORTE_MENSUAL, Permisos::REPORTE),
                    $op('Reportes', 'Reporte anual', 'reporte.anual', Permisos::REPORTE_ANUAL, Permisos::REPORTE),
                    $op('Reportes', 'Historial laboral', 'reporte.historial_laboral', Permisos::REPORTE_HISTORIAL_LABORAL, Permisos::REPORTE),
                    $op('Reportes', 'Auditoría', 'reporte.auditoria', Permisos::REPORTE_AUDITORIA, Permisos::REPORTE),
                ],
            ],
            [
                'title' => 'Catálogos',
                'icon' => 'fa fa-book',
                'children' => [
                    $op('Campo', 'Campos', 'campo.campos', Permisos::CAMPO_PARCELA, Permisos::CAMPO),
                    $op('Campo', 'Labores', 'campo.labores', Permisos::CAMPO_LABOR, Permisos::CAMPO),
                    $op('Campo', 'Mano de obra', 'costos.mano_obra', Permisos::CAMPO_MANO_OBRA, Permisos::CAMPO),
                    $op('Campo', 'Labores de riego', 'riego.labores', Permisos::CAMPO_RIEGO_LABOR, Permisos::CAMPO_RIEGO),
                    $op('Campo', 'Maquinarias', 'campo.maquinarias', Permisos::CAMPO_MAQUINARIA, Permisos::CAMPO),
                    $op('Insumos', 'Productos', 'producto.index', Permisos::INSUMO_PRODUCTO, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Categorías', 'producto.categorias', Permisos::INSUMO_CATEGORIA, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Subcategorías', 'producto.subcategorias', Permisos::INSUMO_SUBCATEGORIA, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Usos', 'producto.usos', Permisos::INSUMO_USO, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Nutrientes', 'producto.nutrientes', Permisos::INSUMO_NUTRIENTE, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Tabla de concentración', 'producto.tabla_concentracion', Permisos::INSUMO_CONCENTRACION, Permisos::INSUMO_CATALOGO),
                    $op('Insumos', 'Proveedores', 'almacen.proveedores', Permisos::INSUMO_PROVEEDOR, Permisos::INSUMO_CATALOGO),
                    $op('Personal', 'Cargos', 'planilla.cargos', Permisos::PERSONAL_CARGOS, Permisos::PLANILLA),
                    $op('Personal', 'Conceptos', 'planilla.conceptos', Permisos::PLANILLA_CONCEPTO, Permisos::PLANILLA),
                    $op('Personal', 'Parámetros', 'planilla.parametros', Permisos::PLANILLA_PARAMETRO, Permisos::PLANILLA),
                    $op('Personal', 'Descuentos de AFP', 'planilla.descuentos_afp', Permisos::PLANILLA_CONFIG_AFP, Permisos::PLANILLA),
                    $op('Personal', 'Tipos de asistencia', 'planilla.tipos_asistencia', Permisos::PLANILLA_CONFIG_ASISTENCIA, Permisos::PLANILLA),
                    $op('Personal', 'Grupos de cuadrilla', 'cuadrilla.grupos', Permisos::CUADRILLA_GRUPO, Permisos::CUADRILLA),
                ],
            ],
            [
                'title' => 'Sistema',
                'icon' => 'fa fa-gear',
                'children' => [
                    $op('Sistema', 'Usuarios', 'sistema.usuarios', Permisos::SISTEMA_USUARIO, Permisos::SISTEMA),
                    $op('Sistema', 'Roles y permisos', 'sistema.roles', Permisos::SISTEMA_ROL, Permisos::SISTEMA),
                    $op('Sistema', 'Configuración', 'sistema.configuracion', Permisos::SISTEMA_CONFIGURACION, Permisos::SISTEMA),
                    $op('Sistema', 'Errores', 'sistema.errores', Permisos::SISTEMA_ERRORES, Permisos::SISTEMA),
                ],
            ],
        ];

        return $this->filterMenu($rawMenu);
    }

    /**
     * Decide si el usuario actual puede ver una opción del menú.
     *  1. Super Admin  → siempre.
     *  2. Administrador → siempre, salvo que el 'can' de la opción esté en EXCEPCIONES_ADMINISTRADOR.
     *  3. Otro rol → necesita el 'can' de la opción y el de su módulo ('modulo'), como cuando cada módulo era un grupo.
     */
    private function puedeVer(array $item): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {
            return true;
        }
        if ($user->hasRole('Administrador')) {
            return !isset($item['can']) || !array_intersect((array) $item['can'], self::EXCEPCIONES_ADMINISTRADOR);
        }
        if (!isset($item['can'])) {
            return false;
        }
        return $user->can($item['can']) && (!isset($item['modulo']) || $user->can($item['modulo']));
    }

    /** Grupos con sus opciones visibles; un grupo sin ninguna opción visible no aparece. */
    private function filterMenu(array $items): array
    {
        $actual = Route::currentRouteName();

        return collect($items)
            ->map(function ($grupo) use ($actual) {
                $grupo['children'] = collect($grupo['children'])
                    ->filter(fn($op) => Route::has($op['route']) && $this->puedeVer($op))
                    ->map(fn($op) => $op + ['url' => route($op['route']), 'isActive' => $actual === $op['route']])
                    ->values()
                    ->all();
                $grupo['isActive'] = collect($grupo['children'])->contains('isActive', true);
                return $grupo;
            })
            ->filter(fn($grupo) => $grupo['children'])
            ->values()
            ->all();
    }
    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }
}