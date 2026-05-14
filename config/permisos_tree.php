<?php

use App\Constants\Permisos;
// config/permisos_tree.php

return [
    [
        'nombre' => Permisos::PLANILLA,
        'hijos' => [
            [
                'nombre' => Permisos::PERSONAL,
                'hijos' => [
                    ['nombre' => Permisos::PERSONAL_VER],
                    ['nombre' => Permisos::PERSONAL_CREAR],
                    ['nombre' => Permisos::PERSONAL_EDITAR],
                    ['nombre' => Permisos::PERSONAL_CONTRATOS],
                    ['nombre' => Permisos::PERSONAL_FAMILIARES],
                    ['nombre' => Permisos::PERSONAL_OPCIONES],
                    ['nombre' => Permisos::PERSONAL_ELIMINAR],
                    ['nombre' => Permisos::PERSONAL_RESTAURAR],
                ],
            ],
            [
                'nombre' => 'Planilla Actividades Diarias',
                'hijos' => [
                    ['nombre' => 'Planilla Actividades Diarias Ver'],
                    ['nombre' => 'Planilla Actividades Diarias Agregar'],
                    ['nombre' => 'Planilla Actividades Diarias Editar'],
                    ['nombre' => 'Planilla Actividades Diarias Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Asistencia Mensual',
                'hijos' => [
                    ['nombre' => 'Planilla Asistencia Mensual Ver'],
                    ['nombre' => 'Planilla Asistencia Mensual Agregar'],
                    ['nombre' => 'Planilla Asistencia Mensual Editar'],
                    ['nombre' => 'Planilla Asistencia Mensual Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Permisos y Suspensiones',
                'hijos' => [
                    ['nombre' => 'Planilla Permisos y Suspensiones Ver'],
                    ['nombre' => 'Planilla Permisos y Suspensiones Agregar'],
                    ['nombre' => 'Planilla Permisos y Suspensiones Editar'],
                    ['nombre' => 'Planilla Permisos y Suspensiones Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Resumen Mensual',
                'hijos' => [
                    ['nombre' => 'Planilla Resumen Mensual Ver'],
                ],
            ],
            [
                'nombre' => 'Planilla Resumen General',
                'hijos' => [
                    ['nombre' => 'Planilla Resumen General Ver'],
                ],
            ],
            [
                'nombre' => 'Planilla Blanco',
                'hijos' => [
                    ['nombre' => 'Planilla Blanco Ver'],
                ],
            ],
            [
                'nombre' => 'Planilla Familiares',
                'hijos' => [
                    ['nombre' => 'Planilla Familiares Ver'],
                    ['nombre' => 'Planilla Familiares Agregar'],
                    ['nombre' => 'Planilla Familiares Editar'],
                    ['nombre' => 'Planilla Familiares Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Contratos',
                'hijos' => [
                    ['nombre' => 'Planilla Contratos Ver'],
                    ['nombre' => 'Planilla Contratos Agregar'],
                    ['nombre' => 'Planilla Contratos Editar'],
                    ['nombre' => 'Planilla Contratos Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Conceptos',
                'hijos' => [
                    ['nombre' => 'Planilla Conceptos Ver'],
                    ['nombre' => 'Planilla Conceptos Agregar'],
                    ['nombre' => 'Planilla Conceptos Editar'],
                    ['nombre' => 'Planilla Conceptos Eliminar'],
                ],
            ],
            [
                'nombre' => 'Planilla Parámetros',
                'hijos' => [
                    ['nombre' => 'Planilla Parámetros Ver'],
                    ['nombre' => 'Planilla Parámetros Editar'],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::CUADRILLA,
        'hijos' => [
            [
                'nombre' => Permisos::CUADRILLA_PANEL,
                'hijos' => [], // acceso al módulo, sin sub-permisos
            ],
            [
                'nombre' => Permisos::CUADRILLA_LISTA,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_LISTA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_GRUPO,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_GRUPO_GESTIONAR],
                    ['nombre' => Permisos::CUADRILLA_GRUPO_VER_ELIMINADOS],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_SEMANAL,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_SEMANAL_GESTIONAR_TRAMO],
                    ['nombre' => Permisos::CUADRILLA_SEMANAL_AGREGAR_GRUPOS],
                    ['nombre' => Permisos::CUADRILLA_SEMANAL_ASIGNAR_COSTOS],
                    ['nombre' => Permisos::CUADRILLA_SEMANAL_GESTIONAR_GASTOS],
                    ['nombre' => Permisos::CUADRILLA_SEMANAL_GESTIONAR_HORAS],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_DIARIO,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_DIARIO_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_BONIFICACION,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_BONIFICACION_AGREGAR_METODO],
                    ['nombre' => Permisos::CUADRILLA_BONIFICACION_AGREGAR_RECOJO],
                    ['nombre' => Permisos::CUADRILLA_BONIFICACION_ACTUALIZAR],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_RESUMEN_GENERAL,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_RESUMEN_GENERAL_EXPORTAR],
                ],
            ],
            [
                'nombre' => Permisos::CUADRILLA_RESUMEN_ANUAL,
                'hijos' => [
                    ['nombre' => Permisos::CUADRILLA_RESUMEN_ANUAL_EXPORTAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Riego',
        'hijos' => [
            [
                'nombre' => 'Riego Reporte Diario',
                'hijos' => [
                    ['nombre' => 'Riego Reporte Diario Ver'],
                ],
            ],
            [
                'nombre' => 'Riego Labores',
                'hijos' => [
                    ['nombre' => 'Riego Labores Ver'],
                    ['nombre' => 'Riego Labores Agregar'],
                    ['nombre' => 'Riego Labores Editar'],
                    ['nombre' => 'Riego Labores Eliminar'],
                ],
            ],
            [
                'nombre' => 'Riego Estado',
                'hijos' => [
                    ['nombre' => 'Riego Estado Ver'],
                ],
            ],
            [
                'nombre' => 'Riego Resumen Diario',
                'hijos' => [
                    ['nombre' => 'Riego Resumen Diario Ver'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Campo',
        'hijos' => [
            [
                'nombre' => 'Campo Labores',
                'hijos' => [
                    ['nombre' => 'Campo Labores Ver'],
                    ['nombre' => 'Campo Labores Agregar'],
                    ['nombre' => 'Campo Labores Editar'],
                    ['nombre' => 'Campo Labores Eliminar'],
                ],
            ],
            [
                'nombre' => 'Campo Mano de Obra',
                'hijos' => [
                    ['nombre' => 'Campo Mano de Obra Ver'],
                    ['nombre' => 'Campo Mano de Obra Agregar'],
                    ['nombre' => 'Campo Mano de Obra Editar'],
                    ['nombre' => 'Campo Mano de Obra Eliminar'],
                ],
            ],
            [
                'nombre' => 'Campo Campos',
                'hijos' => [
                    ['nombre' => 'Campo Campos Ver'],
                    ['nombre' => 'Campo Campos Agregar'],
                    ['nombre' => 'Campo Campos Editar'],
                    ['nombre' => 'Campo Campos Eliminar'],
                ],
            ],
            [
                'nombre' => 'Campo Siembras',
                'hijos' => [
                    ['nombre' => 'Campo Siembras Ver'],
                    ['nombre' => 'Campo Siembras Agregar'],
                    ['nombre' => 'Campo Siembras Editar'],
                    ['nombre' => 'Campo Siembras Eliminar'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Campañas',
        'hijos' => [
            [
                'nombre' => 'Campañas Resumen General',
                'hijos' => [
                    ['nombre' => 'Campañas Resumen General Ver'],
                ],
            ],
            [
                'nombre' => 'Campañas Calendario',
                'hijos' => [
                    ['nombre' => 'Campañas Calendario Ver'],
                ],
            ],
            [
                'nombre' => 'Campañas Costos',
                'hijos' => [
                    ['nombre' => 'Campañas Costos Ver'],
                    ['nombre' => 'Campañas Costos Agregar'],
                    ['nombre' => 'Campañas Costos Editar'],
                    ['nombre' => 'Campañas Costos Eliminar'],
                ],
            ],
            [
                'nombre' => 'Campañas Por Campo',
                'hijos' => [
                    ['nombre' => 'Campañas Por Campo Ver'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Cochinilla',
        'hijos' => [
            [
                'nombre' => Permisos::COCHINILLA_INGRESO,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::COCHINILLA_VENTEADO,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::COCHINILLA_FILTRADO,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::COCHINILLA_COSECHA,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::COCHINILLA_INFESTACION,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::COCHINILLA_VENTA,
                'hijos' => [
                    ['nombre' => Permisos::COCHINILLA_VENTA_ENTREGA_VER],
                    ['nombre' => Permisos::COCHINILLA_VENTA_ENTREGA_REGISTRAR],
                    ['nombre' => Permisos::COCHINILLA_VENTA_REPORTE_VER],
                    ['nombre' => Permisos::COCHINILLA_VENTA_REPORTE_GESTIONAR],
                    ['nombre' => Permisos::COCHINILLA_VENTA_FACTURACION_VER],
                    ['nombre' => Permisos::COCHINILLA_VENTA_FACTURACION_GESTIONAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::EVALUACION,
        'hijos' => [
            [
                'nombre' => Permisos::PLANTA_EVALUACION,
                'hijos' => [
                    ['nombre' => Permisos::PLANTA_EVALUACION_VER],
                    ['nombre' => Permisos::PLANTA_EVALUACION_CREAR],
                    ['nombre' => Permisos::PLANTA_EVALUACION_EDITAR],
                    ['nombre' => Permisos::PLANTA_EVALUACION_ELIMINAR],
                    ['nombre' => Permisos::PLANTA_EVALUACION_REPORTE],
                ],
            ],
            [
                'nombre' => Permisos::BROTE_EVALUACION,
                'hijos' => [
                    ['nombre' => Permisos::BROTE_EVALUACION_VER],
                    ['nombre' => Permisos::BROTE_EVALUACION_CREAR],
                    ['nombre' => Permisos::BROTE_EVALUACION_EDITAR],
                    ['nombre' => Permisos::BROTE_EVALUACION_ELIMINAR],
                    ['nombre' => Permisos::BROTE_EVALUACION_REPORTE],
                ],
            ],
            [
                'nombre' => Permisos::INFESTACION_EVALUACION,
                'hijos' => [
                    ['nombre' => Permisos::INFESTACION_EVALUACION_VER],
                    ['nombre' => Permisos::INFESTACION_EVALUACION_REGISTRAR]
                ],
            ],
            [
                'nombre' => Permisos::PROYECCION_EVALUACION,
                'hijos' => [
                    ['nombre' => Permisos::PROYECCION_EVALUACION_GUARDAR],
                    ['nombre' => Permisos::PROYECCION_EVALUACION_DETALLE]
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::INSUMO_CATALOGO,
        'hijos' => [
            [
                'nombre' => Permisos::INSUMO_PRODUCTO,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_PRODUCTO_VER],
                    ['nombre' => Permisos::INSUMO_PRODUCTO_GESTIONAR],
                    ['nombre' => Permisos::INSUMO_PRODUCTO_RESTAURAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_CATEGORIA,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_CATEGORIA_VER],
                    ['nombre' => Permisos::INSUMO_CATEGORIA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_SUBCATEGORIA,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_SUBCATEGORIA_VER],
                    ['nombre' => Permisos::INSUMO_SUBCATEGORIA_VER_AUDITORIA],
                    ['nombre' => Permisos::INSUMO_SUBCATEGORIA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_USO,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_USO_VER],
                    ['nombre' => Permisos::INSUMO_USO_GESTIONAR],
                ],
            ],
            [
                // Sin hijos de acción — el padre ya es el único permiso necesario
                'nombre' => Permisos::INSUMO_NUTRIENTE,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_NUTRIENTE_VER],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_CONCENTRACION,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_CONCENTRACION_VER],
                    ['nombre' => Permisos::INSUMO_CONCENTRACION_GESTIONAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::INSUMO,
        'hijos' => [
            [
                'nombre' => Permisos::INSUMO_COMPRA,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_COMPRA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_SALIDA,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_SALIDA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_COMBUSTIBLE,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_COMBUSTIBLE_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_DISTRIBUCION,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_DISTRIBUCION_GESTIONAR],
                ],
            ],
            [
                // Kardex por Insumo comparte este nodo — ambas rutas usan
                // los mismos permisos hijos para habilitar/deshabilitar botones
                'nombre' => Permisos::INSUMO_KARDEX,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_KARDEX_CREAR],
                    ['nombre' => Permisos::INSUMO_KARDEX_ELIMINAR],
                    ['nombre' => Permisos::INSUMO_KARDEX_ASIGNAR_MOVIMIENTOS],
                    ['nombre' => Permisos::INSUMO_KARDEX_GENERAR_RESUMEN],
                    ['nombre' => Permisos::INSUMO_KARDEX_IMPORTAR],
                ],
            ],
            [
                'nombre' => Permisos::INSUMO_KARDEX_REPORTE,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_KARDEX_REPORTE_VER],
                    ['nombre' => Permisos::INSUMO_KARDEX_REPORTE_CREAR],
                    ['nombre' => Permisos::INSUMO_KARDEX_REPORTE_ELIMINAR],
                    ['nombre' => Permisos::INSUMO_KARDEX_REPORTE_GENERAR_RESUMEN],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Sistema',
        'hijos' => [
            [
                'nombre' => 'Usuarios Administrar',
                'hijos' => [
                    ['nombre' => 'Usuarios Ver'],
                    ['nombre' => 'Usuarios Agregar'],
                    ['nombre' => 'Usuarios Editar'],
                    ['nombre' => 'Usuarios Eliminar'],
                ],
            ],
            [
                'nombre' => 'Roles',
                'hijos' => [
                    ['nombre' => 'Roles Ver'],
                    ['nombre' => 'Roles Agregar'],
                    ['nombre' => 'Roles Editar'],
                    ['nombre' => 'Roles Permisos Administrar'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Contabilidad',
        'hijos' => [
            [
                'nombre' => 'Contabilidad Costos FDM',
                'hijos' => [
                    ['nombre' => 'Contabilidad Costos FDM Ver'],
                ],
            ],
            [
                'nombre' => 'Contabilidad Gasto General',
                'hijos' => [
                    ['nombre' => 'Contabilidad Gasto General Ver'],
                    ['nombre' => 'Contabilidad Gasto General Agregar'],
                    ['nombre' => 'Contabilidad Gasto General Editar'],
                    ['nombre' => 'Contabilidad Gasto General Eliminar'],
                ],
            ],
            [
                'nombre' => 'Contabilidad Costos Mensuales',
                'hijos' => [
                    ['nombre' => 'Contabilidad Costos Mensuales Ver'],
                ],
            ],
            [
                'nombre' => 'Contabilidad Costo Mensual',
                'hijos' => [
                    ['nombre' => 'Contabilidad Costo Mensual Ver'],
                ],
            ],
            [
                'nombre' => 'Contabilidad Costos Generales',
                'hijos' => [
                    ['nombre' => 'Contabilidad Costos Generales Ver'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Información General',
        'hijos' => [
            [
                'nombre' => 'Proveedores',
                'hijos' => [
                    ['nombre' => 'Proveedores Ver'],
                    ['nombre' => 'Proveedores Agregar'],
                    ['nombre' => 'Proveedores Editar'],
                    ['nombre' => 'Proveedores Eliminar'],
                ],
            ],
            [
                'nombre' => 'Maquinarias',
                'hijos' => [
                    ['nombre' => 'Maquinarias Ver'],
                    ['nombre' => 'Maquinarias Agregar'],
                    ['nombre' => 'Maquinarias Editar'],
                    ['nombre' => 'Maquinarias Eliminar'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Configuración',
        'hijos' => [
            [
                'nombre' => 'Configuración Parámetros',
                'hijos' => [
                    ['nombre' => 'Configuración Parámetros Ver'],
                    ['nombre' => 'Configuración Parámetros Editar'],
                ],
            ],
            [
                'nombre' => 'Configuración Descuentos AFP',
                'hijos' => [
                    ['nombre' => 'Configuración Descuentos AFP Ver'],
                    ['nombre' => 'Configuración Descuentos AFP Editar'],
                ],
            ],
            [
                'nombre' => 'Configuración Tipo Asistencia',
                'hijos' => [
                    ['nombre' => 'Configuración Tipo Asistencia Ver'],
                    ['nombre' => 'Configuración Tipo Asistencia Agregar'],
                    ['nombre' => 'Configuración Tipo Asistencia Editar'],
                    ['nombre' => 'Configuración Tipo Asistencia Eliminar'],
                ],
            ],
        ],
    ],

    [
        'nombre' => 'Reporte y Auditoría',
        'hijos' => [
            [
                'nombre' => 'Reporte Diario',
                'hijos' => [
                    ['nombre' => 'Reporte Diario Ver'],
                ],
            ],
            [
                'nombre' => 'Reporte Mensual',
                'hijos' => [
                    ['nombre' => 'Reporte Mensual Ver'],
                ],
            ],
            [
                'nombre' => 'Reporte Anual',
                'hijos' => [
                    ['nombre' => 'Reporte Anual Ver'],
                ],
            ],
            [
                'nombre' => 'Auditoría',
                'hijos' => [
                    ['nombre' => 'Auditoría Ver'],
                ],
            ],
        ],
    ],
];