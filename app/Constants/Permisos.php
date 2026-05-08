<?php

namespace App\Constants;

/**
 * =============================================================================
 * CLASE DE PERMISOS DEL SISTEMA
 * =============================================================================
 *
 * PROPÓSITO:
 *   Centralizar todos los nombres de permisos para evitar strings sueltos
 *   en rutas, vistas, middlewares y lógica de negocio.
 *
 * REGLAS DE NOMENCLATURA:
 * -----------------------------------------------------------------------------
 * 1. DOMINIO PRIMERO — La constante empieza con el dominio de negocio,
 *    no con la ubicación en el menú. El menú puede reorganizarse; el dominio
 *    no cambia.
 *    ✅ PERSONAL_VER         (dominio: PERSONAL)
 *    ❌ RRHH_PERSONAL_VER    (ubicación del menú, puede cambiar)
 *
 * 2. DOMINIOS DISPONIBLES — Usar solo estos prefijos de dominio:
 *    - PERSONAL     → plan_empleados, plan_familiares, plan_contratos, users
 *    - PLANILLA     → plan_mensuales, plan_registros_diarios, plan_conceptos, etc.
 *    - CUADRILLA    → cuad_*
 *    - INSUMO       → ins_*, kardex_*, compra_*, almacen_*
 *    - CAMPO        → campos, labores, siembras, mano_obras, reg_*
 *    - CAMPAÑA      → campos_campanias, fertilizacion_campanias, pesticidas_campanias
 *    - COCHINILLA   → cochinilla_*, venta_cochinillas, venta_facturada_cochinillas
 *    - PLANTA       → eval_poblacion_plantas*, poblacion_plantas*
 *    - EVALUACION   → eval_brotes*, eval_infestacion*, evaluacion_*, proyeccion_*
 *    - REPORTE      → rep_*, rpt_*, reporte_*, v_reporte_*
 *    - SISTEMA      → users, roles, permissions, auditorias, configuracion*
 *    - CONTABILIDAD → costos_mensuales, costo_fdm*, costo_mano*, kardex_consolidados
 *
 * 3. VERBO AL FINAL — La acción siempre va al final, nunca al inicio.
 *    ✅ PERSONAL_CONTRATO_EDITAR
 *    ❌ EDITAR_CONTRATO_PERSONAL
 *
 * 4. VERBOS PERMITIDOS (usar exactamente estos, sin inventar sinónimos):
 *    - VER          → leer, listar, descargar, exportar, imprimir
 *    - CREAR        → nuevo registro
 *    - EDITAR       → modificar registro existente
 *    - ELIMINAR     → borrar, archivar, anular
 *    - RESTAURAR    → recuperar eliminados
 *    - GESTIONAR    → CRUD completo sobre una sub-entidad relacionada
 *                     (ej: gestionar familiares de un empleado)
 *    - ADMINISTRAR  → acceso total al módulo (equivale a todos los verbos)
 *
 * 5. CONSTANTES SIN CONCATENACIÓN DE OTRAS CONSTANTES — Para que al usar
 *    Permisos::PLANTA_EVALUACION_ELIMINAR en cualquier parte del código
 *    se entienda el dominio sin ir a ver la clase.
 *    El valor string SÍ puede heredar el prefijo del padre para formar
 *    el árbol de Spatie, pero la constante PHP es independiente.
 *    ✅ const PLANTA_EVALUACION_ELIMINAR = 'Planta Evaluación Eliminar';
 *    ❌ const PLANTA_EVALUACION_ELIMINAR = self::PLANTA_EVALUACION . ' Eliminar';
 *
 * 6. AGRUPACIÓN POR DOMINIO EN EL ARCHIVO — Separar con comentarios de sección.
 *    Dentro de cada sección, ordenar: primero el nodo raíz, luego sus hijos,
 *    de más general a más específico.
 *
 * 7. PERMISOS DE NODO PADRE — El permiso del padre es el acceso al módulo.
 *    Tener el padre NO implica tener los hijos; cada uno se asigna por separado.
 *    El padre se usa en: middleware de ruta, 'can' del menú padre.
 *    Los hijos se usan en: @can en vistas, lógica de negocio puntual.
 *
 * USO:
 *   En rutas:      ->middleware('can:' . Permisos::PLANTA_EVALUACION_VER)
 *   En vistas:     @can(App\Constants\Permisos::PLANTA_EVALUACION_ELIMINAR)
 *   En PHP:        $user->can(Permisos::PERSONAL_VER)
 * =============================================================================
 */
class Permisos
{
    // =========================================================================
    // DOMINIO: EVALUACIÓN (brotes, infestación, proyección, población)
    // Tablas: eval_*, evaluacion_*, proyeccion_*, poblacion_plantas*
    // =========================================================================

    // — Módulo raíz
    const EVALUACION                            = 'Evaluación de Campo';

