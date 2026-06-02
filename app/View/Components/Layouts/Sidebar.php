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

    private function buildMenu(): array
    {
        $rawMenu = [
            [
                'title' => 'Planilla',
                'icon' => 'fa fa-table',
                'can' => Permisos::PLANILLA,
                'children' => [
                    [
                        'title' => 'Empleados',
                        'route' => 'empleados',
                        'can' => Permisos::PERSONAL
                    ],
                    [
                        'title' => 'Actividades Diarias',
                        'route' => 'reporte.reporte_diario',
                        'can' => Permisos::PLANILLA_ACTIVIDAD
                    ],
                    [
                        'title' => 'Asistencia Mensual',
                        'route' => 'planilla.asistencia',
                        'can' => Permisos::PLANILLA_ASISTENCIA
                    ],
                    [
                        'title' => 'Permisos y Suspensiones',
                        'route' => 'planilla.suspensiones',
                        'can' => Permisos::PLANILLA_SUSPENSION
                    ],
                    [
                        'title' => 'Resumen Mensual',
                        'route' => 'reporte.resumen_planilla',
                        'can' => Permisos::PLANILLA_RESUMEN_MENSUAL
                    ],
                    [
                        'title' => 'Resumen General',
                        'route' => 'gestion_planilla.reporte_general',
                        'can' => Permisos::PLANILLA_RESUMEN_GENERAL
                    ],
                    [
                        'title' => 'Planilla B+N',
                        'route' => 'planilla.blanco',
                        'can' => Permisos::PLANILLA_BLANCO
                    ],
                    [
                        'title' => 'Familiares',
                        'route' => 'empleados.asignacion_familiar',
                        'can' => Permisos::PLANILLA_FAMILIAR
                    ],
                    [
                        'title' => 'Contratos Empleados',
                        'route' => 'planilla.contratos',
                        'can' => Permisos::PERSONAL_CONTRATOS
                    ],
                    [
                        'title' => 'Conceptos',
                        'route' => 'planilla.conceptos',
                        'can' => Permisos::PLANILLA_CONCEPTO
                    ],
                    [
                        'title' => 'Parámetros',
                        'route' => 'planilla.parametros',
                        'can' => Permisos::PLANILLA_PARAMETRO
                    ],
                    [
                        'title' => 'Descuentos de AFP',
                        'route' => 'descuentos_afp',
                        'can' => Permisos::PLANILLA_CONFIG_AFP,
                    ],
                    [
                        'title' => 'Tipo de Asistencia',
                        'route' => 'configuracion.tipo_asistencia',
                        'can' => Permisos::PLANILLA_CONFIG_ASISTENCIA,
                    ],
                ],
            ],
            [
                'title' => 'Cuadrilla',
                'icon' => 'fas fa-hard-hat',
                'can' => Permisos::CUADRILLA,
                'children' => [
                    ['title' => 'Panel de cuadrilleros', 'route' => 'cuadrilleros.gestion', 'can' => Permisos::CUADRILLA_PANEL],
                    ['title' => 'Lista de cuadrilleros', 'route' => 'cuadrilla.cuadrilleros', 'can' => Permisos::CUADRILLA_LISTA],
                    ['title' => 'Grupos de cuadrillas', 'route' => 'cuadrilla.grupos', 'can' => Permisos::CUADRILLA_GRUPO],
                    ['title' => 'Reporte semanal (horas)', 'route' => 'gestion_cuadrilleros.reporte-semanal.index', 'can' => Permisos::CUADRILLA_SEMANAL],
                    ['title' => 'Reporte diario (actividades)', 'route' => 'gestion_cuadrilleros.registro-diario.index', 'can' => Permisos::CUADRILLA_DIARIO],
                    ['title' => 'Bonificaciones', 'route' => 'gestion_cuadrilleros.bonificaciones.index', 'can' => Permisos::CUADRILLA_BONIFICACION],
                    ['title' => 'Resumen General', 'route' => 'gestion_cuadrilleros.resumen_general.index', 'can' => Permisos::CUADRILLA_RESUMEN_GENERAL],
                    ['title' => 'Resumen anual', 'route' => 'gestion_cuadrilleros.resumen_anual', 'can' => Permisos::CUADRILLA_RESUMEN_ANUAL],
                ],
            ],

            [
                'title' => 'Riego',
                'icon' => 'fa fa-water',
                'can' => Permisos::CAMPO_RIEGO,
                'children' => [
                    ['title' => 'Reporte diario regadores', 'route' => 'reporte.reporte_diario_riego', 'can' => Permisos::CAMPO_RIEGO_REPORTE],
                    ['title' => 'Labores en riego', 'route' => 'configuracion.labores_riego', 'can' => Permisos::CAMPO_RIEGO_LABOR],
                    ['title' => 'Ver estado de riegos', 'route' => 'campo.riego', 'can' => Permisos::CAMPO_RIEGO_ESTADO],
                    ['title' => 'Resumen diario de riegos', 'route' => 'consolidado.riego', 'can' => Permisos::CAMPO_RIEGO_RESUMEN],
                ],
            ],
            [
                'title' => 'Campo',
                'icon' => 'fa fa-leaf',
                'can' => Permisos::CAMPO,
                'children' => [
                    ['title' => 'Labores', 'route' => 'configuracion.labores', 'can' => Permisos::CAMPO_LABOR],
                    ['title' => 'Mano de obra', 'route' => 'campo.mano_obra', 'can' => Permisos::CAMPO_MANO_OBRA],
                    ['title' => 'Campos', 'route' => 'campo.campos', 'can' => Permisos::CAMPO_PARCELA],
                    ['title' => 'Siembras', 'route' => 'campo.siembra', 'can' => Permisos::CAMPO_SIEMBRA],
                    ['title' => 'Maquinarias', 'route' => 'maquinarias.index', 'can' => Permisos::CAMPO_MAQUINARIA],
                ],
            ],
            [
                'title' => 'Campañas',
                'icon' => 'fa fa-flag',
                'can' => Permisos::CAMPAÑA,
                'children' => [
                    [
                        'title' => 'Resumen General de Campañas',
                        'route' => 'campanias',
                        'can' => Permisos::CAMPAÑA_RESUMEN,
                    ],
                    [
                        'title' => 'Todas las campañas',
                        'route' => 'campania.calendario',
                        'can' => Permisos::CAMPAÑA_CALENDARIO,
                    ],
                    [
                        'title' => 'Campañas por campo',
                        'route' => 'campania.x.campo',
                        'can' => Permisos::CAMPAÑA_POR_CAMPO,
                    ],
                ],
            ],
            [
                'title' => 'Cochinilla',
                'icon' => 'fa fa-bug',
                'can' => Permisos::COCHINILLA,
                'children' => [
                    ['title' => 'Ingreso', 'route' => 'cochinilla.ingreso', 'can' => Permisos::COCHINILLA_INGRESO],
                    ['title' => 'Venteado', 'route' => 'cochinilla.venteado', 'can' => Permisos::COCHINILLA_VENTEADO],
                    ['title' => 'Filtrado', 'route' => 'cochinilla.filtrado', 'can' => Permisos::COCHINILLA_FILTRADO],
                    ['title' => 'Cosecha Mamas', 'route' => 'cochinilla.cosecha_mamas', 'can' => Permisos::COCHINILLA_COSECHA],
                    ['title' => 'Infestación', 'route' => 'cochinilla.infestacion', 'can' => Permisos::COCHINILLA_INFESTACION],
                    ['title' => 'Venta', 'route' => 'cochinilla.ventas', 'can' => Permisos::COCHINILLA_VENTA],
                ],
            ],
            [
                'title' => 'Evaluación de Campo',
                'icon' => 'fa fa-file',
                'can' => Permisos::EVALUACION,
                'children' => [
                    ['title' => 'Población Plantas', 'route' => 'reporte_campo.poblacion_plantas', 'can' => Permisos::PLANTA_EVALUACION],
                    ['title' => 'Brotes x Piso', 'route' => 'reporte_campo.evaluacion_brotes', 'can' => Permisos::BROTE_EVALUACION],
                    ['title' => 'Proyección rendimiento: Conteo de cochinilla', 'route' => 'reporte_campo.evaluacion_infestacion_cosecha', 'can' => Permisos::INFESTACION_EVALUACION],
                    ['title' => 'Proyección Rendimiento Poda', 'route' => 'reporte_campo.evaluacion_proyeccion_rendimiento_poda', 'can' => Permisos::PROYECCION_EVALUACION],
                ],
            ],
            [
                'title' => 'Producto y Nutrientes',
                'icon' => 'fa fa-box',
                'can' => Permisos::INSUMO_CATALOGO,
                'children' => [
                    ['title' => 'Productos', 'route' => 'productos.index', 'can' => Permisos::INSUMO_PRODUCTO],
                    ['title' => 'Categorias', 'route' => 'categorias.index', 'can' => Permisos::INSUMO_CATEGORIA],
                    ['title' => 'Subcategorias', 'route' => 'subcategorias.index', 'can' => Permisos::INSUMO_SUBCATEGORIA],
                    ['title' => 'Usos', 'route' => 'producto.usos', 'can' => Permisos::INSUMO_USO],
                    ['title' => 'Nutrientes', 'route' => 'nutrientes.index', 'can' => Permisos::INSUMO_NUTRIENTE],
                    ['title' => 'Tabla de concentración', 'route' => 'tabla_concentracion.index', 'can' => Permisos::INSUMO_CONCENTRACION],
                    ['title' => 'Proveedores', 'route' => 'proveedores.index', 'can' => Permisos::INSUMO_PROVEEDOR],
                ],
            ],
            [
                'title' => 'Kardex y Almacén',
                'icon' => 'fa fa-clipboard-list',
                'can' => Permisos::INSUMO,
                'children' => [
                    [
                        'title' => 'Entradas (compras)',
                        'route' => 'almacen.compras',
                        'can' => Permisos::INSUMO_COMPRA,
                    ],
                    [
                        'title' => 'Salida de Almacén Pesticidas y Fertilizantes',
                        'route' => 'almacen.salida_productos',
                        'can' => Permisos::INSUMO_SALIDA,
                    ],
                    [
                        'title' => 'Salida de Combustible',
                        'route' => 'almacen.salida_combustible',
                        'can' => Permisos::INSUMO_COMBUSTIBLE,
                    ],
                    [
                        'title' => 'Distribución de Combustible',
                        'route' => 'almacen.distribucion_combustible',
                        'can' => Permisos::INSUMO_DISTRIBUCION,
                    ],
                    [
                        'title' => 'Kardex de Insumos',
                        'route' => 'gestion_insumos.kardex',
                        'can' => Permisos::INSUMO_KARDEX,
                    ],
                    [
                        // Misma guardia que Kardex — comparte permisos hijos
                        'title' => 'Kardex por Insumo',
                        'route' => 'gestion_insumos.kardex.crear',
                        'can' => Permisos::INSUMO_KARDEX,
                    ],
                    [
                        'title' => 'Reporte de Kardex',
                        'route' => 'gestion_insumos.kardex.reportes',
                        'can' => Permisos::INSUMO_KARDEX_REPORTE,
                    ],
                ],
            ],
            [
                'title' => 'Sistema',
                'icon' => 'fas fa-palette',
                'can' => Permisos::SISTEMA,
                'children' => [
                    [
                        'title' => 'Usuarios',
                        'route' => 'usuarios',
                        'can' => Permisos::SISTEMA_USUARIO,
                    ],
                    [
                        'title' => 'Roles y Permisos',
                        'route' => 'roles_permisos',
                        'can' => Permisos::SISTEMA_ROL,
                    ],
                ],
            ],
            [
                'title' => 'Contabilidad',
                'icon' => 'fa fa-calculator',
                'can' => Permisos::CONTABILIDAD,
                'children' => [
                    ['title' => 'Costos Generales FDM', 'route' => 'fdm.costos_generales', 'can' => Permisos::CONTABILIDAD_FDM],
                    ['title' => 'Costos Mensuales', 'route' => 'contabilidad.costos_mensuales', 'can' => Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA],
                    ['title' => 'Costo Mensual', 'route' => 'contabilidad.costo_mensual', 'can' => Permisos::CONTABILIDAD_COSTO_MENSUAL],
                    ['title' => 'Costos x Campaña', 'route' => 'campania.costos', 'can' => Permisos::CAMPAÑA_COSTOS],
                    
                ],
            ],

            [
                'title' => 'Reporte y Auditoria',
                'icon' => 'fa fa-file',
                'can' => Permisos::REPORTE,
                'children' => [
                    ['title' => 'Reporte Diario', 'route' => 'reporte_general.reporte_diario', 'can' => Permisos::REPORTE_DIARIO],
                    ['title' => 'Reporte Mensual', 'route' => 'reporte_general.reporte_mensual', 'can' => Permisos::REPORTE_MENSUAL],
                    ['title' => 'Reporte Anual', 'route' => 'reporte_general.reporte_anual', 'can' => Permisos::REPORTE_ANUAL],
                    ['title' => 'Auditoria', 'route' => 'auditoria', 'can' => Permisos::REPORTE_AUDITORIA],
                ],
            ],
        ];

        return $this->filterMenu($rawMenu);
    }

    /**
     * Decide si el usuario actual puede ver un ítem del menú.
     *
     * Reglas en orden:
     *  1. Super Admin  → siempre true.
     *  2. Administrador → true SALVO que el 'can' del ítem esté en EXCEPCIONES_ADMINISTRADOR.
     *  3. Cualquier otro rol → evalúa 'can' normalmente con Spatie.
     */
    private function puedeVer(array $item): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin'))
            return true;

        if ($user->hasRole('Administrador')) {
            if (isset($item['can'])) {
                $estaExcluido = !empty(
                    array_intersect((array) $item['can'], self::EXCEPCIONES_ADMINISTRADOR)
                );
                if ($estaExcluido)
                    return false;
            }
            return true;
        }

        // Otro rol: verificar solo el 'can' del propio nodo, sin importar hijos ni padres
        if (!isset($item['can']))
            return false;

        return $user->can($item['can']);
    }

    private function filterMenu(array $items): array
    {
        $currentRoute = Route::currentRouteName();

        return collect($items)
            ->filter(fn($item) => $this->puedeVer($item))
            ->map(function ($item) use ($currentRoute) {

                if (isset($item['children'])) {
                    // Filtrar hijos recursivamente con la misma lógica
                    $item['children'] = collect($item['children'])
                        ->filter(fn($child) => $this->puedeVer($child))
                        ->map(function ($child) use ($currentRoute) {
                        // Si el hijo también tiene children, filtrarlos recursivamente
                        if (isset($child['children'])) {
                            $child = $this->mapItem($child, $currentRoute);
                        } else {
                            $child['url'] = route($child['route']);
                            $child['isActive'] = Route::currentRouteName() === $child['route'];
                        }
                        return $child;
                    })
                        ->values()
                        ->toArray();

                    // Padre visible aunque no tenga hijos visibles
                    // (él mismo tiene el permiso, no depende de sus hijos)
                    $item['isActive'] = collect($item['children'])->contains('isActive', true);

                } else {
                    $item['url'] = route($item['route']);
                    $item['isActive'] = $currentRoute === $item['route'];
                }

                return $item;
            })
            ->values()
            ->toArray();
    }

    // Extrae el map recursivo para no duplicar código
    private function mapItem(array $item, string $currentRoute): array
    {
        if (isset($item['children'])) {
            $item['children'] = collect($item['children'])
                ->filter(fn($child) => $this->puedeVer($child))
                ->map(fn($child) => $this->mapItem($child, $currentRoute))
                ->values()
                ->toArray();

            $item['isActive'] = collect($item['children'])->contains('isActive', true);
        } else {
            $item['url'] = route($item['route']);
            $item['isActive'] = Route::currentRouteName() === $item['route'];
        }

        return $item;
    }

    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }
}