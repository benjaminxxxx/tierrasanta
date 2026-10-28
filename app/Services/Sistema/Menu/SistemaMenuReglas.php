<?php

namespace App\Services\Sistema\Menu;

use App\Constants\Permisos;

/**
 * Definición del menú lateral (sin base de datos). Cada grupo tiene ícono y color (estilo explorador de archivos);
 * cada opción: sección, título, ruta, permiso ('can') y permiso del módulo ('modulo'), e ícono propio.
 * Para los roles comunes se piden los dos permisos (SistemaMenuConsulta), así nadie ve más que antes.
 */
class SistemaMenuReglas
{
    /** Accesos directos de un usuario que todavía no personalizó los suyos (lo que se llena todos los días) */
    public const ACCESOS_INICIALES = [
        'planilla.registro_diario', 'planilla.suspensiones', 'cuadrilla.registro_diario', 'riego.reporte_diario',
        'almacen.compras', 'almacen.salida_productos', 'almacen.salida_combustible',
    ];

    public const MAX_ACCESOS = 20;

    /** Color del ícono de cada grupo: [claro, oscuro] */
    public const COLORES = [
        'inicio' => ['#E8833A', '#F09A55'],
        'planilla' => ['#2A78D6', '#5A9BEA'],
        'cuadrilla' => ['#0F9B8E', '#2CC1B2'],
        'riego' => ['#0E9AC4', '#3BBEE3'],
        'produccion' => ['#1BAF7A', '#3CCF95'],
        'almacen' => ['#D4900F', '#F0B847'],
        'costos' => ['#7C5CD6', '#9B82EA'],
        'reportes' => ['#D6456B', '#EE6F8F'],
        'catalogos' => ['#C98A16', '#E5AC3E'],
        'sistema' => ['#6F737B', '#9AA1AA'],
    ];