    // — Submódulo: Población de Plantas
    const PLANTA_EVALUACION                     = 'Evaluación de Campo Población Plantas';
    const PLANTA_EVALUACION_VER                 = 'Evaluación de Campo Población Plantas Ver';
    const PLANTA_EVALUACION_CREAR               = 'Evaluación de Campo Población Plantas Crear Evaluación';
    const PLANTA_EVALUACION_EDITAR              = 'Evaluación de Campo Población Plantas Editar Evaluación';
    const PLANTA_EVALUACION_ELIMINAR            = 'Evaluación de Campo Población Plantas Eliminar Evaluación';
    const PLANTA_EVALUACION_REPORTE             = 'Evaluación de Campo Población Plantas Ver Reporte';

    // — Submódulo: Brotes x Piso
    const BROTE_EVALUACION                      = 'Evaluación de Campo Brotes x Piso';
    const BROTE_EVALUACION_VER                  = 'Evaluación de Campo Brotes x Piso Ver';
    const BROTE_EVALUACION_CREAR                = 'Evaluación de Campo Brotes x Piso Crear Evaluación';
    const BROTE_EVALUACION_EDITAR               = 'Evaluación de Campo Brotes x Piso Editar Evaluación';
    const BROTE_EVALUACION_ELIMINAR             = 'Evaluación de Campo Brotes x Piso Eliminar Evaluación';
    const BROTE_EVALUACION_REPORTE              = 'Evaluación de Campo Brotes x Piso Ver Reporte';

    // — Submódulo: Infestación Cosecha
    const INFESTACION_EVALUACION                = 'Evaluación de Campo Infestación Cosecha';
    const INFESTACION_EVALUACION_VER            = 'Evaluación de Campo Infestación Cosecha Ver';
    const INFESTACION_EVALUACION_REGISTRAR      = 'Evaluación de Campo Infestación Cosecha Registrar Evaluación';

    // — Submódulo: Proyección Rendimiento Poda
    const PROYECCION_EVALUACION                 = 'Evaluación de Campo Proyección Rendimiento';
    const PROYECCION_EVALUACION_GUARDAR         = 'Evaluación de Campo Proyección Rendimiento Guardar Proyección';
    const PROYECCION_EVALUACION_DETALLE         = 'Evaluación de Campo Proyección Rendimiento Registrar Detalle';

    // =========================================================================
    // DOMINIO: PERSONAL (empleados, familiares, contratos)
    // Tablas: plan_empleados, plan_familiares, plan_contratos, plan_sueldos
    // =========================================================================

    const PERSONAL                              = 'Planilla Empleados';
    const PERSONAL_VER                          = 'Planilla Empleados Ver';
    const PERSONAL_CREAR                        = 'Planilla Empleados Crear Empleado';
    const PERSONAL_EDITAR                       = 'Planilla Empleados Editar Empleado';
    const PERSONAL_ELIMINAR                     = 'Planilla Empleados Eliminar';
    const PERSONAL_RESTAURAR                    = 'Planilla Empleados Restaurar Empleado';
    const PERSONAL_CONTRATOS                    = 'Planilla Empleados Gestionar Contratos y Sueldos';
    const PERSONAL_FAMILIARES                   = 'Planilla Empleados Gestionar Familiares';
    const PERSONAL_OPCIONES                     = 'Planilla Empleados Gestionar Opciones';

    // =========================================================================
    // DOMINIO: PLANILLA (asistencia, registros diarios, resúmenes)
    // Tablas: plan_mensuales, plan_registros_diarios, plan_periodos, etc.
    // =========================================================================

    const PLANILLA                              = 'Planilla';
    const PLANILLA_ACTIVIDAD_VER                = 'Planilla Actividades Diarias Ver';
    const PLANILLA_ASISTENCIA_VER               = 'Planilla Asistencia Mensual Ver';
    const PLANILLA_SUSPENSION                   = 'Planilla Permisos y Suspensiones';
    const PLANILLA_SUSPENSION_VER               = 'Planilla Permisos y Suspensiones Ver';
    const PLANILLA_SUSPENSION_CREAR             = 'Planilla Permisos y Suspensiones Crear';
    const PLANILLA_SUSPENSION_EDITAR            = 'Planilla Permisos y Suspensiones Editar';
    const PLANILLA_SUSPENSION_ELIMINAR          = 'Planilla Permisos y Suspensiones Eliminar';
    const PLANILLA_RESUMEN_MENSUAL_VER          = 'Planilla Resumen Mensual Ver';
    const PLANILLA_RESUMEN_GENERAL_VER          = 'Planilla Resumen General Ver';
    const PLANILLA_BLANCO_VER                   = 'Planilla Blanco Ver';
    const PLANILLA_CONCEPTOS                    = 'Planilla Conceptos';
    const PLANILLA_CONCEPTOS_VER                = 'Planilla Conceptos Ver';
    const PLANILLA_CONCEPTOS_CREAR              = 'Planilla Conceptos Crear';
    const PLANILLA_CONCEPTOS_EDITAR             = 'Planilla Conceptos Editar';
    const PLANILLA_CONCEPTOS_ELIMINAR           = 'Planilla Conceptos Eliminar';
    const PLANILLA_PARAMETROS_VER               = 'Planilla Parámetros Ver';
    const PLANILLA_PARAMETROS_EDITAR            = 'Planilla Parámetros Editar';

