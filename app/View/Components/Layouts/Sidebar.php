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
                'children' => [
                    ['title' => 'Empleados', 'route' => 'empleados'],
                    ['title' => 'Actividades Diarias', 'route' => 'reporte.reporte_diario'],
                    ['title' => 'Asistencia Mensual', 'route' => 'planilla.asistencia'],
                    ['title' => 'Permisos y Suspensiones', 'route' => 'planilla.suspensiones'],
                    ['title' => 'Resumen Mensual', 'route' => 'reporte.resumen_planilla'],
                    ['title' => 'Resumen General', 'route' => 'gestion_planilla.reporte_general'],
                    ['title' => 'Planilla B+N', 'route' => 'planilla.blanco'],
                    ['title' => 'Familiares', 'route' => 'empleados.asignacion_familiar'],
                    ['title' => 'Contratos Empleados', 'route' => 'planilla.contratos'],
                    ['title' => 'Conceptos', 'route' => 'planilla.conceptos'],
                    ['title' => 'Parámetros', 'route' => 'planilla.parametros'],
                ]
            ],
            [
                'title' => 'Cuadrilla',
                'icon' => 'fas fa-hard-hat',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Panel de cuadrilleros', 'route' => 'cuadrilleros.gestion'],
                    ['title' => 'Lista de cuadrilleros', 'route' => 'cuadrilla.cuadrilleros'],
                    ['title' => 'Grupos de cuadrillas', 'route' => 'cuadrilla.grupos'],
                    ['title' => 'Reporte semanal (horas)', 'route' => 'gestion_cuadrilleros.reporte-semanal.index'],
                    ['title' => 'Reporte diario (actividades)', 'route' => 'gestion_cuadrilleros.registro-diario.index'],
                    ['title' => 'Bonificaciones', 'route' => 'gestion_cuadrilleros.bonificaciones.index'],
                    ['title' => 'Resumen General', 'route' => 'gestion_cuadrilleros.resumen_general.index'],
                    ['title' => 'Resumen anual', 'route' => 'gestion_cuadrilleros.resumen_anual'],
                ]
            ],
            [
                'title' => 'Riego',
                'icon' => 'fa fa-water',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Reporte diario regadores', 'route' => 'reporte.reporte_diario_riego'],
                    ['title' => 'Labores en riego', 'route' => 'configuracion.labores_riego'],
                    ['title' => 'Ver estado de riegos', 'route' => 'campo.riego'],
                    ['title' => 'Resumen diario de riegos', 'route' => 'consolidado.riego'],
                ]
            ],
            [
                'title' => 'Campo',
                'icon' => 'fa fa-leaf',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Labores', 'route' => 'configuracion.labores'],
                    ['title' => 'Mano de obra', 'route' => 'campo.mano_obra'],
                    ['title' => 'Campos', 'route' => 'campo.campos'],
                    ['title' => 'Siembras', 'route' => 'campo.siembra'],
                ]
            ],
            [
                'title' => 'Campañas',
                'icon' => 'fa fa-flag',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Resumen General de Campañas', 'route' => 'campanias'],
                    ['title' => 'Todas las campañas', 'route' => 'campania.calendario'],
                    ['title' => 'Costos', 'route' => 'campania.costos'],
                    ['title' => 'Campañas por campo', 'route' => 'campo.campania'],
                    ['title' => 'Campañas por campo v2', 'route' => 'campania.x.campo'],
                ]
            ],
            [
                'title' => 'Cochinilla',
                'icon' => 'fa fa-bug',
                'can' => ['Cochinilla Administrar', 'Cochinilla Entregar', 'Cochinilla Facturar'],
                'children' => [
                    ['title' => 'Ingreso', 'route' => 'cochinilla.ingreso'],
                    ['title' => 'Venteado', 'route' => 'cochinilla.venteado'],
                    ['title' => 'Filtrado', 'route' => 'cochinilla.filtrado'],
                    ['title' => 'Cosecha Mamas', 'route' => 'cochinilla.cosecha_mamas'],
                    ['title' => 'Infestación', 'route' => 'cochinilla.infestacion'],
                    ['title' => 'Venta', 'route' => 'cochinilla.ventas'],
                ]
            ],
            [
                'title' => 'Evaluación de Campo',
                'icon' => 'fa fa-file',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Población Plantas', 'route' => 'reporte_campo.poblacion_plantas'],
                    ['title' => 'Brotes x Piso', 'route' => 'reporte_campo.evaluacion_brotes'],
                    ['title' => 'Infestación Cosecha', 'route' => 'reporte_campo.evaluacion_infestacion_cosecha'],
                    ['title' => 'Proyección Rendimiento Poda', 'route' => 'reporte_campo.evaluacion_proyeccion_rendimiento_poda'],
                ]
            ],
            [
                'title' => 'Producto y Nutrientes',
                'icon' => 'fa fa-box',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Productos', 'route' => 'productos.index'],
                    ['title' => 'Nutrientes', 'route' => 'nutrientes.index'],
                    ['title' => 'Tabla de concentración', 'route' => 'tabla_concentracion.index'],
                ]
            ],
            [
                'title' => 'Kardex y Almacén',
                'icon' => 'fa fa-clipboard-list',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Salida de Almacén Pesticidas y Fertilizantes', 'route' => 'almacen.salida_productos'],
                    ['title' => 'Salida de Combustible', 'route' => 'almacen.salida_combustible'],
                    ['title' => 'Kardex de Insumos', 'route' => 'gestion_insumos.kardex'],
                    ['title' => 'Reporte de Kardex', 'route' => 'gestion_insumos.kardex.reportes'],
                    ['title' => 'Ver Kardex', 'route' => 'kardex.lista'],
                ]
            ],
            [
                'title' => 'Sistema',
                'icon' => 'fas fa-palette',
                'can' => ['Usuarios Administrar', 'Roles'],
                'children' => [
                    ['title' => 'Usuarios', 'route' => 'usuarios', 'can' => 'Usuarios Administrar'],
                    ['title' => 'Roles y Permisos', 'route' => 'roles_permisos', 'can' => 'Roles'],
                ]
            ],
            [
                'title' => 'Contabilidad',
                'icon' => 'fa fa-calculator',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Costos Generales FDM', 'route' => 'fdm.costos_generales'],
                    ['title' => 'Gasto General', 'route' => 'gastos.general'],
                    ['title' => 'Costos Mensuales', 'route' => 'contabilidad.costos_mensuales'],
                    ['title' => 'Costo Mensual', 'route' => 'contabilidad.costo_mensual'],
                    ['title' => 'Costos Generales', 'route' => 'contabilidad.costos_generales'],
                ]
            ],
            [
                'title' => 'Información General',
                'icon' => 'fa fa-users',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Proveedores', 'route' => 'proveedores.index'],
                    ['title' => 'Maquinarias', 'route' => 'maquinarias.index'],
                ]
            ],
            [
                'title' => 'Configuración',
                'icon' => 'fa fa-cogs',
                'roles' => ['Administrador', 'Super Admin'],
                'children' => [
                    ['title' => 'Parámetros', 'route' => 'configuracion'],
                    ['title' => 'Descuentos de AFP', 'route' => 'descuentos_afp'],
                    ['title' => 'Tipo de Asistencia', 'route' => 'configuracion.tipo_asistencia'],
                ]
            ],
        ];

        return $this->filterMenu($rawMenu);
    }
    private function filterMenu(array $items): array
    {
        $currentRoute = Route::currentRouteName();

        return collect($items)->filter(function ($item) {
            // Mantenemos tus filtros de seguridad
            if (isset($item['roles']) && !auth()->user()->hasAnyRole($item['roles']))
                return false;
            if (isset($item['can']) && !auth()->user()->canAny((array) $item['can']))
                return false;
            return true;
        })->map(function ($item) use ($currentRoute) {
            // 1. Procesar hijos primero
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->map(function ($child) use ($currentRoute) {
                    // Agregar URL y estado activo al hijo
                    $child['url'] = route($child['route']);
                    $child['isActive'] = $currentRoute === $child['route'];
                    return $child;
                })->toArray();

                // 2. El padre está activo si algún hijo está activo
                $item['isActive'] = collect($item['children'])->contains('isActive', true);
            } else {
                // Si el padre no tiene hijos, verificamos su propia ruta (si tuviera)
                $item['isActive'] = isset($item['route']) ? $currentRoute === $item['route'] : false;
            }

            return $item;
        })->toArray();

    }
    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }
}
