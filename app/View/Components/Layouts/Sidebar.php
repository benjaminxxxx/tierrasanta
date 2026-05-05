<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public array $menu = [];
    public function __construct()
    {
        $this->menu = $this->buildMenu();
    }
    private function buildMenu(): array
    {
        $rawMenu = [
            [
                'title' => 'Planilla',
                'icon' => 'fa fa-table',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Planilla',
                'children' => [
                    ['title' => 'Empleados', 'route' => 'empleados', 'can' => 'Planilla Empleados Ver'],
                    ['title' => 'Actividades Diarias', 'route' => 'reporte.reporte_diario', 'can' => 'Planilla Actividades Diarias Ver'],
                    ['title' => 'Asistencia Mensual', 'route' => 'planilla.asistencia', 'can' => 'Planilla Asistencia Mensual Ver'],
                    ['title' => 'Permisos y Suspensiones', 'route' => 'planilla.suspensiones', 'can' => 'Planilla Permisos y Suspensiones Ver'],
                    ['title' => 'Resumen Mensual', 'route' => 'reporte.resumen_planilla', 'can' => 'Planilla Resumen Mensual Ver'],
                    ['title' => 'Resumen General', 'route' => 'gestion_planilla.reporte_general', 'can' => 'Planilla Resumen General Ver'],
                    ['title' => 'Planilla B+N', 'route' => 'planilla.blanco', 'can' => 'Planilla Blanco Ver'],
                    ['title' => 'Familiares', 'route' => 'empleados.asignacion_familiar', 'can' => 'Planilla Familiares Ver'],
                    ['title' => 'Contratos Empleados', 'route' => 'planilla.contratos', 'can' => 'Planilla Contratos Ver'],
                    ['title' => 'Conceptos', 'route' => 'planilla.conceptos', 'can' => 'Planilla Conceptos Ver'],
                    ['title' => 'Parámetros', 'route' => 'planilla.parametros', 'can' => 'Planilla Parámetros Ver'],
                ]
            ],
            [
                'title' => 'Cuadrilla',
                'icon' => 'fas fa-hard-hat',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Cuadrilla',
                'children' => [
                    ['title' => 'Panel de cuadrilleros', 'route' => 'cuadrilleros.gestion', 'can' => 'Cuadrilla Panel Cuadrilleros Ver'],
                    ['title' => 'Lista de cuadrilleros', 'route' => 'cuadrilla.cuadrilleros', 'can' => 'Cuadrilla Lista Cuadrilleros Ver'],
                    ['title' => 'Grupos de cuadrillas', 'route' => 'cuadrilla.grupos', 'can' => 'Cuadrilla Grupos Ver'],
                    ['title' => 'Reporte semanal (horas)', 'route' => 'gestion_cuadrilleros.reporte-semanal.index', 'can' => 'Cuadrilla Reporte Semanal Ver'],
                    ['title' => 'Reporte diario (actividades)', 'route' => 'gestion_cuadrilleros.registro-diario.index', 'can' => 'Cuadrilla Reporte Diario Ver'],
                    ['title' => 'Bonificaciones', 'route' => 'gestion_cuadrilleros.bonificaciones.index', 'can' => 'Cuadrilla Bonificaciones Ver'],
                    ['title' => 'Resumen General', 'route' => 'gestion_cuadrilleros.resumen_general.index', 'can' => 'Cuadrilla Resumen General Ver'],
                    ['title' => 'Resumen anual', 'route' => 'gestion_cuadrilleros.resumen_anual', 'can' => 'Cuadrilla Resumen Anual Ver'],
                ]
            ],
            [
                'title' => 'Riego',
                'icon' => 'fa fa-water',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Riego',
                'children' => [
                    ['title' => 'Reporte diario regadores', 'route' => 'reporte.reporte_diario_riego', 'can' => 'Riego Reporte Diario Ver'],
                    ['title' => 'Labores en riego', 'route' => 'configuracion.labores_riego', 'can' => 'Riego Labores Ver'],
                    ['title' => 'Ver estado de riegos', 'route' => 'campo.riego', 'can' => 'Riego Estado Ver'],
                    ['title' => 'Resumen diario de riegos', 'route' => 'consolidado.riego', 'can' => 'Riego Resumen Diario Ver'],
                ]
            ],
            [
                'title' => 'Campo',
                'icon' => 'fa fa-leaf',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Campo',
                'children' => [
                    ['title' => 'Labores', 'route' => 'configuracion.labores', 'can' => 'Campo Labores Ver'],
                    ['title' => 'Mano de obra', 'route' => 'campo.mano_obra', 'can' => 'Campo Mano de Obra Ver'],
                    ['title' => 'Campos', 'route' => 'campo.campos', 'can' => 'Campo Campos Ver'],
                    ['title' => 'Siembras', 'route' => 'campo.siembra', 'can' => 'Campo Siembras Ver'],
                ]
            ],
            [
                'title' => 'Campañas',
                'icon' => 'fa fa-flag',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Campañas',
                'children' => [
                    ['title' => 'Resumen General de Campañas', 'route' => 'campanias', 'can' => 'Campañas Resumen General Ver'],
                    ['title' => 'Todas las campañas', 'route' => 'campania.calendario', 'can' => 'Campañas Calendario Ver'],
                    ['title' => 'Costos', 'route' => 'campania.costos', 'can' => 'Campañas Costos Ver'],
                    ['title' => 'Campañas por campo', 'route' => 'campo.campania', 'can' => 'Campañas por Campo Ver'],
                    ['title' => 'Campañas por campo v2', 'route' => 'campania.x.campo', 'can' => 'Campañas por Campo v2 Ver'],
                ]
            ],
            [
                'title' => 'Cochinilla',
                'icon' => 'fa fa-bug',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Cochinilla',
                'children' => [
                    ['title' => 'Ingreso', 'route' => 'cochinilla.ingreso', 'can' => 'Cochinilla Ingreso Ver'],
                    ['title' => 'Venteado', 'route' => 'cochinilla.venteado', 'can' => 'Cochinilla Venteado Ver'],
                    ['title' => 'Filtrado', 'route' => 'cochinilla.filtrado', 'can' => 'Cochinilla Filtrado Ver'],
                    ['title' => 'Cosecha Mamas', 'route' => 'cochinilla.cosecha_mamas', 'can' => 'Cosecha Mamas Ver'],
                    ['title' => 'Infestación', 'route' => 'cochinilla.infestacion', 'can' => 'Cochinilla Infestación Ver'],
                    ['title' => 'Venta', 'route' => 'cochinilla.ventas', 'can' => 'Cochinilla Venta'],
                ]
            ],
            [
                'title' => 'Evaluación de Campo',
                'icon' => 'fa fa-file',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Evaluación de Campo',
                'children' => [
                    ['title' => 'Población Plantas', 'route' => 'reporte_campo.poblacion_plantas', 'can' => 'Evaluación Población Ver'],
                    ['title' => 'Brotes x Piso', 'route' => 'reporte_campo.evaluacion_brotes', 'can' => 'Evaluación Brotes Ver'],
                    ['title' => 'Infestación Cosecha', 'route' => 'reporte_campo.evaluacion_infestacion_cosecha', 'can' => 'Evaluación Infestación Ver'],
                    ['title' => 'Proyección Rendimiento Poda', 'route' => 'reporte_campo.evaluacion_proyeccion_rendimiento_poda', 'can' => 'Evaluación Proyección Ver'],
                ]
            ],
            [
                'title' => 'Producto y Nutrientes',
                'icon' => 'fa fa-box',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Producto y Nutrientes',
                'children' => [
                    ['title' => 'Productos', 'route' => 'productos.index', 'can' => 'Productos Ver'],
                    ['title' => 'Categorias', 'route' => 'categorias.index', 'can' => 'Categorias Ver'],
                    ['title' => 'Subcategorias', 'route' => 'subcategorias.index', 'can' => 'Subcategorias Ver'],
                    ['title' => 'Usos', 'route' => 'producto.usos', 'can' => 'Usos Ver'],
                    ['title' => 'Nutrientes', 'route' => 'nutrientes.index', 'can' => 'Nutrientes Ver'],
                    ['title' => 'Tabla de concentración', 'route' => 'tabla_concentracion.index', 'can' => 'Tabla Concentración Ver'],
                ]
            ],
            [
                'title' => 'Kardex y Almacén',
                'icon' => 'fa fa-clipboard-list',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Kardex y Almacén',
                'children' => [
                    ['title' => 'Entradas (compras)', 'route' => 'almacen.compras', 'can' => 'Kardex Entradas Ver'],
                    ['title' => 'Salida de Almacén Pesticidas y Fertilizantes', 'route' => 'almacen.salida_productos', 'can' => 'Kardex Salida Productos Ver'],
                    ['title' => 'Salida de Combustible', 'route' => 'almacen.salida_combustible', 'can' => 'Kardex Salida Combustible Ver'],
                    ['title' => 'Distribución de Combustible', 'route' => 'almacen.distribucion_combustible', 'can' => 'Kardex Distribución Combustible Ver'],
                    ['title' => 'Kardex de Insumos', 'route' => 'gestion_insumos.kardex', 'can' => 'Kardex Insumos Ver'],
                    ['title' => 'Kardexes por Producto', 'route' => 'gestion_insumos.kardex.crear', 'can' => 'Kardex por Producto Ver'],
                    ['title' => 'Reporte de Kardex', 'route' => 'gestion_insumos.kardex.reportes', 'can' => 'Kardex Reportes Ver'],
                    ['title' => 'Ver Kardex', 'route' => 'kardex.lista', 'can' => 'Ver Kardex'],
                ]
            ],
            [
                'title' => 'Sistema',
                'icon' => 'fas fa-palette',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Sistema',
                'children' => [
                    ['title' => 'Usuarios', 'route' => 'usuarios', 'can' => 'Usuarios Ver'],
                    ['title' => 'Roles y Permisos', 'route' => 'roles_permisos', 'can' => 'Roles Ver'],
                ]
            ],
            [
                'title' => 'Contabilidad',
                'icon' => 'fa fa-calculator',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Contabilidad',
                'children' => [
                    ['title' => 'Costos Generales FDM', 'route' => 'fdm.costos_generales', 'can' => 'Contabilidad Costos FDM Ver'],
                    ['title' => 'Gasto General', 'route' => 'gastos.general', 'can' => 'Contabilidad Gasto General Ver'],
                    ['title' => 'Costos Mensuales', 'route' => 'contabilidad.costos_mensuales', 'can' => 'Contabilidad Costos Mensuales Ver'],
                    ['title' => 'Costo Mensual', 'route' => 'contabilidad.costo_mensual', 'can' => 'Contabilidad Costo Unitario Ver'],
                    ['title' => 'Costos Generales', 'route' => 'contabilidad.costos_generales', 'can' => 'Contabilidad Costos Generales Ver'],
                ]
            ],
            [
                'title' => 'Información General',
                'icon' => 'fa fa-users',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Información General',
                'children' => [
                    ['title' => 'Proveedores', 'route' => 'proveedores.index', 'can' => 'Proveedores Ver'],
                    ['title' => 'Maquinarias', 'route' => 'maquinarias.index', 'can' => 'Maquinarias Ver'],
                ]
            ],
            [
                'title' => 'Configuración',
                'icon' => 'fa fa-cogs',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Configuración',
                'children' => [
                    ['title' => 'Parámetros', 'route' => 'configuracion', 'can' => 'Configuración Parámetros Ver'],
                    ['title' => 'Descuentos de AFP', 'route' => 'descuentos_afp', 'can' => 'Configuración AFP Ver'],
                    ['title' => 'Tipo de Asistencia', 'route' => 'configuracion.tipo_asistencia', 'can' => 'Configuración Asistencia Ver'],
                ]
            ],
            [
                'title' => 'Reporte y Auditoria',
                'icon' => 'fa fa-file',
                'roles' => ['Administrador', 'Super Admin'],
                'can' => 'Reporte y Auditoria',
                'children' => [
                    ['title' => 'Reporte Diario', 'route' => 'reporte_general.reporte_diario', 'can' => 'Reporte Diario Ver'],
                    ['title' => 'Reporte Mensual', 'route' => 'reporte_general.reporte_mensual', 'can' => 'Reporte Mensual Ver'],
                    ['title' => 'Reporte Anual', 'route' => 'reporte_general.reporte_anual', 'can' => 'Reporte Anual Ver'],
                    ['title' => 'Auditoria', 'route' => 'auditoria', 'can' => 'Auditoria Ver'],
                ]
            ],
        ];
        return $this->filterMenu($rawMenu);
    }
    private function filterMenu(array $items): array
    {
        $currentRoute = Route::currentRouteName();

        return collect($items)
            ->filter(function ($item) {

                $hasRole = true;
                $hasPermission = true;

                // Verificar roles
                if (isset($item['roles'])) {
                    $hasRole = auth()->user()->hasAnyRole($item['roles']);
                }

                // Verificar permisos
                if (isset($item['can'])) {
                    $hasPermission = auth()->user()->canAny((array) $item['can']);
                }

                // Si existen ambos => OR
                if (isset($item['roles']) && isset($item['can'])) {
                    return $hasRole || $hasPermission;
                }

                // Si solo existe roles
                if (isset($item['roles'])) {
                    return $hasRole;
                }

                // Si solo existe can
                if (isset($item['can'])) {
                    return $hasPermission;
                }

                return true;
            })
            ->map(function ($item) use ($currentRoute) {

                if (isset($item['children'])) {

                    $item['children'] = collect($item['children'])
                        ->filter(function ($child) {

                            $hasRole = true;
                            $hasPermission = true;

                            if (isset($child['roles'])) {
                                $hasRole = auth()->user()->hasAnyRole($child['roles']);
                            }

                            if (isset($child['can'])) {
                                $hasPermission = auth()->user()->canAny((array) $child['can']);
                            }

                            if (isset($child['roles']) && isset($child['can'])) {
                                return $hasRole || $hasPermission;
                            }

                            if (isset($child['roles'])) {
                                return $hasRole;
                            }

                            if (isset($child['can'])) {
                                return $hasPermission;
                            }

                            return true;
                        })
                        ->map(function ($child) use ($currentRoute) {

                            $child['url'] = route($child['route']);
                            $child['isActive'] = $currentRoute === $child['route'];

                            return $child;
                        })
                        ->values()
                        ->toArray();

                    // Ocultar padre si no tiene hijos visibles
                    if (empty($item['children'])) {
                        return null;
                    }

                    $item['isActive'] = collect($item['children'])
                        ->contains('isActive', true);

                } else {

                    $item['isActive'] = isset($item['route'])
                        ? $currentRoute === $item['route']
                        : false;
                }

                return $item;
            })
            ->filter()
            ->values()
            ->toArray();
    }
    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }
}