    /** @return array<int, array{clave:string, title:string, icon:string, children: array}> */
    public static function grupos(): array
    {
        $op = fn(string $seccion, string $title, string $route, ?string $can, ?string $modulo, string $icon) =>
            array_filter(compact('seccion', 'title', 'route', 'can', 'modulo', 'icon'), fn($v) => $v !== null);

        return [
            ['clave' => 'planilla', 'title' => 'Planilla', 'icon' => 'fa fa-id-card', 'children' => [
                $op('Registro diario', 'Planilla registros diarios', 'planilla.registro_diario', Permisos::PLANILLA_ACTIVIDAD, Permisos::PLANILLA, 'fa fa-calendar-day'),
                $op('Asistencia', 'Asistencia mensual', 'planilla.asistencia', Permisos::PLANILLA_ASISTENCIA, Permisos::PLANILLA, 'fa fa-calendar-check'),
                $op('Asistencia', 'Permisos y suspensiones', 'planilla.suspensiones', Permisos::PLANILLA_SUSPENSION, Permisos::PLANILLA, 'fa fa-umbrella-beach'),
                $op('Planillas', 'Planilla agraria', 'planilla.blanco', Permisos::PLANILLA_BLANCO, Permisos::PLANILLA, 'fa fa-file-invoice-dollar'),
                $op('Planillas', 'Planilla oficina', 'planilla.oficina', Permisos::PLANILLA_OFICINA, Permisos::PLANILLA, 'fa fa-briefcase'),
                $op('Planillas', 'Resumen mensual', 'planilla.resumen_mensual', Permisos::PLANILLA_RESUMEN_MENSUAL, Permisos::PLANILLA, 'fa fa-table'),
                $op('Planillas', 'Resumen general', 'planilla.resumen_general', Permisos::PLANILLA_RESUMEN_GENERAL, Permisos::PLANILLA, 'fa fa-table-list'),
                $op('Empleados', 'Empleados', 'planilla.empleados', Permisos::PERSONAL, Permisos::PLANILLA, 'fa fa-user'),
                $op('Empleados', 'Contratos', 'planilla.panel_contrato', Permisos::PERSONAL_CONTRATOS, Permisos::PLANILLA, 'fa fa-file-signature'),
                $op('Empleados', 'Derecho habientes', 'planilla.derecho_habiente', Permisos::PLANILLA_FAMILIAR, Permisos::PLANILLA, 'fa fa-people-roof'),
            ]],
            ['clave' => 'cuadrilla', 'title' => 'Cuadrilla', 'icon' => 'fa fa-people-group', 'children' => [
                $op('Registro', 'Cuadrilla registros diarios', 'cuadrilla.registro_diario', Permisos::CUADRILLA_DIARIO, Permisos::CUADRILLA, 'fa fa-calendar-day'),
                $op('Registro', 'Horas de la semana', 'cuadrilla.reporte_semanal', Permisos::CUADRILLA_SEMANAL, Permisos::CUADRILLA, 'fa fa-calendar-week'),
                $op('Gestión', 'Panel de cuadrilleros', 'cuadrilla.panel', Permisos::CUADRILLA_PANEL, Permisos::CUADRILLA, 'fa fa-gauge'),
                $op('Gestión', 'Lista de cuadrilleros', 'cuadrilla.cuadrilleros', Permisos::CUADRILLA_LISTA, Permisos::CUADRILLA, 'fa fa-address-book'),
                $op('Gestión', 'Bonificaciones', 'cuadrilla.bonificaciones', Permisos::CUADRILLA_BONIFICACION, Permisos::CUADRILLA, 'fa fa-award'),
                $op('Gestión', 'Pagos (desglose)', 'cuadrilla.pagos', Permisos::CUADRILLA, Permisos::CUADRILLA, 'fa fa-money-bill-wave'),
                $op('Resúmenes', 'Resumen general', 'cuadrilla.resumen_general', Permisos::CUADRILLA_RESUMEN_GENERAL, Permisos::CUADRILLA, 'fa fa-table-list'),
                $op('Resúmenes', 'Resumen anual', 'cuadrilla.resumen_anual', Permisos::CUADRILLA_RESUMEN_ANUAL, Permisos::CUADRILLA, 'fa fa-calendar'),
            ]],
            // Riego vuelve a ser grupo propio (pedido de planilla: es su trabajo diario, no "producción")
            ['clave' => 'riego', 'title' => 'Riego', 'icon' => 'fa fa-droplet', 'children' => [
                $op('Registro', 'Reporte diario de regadores', 'riego.reporte_diario', Permisos::CAMPO_RIEGO_REPORTE, Permisos::CAMPO_RIEGO, 'fa fa-droplet'),
                $op('Seguimiento', 'Estado de riegos', 'riego.estados', Permisos::CAMPO_RIEGO_ESTADO, Permisos::CAMPO_RIEGO, 'fa fa-water'),
                $op('Seguimiento', 'Resumen diario de riegos', 'riego.resumen_diario', Permisos::CAMPO_RIEGO_RESUMEN, Permisos::CAMPO_RIEGO, 'fa fa-list-check'),
                $op('Catálogo', 'Labores de riego', 'riego.labores', Permisos::CAMPO_RIEGO_LABOR, Permisos::CAMPO_RIEGO, 'fa fa-list-ul'),
            ]],
            ['clave' => 'produccion', 'title' => 'Producción', 'icon' => 'fa fa-seedling', 'children' => [
                $op('Campañas', 'Campañas', 'campania.resumen', Permisos::CAMPAÑA_RESUMEN, Permisos::CAMPAÑA, 'fa fa-flag'),
                $op('Campañas', 'Campañas por campo', 'campania.por_campo', Permisos::CAMPAÑA_POR_CAMPO, Permisos::CAMPAÑA, 'fa fa-map'),
                $op('Campañas', 'Informes mensuales', 'campania.informes_mensuales', Permisos::CAMPAÑA_INFORME_MENSUAL, Permisos::CAMPAÑA, 'fa fa-file-lines'),
                $op('Cochinilla', 'Ingreso', 'cochinilla.ingreso', Permisos::COCHINILLA_INGRESO, Permisos::COCHINILLA, 'fa fa-right-to-bracket'),
                $op('Cochinilla', 'Venteado', 'cochinilla.venteado', Permisos::COCHINILLA_VENTEADO, Permisos::COCHINILLA, 'fa fa-wind'),
                $op('Cochinilla', 'Filtrado', 'cochinilla.filtrado', Permisos::COCHINILLA_FILTRADO, Permisos::COCHINILLA, 'fa fa-filter'),
                $op('Cochinilla', 'Cosecha mamás', 'cochinilla.cosecha_mamas', Permisos::COCHINILLA_COSECHA, Permisos::COCHINILLA, 'fa fa-basket-shopping'),
                $op('Cochinilla', 'Infestación', 'cochinilla.infestacion', Permisos::COCHINILLA_INFESTACION, Permisos::COCHINILLA, 'fa fa-bug'),
                $op('Cochinilla', 'Por recuperar', 'cochinilla.recuperacion', Permisos::COCHINILLA_RECUPERACION, Permisos::COCHINILLA, 'fa fa-rotate-left'),
                $op('Cochinilla', 'Venta', 'cochinilla.ventas', Permisos::COCHINILLA_VENTA, Permisos::COCHINILLA, 'fa fa-hand-holding-dollar'),
                $op('Evaluaciones', 'Población de plantas', 'evaluacion.poblacion_plantas', Permisos::PLANTA_EVALUACION, Permisos::EVALUACION, 'fa fa-leaf'),
                $op('Evaluaciones', 'Brotes por piso', 'evaluacion.brotes', Permisos::BROTE_EVALUACION, Permisos::EVALUACION, 'fa fa-seedling'),
                $op('Evaluaciones', 'Infestación a la cosecha', 'evaluacion.infestacion_cosecha', Permisos::INFESTACION_EVALUACION, Permisos::EVALUACION, 'fa fa-microscope'),
                $op('Evaluaciones', 'Proyección rendimiento poda', 'evaluacion.proyeccion_rendimiento_poda', Permisos::PROYECCION_EVALUACION, Permisos::EVALUACION, 'fa fa-chart-line'),
                $op('Campo', 'Producción de campo', 'campo.produccion', Permisos::CAMPO_PRODUCCION, Permisos::CAMPO, 'fa fa-weight-hanging'),
                $op('Campo', 'Siembras', 'campo.siembras', Permisos::CAMPO_SIEMBRA, Permisos::CAMPO, 'fa fa-spa'),
                $op('Campo', 'Servicios en campo', 'campo.servicios', Permisos::CAMPO, Permisos::CAMPO, 'fa fa-tractor'),
            ]],
            ['clave' => 'almacen', 'title' => 'Almacén', 'icon' => 'fa fa-warehouse', 'children' => [
                $op('Movimientos', 'Entradas (compras)', 'almacen.compras', Permisos::INSUMO_COMPRA, Permisos::INSUMO, 'fa fa-cart-shopping'),
                $op('Movimientos', 'Salidas: pesticidas y fertilizantes', 'almacen.salida_productos', Permisos::INSUMO_SALIDA, Permisos::INSUMO, 'fa fa-flask'),
                $op('Movimientos', 'Salidas: combustible', 'almacen.salida_combustible', Permisos::INSUMO_COMBUSTIBLE, Permisos::INSUMO, 'fa fa-gas-pump'),
                $op('Movimientos', 'Distribución de combustible', 'almacen.distribucion_combustible', Permisos::INSUMO_DISTRIBUCION, Permisos::INSUMO, 'fa fa-share-nodes'),
                $op('Kardex', 'Kardex de insumos', 'almacen.kardex', Permisos::INSUMO_KARDEX, Permisos::INSUMO, 'fa fa-boxes-stacked'),
                $op('Kardex', 'Kardex por insumo', 'almacen.kardex.crear', Permisos::INSUMO_KARDEX, Permisos::INSUMO, 'fa fa-box'),
                $op('Kardex', 'Carga de kardex anual', 'almacen.kardex.carga', Permisos::INSUMO_KARDEX, Permisos::INSUMO, 'fa fa-upload'),
                $op('Kardex', 'Reporte de kardex', 'almacen.kardex.reportes', Permisos::INSUMO_KARDEX_REPORTE, Permisos::INSUMO, 'fa fa-file-lines'),
            ]],
            ['clave' => 'costos', 'title' => 'Costos y caja', 'icon' => 'fa fa-calculator', 'children' => [
                $op('Costos', 'Costos (BDD)', 'costos.campo', null, null, 'fa fa-database'),
                $op('Costos', 'Costos mensuales', 'costos.mensuales', Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA, Permisos::CONTABILIDAD, 'fa fa-calendar'),
                $op('Costos', 'Costo mensual', 'costos.mensual', Permisos::CONTABILIDAD_COSTO_MENSUAL, Permisos::CONTABILIDAD, 'fa fa-file-invoice'),
                $op('Costos', 'Mano de obra indirecta', 'costos.mano_obra_indirecta', Permisos::CONTABILIDAD_COSTO_MENSUAL, Permisos::CONTABILIDAD, 'fa fa-users-gear'),
                $op('Costos', 'Costos por campaña', 'costos.campania', Permisos::CAMPAÑA_COSTOS, Permisos::CONTABILIDAD, 'fa fa-flag'),
                $op('Caja', 'Movimientos de caja', 'caja.movimientos', Permisos::CAJA_MOVIMIENTO, Permisos::CAJA, 'fa fa-right-left'),
                $op('Caja', 'Caja de oficina', 'caja.oficina', Permisos::CAJA_OFICINA, Permisos::CAJA, 'fa fa-cash-register'),
                $op('Caja', 'Historial de caja', 'caja.historial', Permisos::CAJA_HISTORIAL, Permisos::CAJA, 'fa fa-clock-rotate-left'),
            ]],
            ['clave' => 'reportes', 'title' => 'Reportes', 'icon' => 'fa fa-chart-line', 'children' => [
                $op('Reportes', 'Reporte diario', 'reporte.diario', Permisos::REPORTE_DIARIO, Permisos::REPORTE, 'fa fa-calendar-day'),
                $op('Reportes', 'Reporte mensual', 'reporte.mensual', Permisos::REPORTE_MENSUAL, Permisos::REPORTE, 'fa fa-calendar'),
                $op('Reportes', 'Reporte anual', 'reporte.anual', Permisos::REPORTE_ANUAL, Permisos::REPORTE, 'fa fa-calendar-days'),
                $op('Reportes', 'Historial laboral', 'reporte.historial_laboral', Permisos::REPORTE_HISTORIAL_LABORAL, Permisos::REPORTE, 'fa fa-clock-rotate-left'),
                $op('Reportes', 'Auditoría', 'reporte.auditoria', Permisos::REPORTE_AUDITORIA, Permisos::REPORTE, 'fa fa-shield-halved'),
            ]],
            ['clave' => 'catalogos', 'title' => 'Catálogos', 'icon' => 'fa fa-folder', 'children' => [
                $op('Campo', 'Campos', 'campo.campos', Permisos::CAMPO_PARCELA, Permisos::CAMPO, 'fa fa-map-location-dot'),
                $op('Campo', 'Labores', 'campo.labores', Permisos::CAMPO_LABOR, Permisos::CAMPO, 'fa fa-list-check'),
                $op('Campo', 'Mano de obra', 'costos.mano_obra', Permisos::CAMPO_MANO_OBRA, Permisos::CAMPO, 'fa fa-helmet-safety'),
                $op('Campo', 'Maquinarias', 'campo.maquinarias', Permisos::CAMPO_MAQUINARIA, Permisos::CAMPO, 'fa fa-tractor'),
                $op('Insumos', 'Productos', 'producto.index', Permisos::INSUMO_PRODUCTO, Permisos::INSUMO_CATALOGO, 'fa fa-box'),
                $op('Insumos', 'Categorías', 'producto.categorias', Permisos::INSUMO_CATEGORIA, Permisos::INSUMO_CATALOGO, 'fa fa-tags'),
                $op('Insumos', 'Subcategorías', 'producto.subcategorias', Permisos::INSUMO_SUBCATEGORIA, Permisos::INSUMO_CATALOGO, 'fa fa-tag'),
                $op('Insumos', 'Usos', 'producto.usos', Permisos::INSUMO_USO, Permisos::INSUMO_CATALOGO, 'fa fa-hand-sparkles'),
                $op('Insumos', 'Nutrientes', 'producto.nutrientes', Permisos::INSUMO_NUTRIENTE, Permisos::INSUMO_CATALOGO, 'fa fa-atom'),
                $op('Insumos', 'Tabla de concentración', 'producto.tabla_concentracion', Permisos::INSUMO_CONCENTRACION, Permisos::INSUMO_CATALOGO, 'fa fa-table-cells'),
                $op('Insumos', 'Proveedores', 'almacen.proveedores', Permisos::INSUMO_PROVEEDOR, Permisos::INSUMO_CATALOGO, 'fa fa-truck'),
                $op('Personal', 'Cargos', 'planilla.cargos', Permisos::PERSONAL_CARGOS, Permisos::PLANILLA, 'fa fa-sitemap'),
                $op('Personal', 'Parámetros', 'planilla.parametros', Permisos::PLANILLA_PARAMETRO, Permisos::PLANILLA, 'fa fa-sliders'),
                $op('Personal', 'Descuentos de AFP', 'planilla.descuentos_afp', Permisos::PLANILLA_CONFIG_AFP, Permisos::PLANILLA, 'fa fa-percent'),
                $op('Personal', 'Tipos de asistencia', 'planilla.tipos_asistencia', Permisos::PLANILLA_CONFIG_ASISTENCIA, Permisos::PLANILLA, 'fa fa-list-ul'),
                $op('Personal', 'Grupos de cuadrilla', 'cuadrilla.grupos', Permisos::CUADRILLA_GRUPO, Permisos::CUADRILLA, 'fa fa-layer-group'),
            ]],
            ['clave' => 'sistema', 'title' => 'Sistema', 'icon' => 'fa fa-gear', 'children' => [
                $op('Sistema', 'Usuarios', 'sistema.usuarios', Permisos::SISTEMA_USUARIO, Permisos::SISTEMA, 'fa fa-user-gear'),
                $op('Sistema', 'Roles y permisos', 'sistema.roles', Permisos::SISTEMA_ROL, Permisos::SISTEMA, 'fa fa-key'),
                $op('Sistema', 'Configuración', 'sistema.configuracion', Permisos::SISTEMA_CONFIGURACION, Permisos::SISTEMA, 'fa fa-sliders'),
                $op('Sistema', 'Errores', 'sistema.errores', Permisos::SISTEMA_ERRORES, Permisos::SISTEMA, 'fa fa-triangle-exclamation'),
            ]],
        ];
    }
}
