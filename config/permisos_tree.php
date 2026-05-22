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
                'nombre' => Permisos::PLANILLA_ACTIVIDAD,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_ACTIVIDAD_VER],
                    ['nombre' => Permisos::PLANILLA_ACTIVIDAD_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_ASISTENCIA,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_ASISTENCIA_VER],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_SUSPENSION,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_SUSPENSION_VER],
                    ['nombre' => Permisos::PLANILLA_SUSPENSION_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_RESUMEN_MENSUAL,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_RESUMEN_MENSUAL_VER],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_RESUMEN_GENERAL,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_RESUMEN_GENERAL_VER],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_BLANCO,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_BLANCO_VER],
                    ['nombre' => Permisos::PLANILLA_BLANCO_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_FAMILIAR,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_FAMILIAR_VER],
                    ['nombre' => Permisos::PLANILLA_FAMILIAR_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_CONCEPTO,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_CONCEPTO_VER],
                    ['nombre' => Permisos::PLANILLA_CONCEPTO_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_PARAMETRO,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_PARAMETRO_VER],
                    ['nombre' => Permisos::PLANILLA_PARAMETRO_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_CONFIG_AFP,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_CONFIG_AFP_VER],
                    ['nombre' => Permisos::PLANILLA_CONFIG_AFP_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::PLANILLA_CONFIG_ASISTENCIA,
                'hijos' => [
                    ['nombre' => Permisos::PLANILLA_CONFIG_ASISTENCIA_VER],
                    ['nombre' => Permisos::PLANILLA_CONFIG_ASISTENCIA_GESTIONAR],
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
        'nombre' => Permisos::CAMPO_RIEGO,
        'hijos' => [
            [
                'nombre' => Permisos::CAMPO_RIEGO_REPORTE,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_RIEGO_REPORTE_VER],
                    ['nombre' => Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_RIEGO_LABOR,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_RIEGO_LABOR_VER],
                    ['nombre' => Permisos::CAMPO_RIEGO_LABOR_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_RIEGO_ESTADO,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_RIEGO_ESTADO_VER],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_RIEGO_RESUMEN,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_RIEGO_RESUMEN_VER],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::CAMPO,
        'hijos' => [
            [
                'nombre' => Permisos::CAMPO_LABOR,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_LABOR_VER],
                    ['nombre' => Permisos::CAMPO_LABOR_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_MANO_OBRA,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_MANO_OBRA_VER],
                    ['nombre' => Permisos::CAMPO_MANO_OBRA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_PARCELA,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_PARCELA_VER],
                    ['nombre' => Permisos::CAMPO_PARCELA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_SIEMBRA,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_SIEMBRA_VER],
                    ['nombre' => Permisos::CAMPO_SIEMBRA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPO_MAQUINARIA,
                'hijos' => [
                    ['nombre' => Permisos::CAMPO_MAQUINARIA_VER],
                    ['nombre' => Permisos::CAMPO_MAQUINARIA_GESTIONAR],
                ],
            ],
        ],
    ],
    /* /*[
                    'nombre' => 'Campañas Costos',
                    'hijos' => [
                        ['nombre' => 'Campañas Costos Ver'],
                        ['nombre' => 'Campañas Costos Agregar'],
                        ['nombre' => 'Campañas Costos Editar'],
                        ['nombre' => 'Campañas Costos Eliminar'],
                    ],
                ],*/
    [
        'nombre' => Permisos::CAMPAÑA,
        'hijos' => [
            [
                'nombre' => Permisos::CAMPAÑA_RESUMEN,
                'hijos' => [
                    ['nombre' => Permisos::CAMPAÑA_RESUMEN_VER],
                    ['nombre' => Permisos::CAMPAÑA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPAÑA_CALENDARIO,
                'hijos' => [
                    ['nombre' => Permisos::CAMPAÑA_CALENDARIO_VER],
                ],
            ],
            [
                'nombre' => Permisos::CAMPAÑA_POR_CAMPO,
                'hijos' => [
                    ['nombre' => Permisos::CAMPAÑA_POR_CAMPO_VER],
                    ['nombre' => Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::COCHINILLA,
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
            [
                'nombre' => Permisos::INSUMO_PROVEEDOR,
                'hijos' => [
                    ['nombre' => Permisos::INSUMO_PROVEEDOR_VER],
                    ['nombre' => Permisos::INSUMO_PROVEEDOR_GESTIONAR],
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
        'nombre' => Permisos::SISTEMA,
        'hijos' => [
            [
                'nombre' => Permisos::SISTEMA_USUARIO,
                'hijos' => [
                    ['nombre' => Permisos::SISTEMA_USUARIO_VER],
                    ['nombre' => Permisos::SISTEMA_USUARIO_GESTIONAR],
                    ['nombre' => Permisos::SISTEMA_USUARIO_ROL_PERMISOS],
                ],
            ],
            [
                'nombre' => Permisos::SISTEMA_ROL,
                'hijos' => [
                    ['nombre' => Permisos::SISTEMA_ROL_VER],
                    ['nombre' => Permisos::SISTEMA_ROL_GESTIONAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::CONTABILIDAD,
        'hijos' => [
            [
                'nombre' => Permisos::CONTABILIDAD_FDM,
                'hijos' => [
                    ['nombre' => Permisos::CONTABILIDAD_FDM_VER],
                    ['nombre' => Permisos::CONTABILIDAD_FDM_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA,
                'hijos' => [
                    ['nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA_VER],
                    ['nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL,
                'hijos' => [
                    ['nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL_VER],
                    ['nombre' => Permisos::CONTABILIDAD_COSTO_MENSUAL_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::CAMPAÑA_COSTOS,
                'hijos' => [
                    ['nombre' => Permisos::CAMPAÑA_COSTOS_VER],
                    ['nombre' => Permisos::CAMPAÑA_COSTOS_GESTIONAR],
                ],
            ],
        ],
    ],

    [
        'nombre' => Permisos::REPORTE,
        'hijos' => [
            [
                'nombre' => Permisos::REPORTE_DIARIO,
                'hijos' => [
                    ['nombre' => Permisos::REPORTE_DIARIO_VER],
                ],
            ],
            [
                'nombre' => Permisos::REPORTE_MENSUAL,
                'hijos' => [
                    ['nombre' => Permisos::REPORTE_MENSUAL_VER],
                ],
            ],
            [
                'nombre' => Permisos::REPORTE_ANUAL,
                'hijos' => [
                    ['nombre' => Permisos::REPORTE_ANUAL_VER],
                ],
            ],
            [
                'nombre' => Permisos::REPORTE_AUDITORIA,
                'hijos' => [
                    ['nombre' => Permisos::REPORTE_AUDITORIA_VER],
                ],
            ],
        ],
    ],
];