    // =========================================================================
    // DOMINIO: COCHINILLA
    // Tablas: cochinilla_*, venta_cochinillas, venta_facturada_cochinillas
    // =========================================================================

    const COCHINILLA                            = 'Cochinilla';
    const COCHINILLA_INGRESO                    = 'Cochinilla Ingreso';
    const COCHINILLA_INGRESO_VER                = 'Cochinilla Ingreso Ver';
    const COCHINILLA_INGRESO_CREAR              = 'Cochinilla Ingreso Crear';
    const COCHINILLA_INGRESO_EDITAR             = 'Cochinilla Ingreso Editar';
    const COCHINILLA_INGRESO_ELIMINAR           = 'Cochinilla Ingreso Eliminar';
    const COCHINILLA_VENTEADO                   = 'Cochinilla Venteado';
    const COCHINILLA_VENTEADO_VER               = 'Cochinilla Venteado Ver';
    const COCHINILLA_VENTEADO_CREAR             = 'Cochinilla Venteado Crear';
    const COCHINILLA_VENTEADO_EDITAR            = 'Cochinilla Venteado Editar';
    const COCHINILLA_VENTEADO_ELIMINAR          = 'Cochinilla Venteado Eliminar';
    const COCHINILLA_FILTRADO                   = 'Cochinilla Filtrado';
    const COCHINILLA_FILTRADO_VER               = 'Cochinilla Filtrado Ver';
    const COCHINILLA_FILTRADO_CREAR             = 'Cochinilla Filtrado Crear';
    const COCHINILLA_FILTRADO_EDITAR            = 'Cochinilla Filtrado Editar';
    const COCHINILLA_FILTRADO_ELIMINAR          = 'Cochinilla Filtrado Eliminar';
    const COCHINILLA_COSECHA                    = 'Cochinilla Cosecha Mamas';
    const COCHINILLA_COSECHA_VER                = 'Cochinilla Cosecha Mamas Ver';
    const COCHINILLA_COSECHA_CREAR              = 'Cochinilla Cosecha Mamas Crear';
    const COCHINILLA_COSECHA_EDITAR             = 'Cochinilla Cosecha Mamas Editar';
    const COCHINILLA_COSECHA_ELIMINAR           = 'Cochinilla Cosecha Mamas Eliminar';
    const COCHINILLA_INFESTACION                = 'Cochinilla Infestación';
    const COCHINILLA_INFESTACION_VER            = 'Cochinilla Infestación Ver';
    const COCHINILLA_INFESTACION_CREAR          = 'Cochinilla Infestación Crear';
    const COCHINILLA_INFESTACION_EDITAR         = 'Cochinilla Infestación Editar';
    const COCHINILLA_INFESTACION_ELIMINAR       = 'Cochinilla Infestación Eliminar';
    const COCHINILLA_VENTA                      = 'Cochinilla Venta';
    const COCHINILLA_VENTA_VER                  = 'Cochinilla Venta Ver';
    const COCHINILLA_VENTA_CREAR                = 'Cochinilla Venta Crear';
    const COCHINILLA_VENTA_EDITAR               = 'Cochinilla Venta Editar';
    const COCHINILLA_VENTA_ELIMINAR             = 'Cochinilla Venta Eliminar';

    // =========================================================================
    // DOMINIO: SISTEMA (usuarios, roles, auditoría, configuración)
    // Tablas: users, roles, permissions, auditorias, configuracion*
    // =========================================================================

    const SISTEMA                               = 'Sistema';
    const SISTEMA_USUARIO                       = 'Usuarios Administrar';
    const SISTEMA_USUARIO_VER                   = 'Usuarios Ver';
    const SISTEMA_USUARIO_CREAR                 = 'Usuarios Crear';
    const SISTEMA_USUARIO_EDITAR                = 'Usuarios Editar';
    const SISTEMA_USUARIO_ELIMINAR              = 'Usuarios Eliminar';
    const SISTEMA_ROL                           = 'Roles';
    const SISTEMA_ROL_VER                       = 'Roles Ver';
    const SISTEMA_ROL_CREAR                     = 'Roles Crear';
    const SISTEMA_ROL_EDITAR                    = 'Roles Editar';
    const SISTEMA_ROL_PERMISOS                  = 'Roles Permisos Administrar';
}