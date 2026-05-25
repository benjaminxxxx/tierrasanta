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
 *    - CAMPO        → campos, labores, siembras, reg_*
 *    - CAMPAÑA      → campos_campanias, pesticidas_campanias
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
    const EVALUACION = 'Evaluación de Campo';

    // — Submódulo: Población de Plantas
    const PLANTA_EVALUACION = 'Evaluación de Campo Población Plantas';
    const PLANTA_EVALUACION_VER = 'Evaluación de Campo Población Plantas Ver';
    const PLANTA_EVALUACION_CREAR = 'Evaluación de Campo Población Plantas Crear Evaluación';
    const PLANTA_EVALUACION_EDITAR = 'Evaluación de Campo Población Plantas Editar Evaluación';
    const PLANTA_EVALUACION_ELIMINAR = 'Evaluación de Campo Población Plantas Eliminar Evaluación';
    const PLANTA_EVALUACION_REPORTE = 'Evaluación de Campo Población Plantas Ver Reporte';

    // — Submódulo: Brotes x Piso
    const BROTE_EVALUACION = 'Evaluación de Campo Brotes x Piso';
    const BROTE_EVALUACION_VER = 'Evaluación de Campo Brotes x Piso Ver';
    const BROTE_EVALUACION_CREAR = 'Evaluación de Campo Brotes x Piso Crear Evaluación';
    const BROTE_EVALUACION_EDITAR = 'Evaluación de Campo Brotes x Piso Editar Evaluación';
    const BROTE_EVALUACION_ELIMINAR = 'Evaluación de Campo Brotes x Piso Eliminar Evaluación';
    const BROTE_EVALUACION_REPORTE = 'Evaluación de Campo Brotes x Piso Ver Reporte';

    // — Submódulo: Infestación Cosecha
    const INFESTACION_EVALUACION = 'Evaluación de Campo Infestación Cosecha';
    const INFESTACION_EVALUACION_VER = 'Evaluación de Campo Infestación Cosecha Ver';
    const INFESTACION_EVALUACION_REGISTRAR = 'Evaluación de Campo Infestación Cosecha Registrar Evaluación';

    // — Submódulo: Proyección Rendimiento Poda
    const PROYECCION_EVALUACION = 'Evaluación de Campo Proyección Rendimiento';
    const PROYECCION_EVALUACION_GUARDAR = 'Evaluación de Campo Proyección Rendimiento Guardar Proyección';
    const PROYECCION_EVALUACION_DETALLE = 'Evaluación de Campo Proyección Rendimiento Registrar Detalle';
    // =========================================================================
// DOMINIO: CAMPAÑA (campañas agrícolas, costos, resúmenes por campo)
// Tablas: campos_campanias, campos_campanias_consumos,
//         , pesticidas_campanias,
//         costo_mensual_distribuciones
// =========================================================================

    // — Módulo raíz
    const CAMPAÑA = 'Campañas';

    // — Resumen General (+ gestión de campañas desde aquí)
    const CAMPAÑA_RESUMEN = 'Campañas Resumen General';
    const CAMPAÑA_RESUMEN_VER = 'Campañas Resumen General Ver';
    const CAMPAÑA_GESTIONAR = 'Campañas Gestionar';

    // — Calendario (todas las campañas, solo lectura)
    const CAMPAÑA_CALENDARIO = 'Campañas Calendario';
    const CAMPAÑA_CALENDARIO_VER = 'Campañas Calendario Ver';

    // — Por Campo (v2)
    const CAMPAÑA_POR_CAMPO = 'Campañas Por Campo';
    const CAMPAÑA_POR_CAMPO_VER = 'Campañas Por Campo Ver';
    const CAMPAÑA_POR_CAMPO_GESTIONAR = 'Campañas Por Campo Gestionar';
    const CAMPAÑA_COSTOS = 'Campañas Costo';
    const CAMPAÑA_COSTOS_VER = 'Campañas Costo Ver';
    const CAMPAÑA_COSTOS_GESTIONAR = 'Campañas Costo Gestionar';

    // =========================================================================
    // DOMINIO: PERSONAL (empleados, familiares, contratos)
    // Tablas: plan_empleados, plan_familiares, plan_contratos, plan_sueldos
    // =========================================================================

    const PERSONAL = 'Planilla Empleados';
    const PERSONAL_VER = 'Planilla Empleados Ver';
    const PERSONAL_CREAR = 'Planilla Empleados Crear Empleado';
    const PERSONAL_EDITAR = 'Planilla Empleados Editar Empleado';
    const PERSONAL_ELIMINAR = 'Planilla Empleados Eliminar';
    const PERSONAL_RESTAURAR = 'Planilla Empleados Restaurar Empleado';
    const PERSONAL_CONTRATOS = 'Planilla Empleados Gestionar Contratos y Sueldos';
    const PERSONAL_FAMILIARES = 'Planilla Empleados Gestionar Familiares';
    const PERSONAL_OPCIONES = 'Planilla Empleados Gestionar Opciones';



    // =========================================================================
// DOMINIO: CUADRILLA
// Tablas: cuad_cuadrilleros, cuad_grupos, cuad_tramos_laborales,
//         cuad_registros_diarios, cuad_bonos_actividades, cuad_resumen_tramos
// =========================================================================

    const CUADRILLA = 'Cuadrilla';

    // — Panel (solo acceso, sin sub-permisos)
    const CUADRILLA_PANEL = 'Cuadrilla Panel Cuadrilleros';

    // — Lista de cuadrilleros
    const CUADRILLA_LISTA = 'Cuadrilla Lista Cuadrilleros';
    const CUADRILLA_LISTA_GESTIONAR = 'Cuadrilla Lista Cuadrilleros Gestionar';

    // — Grupos
    const CUADRILLA_GRUPO = 'Cuadrilla Grupos';
    const CUADRILLA_GRUPO_GESTIONAR = 'Cuadrilla Grupos Gestionar';
    const CUADRILLA_GRUPO_VER_ELIMINADOS = 'Cuadrilla Grupos Ver Eliminados';

    // — Reporte Semanal
    const CUADRILLA_SEMANAL = 'Cuadrilla Reporte Semanal';
    const CUADRILLA_SEMANAL_GESTIONAR_TRAMO = 'Cuadrilla Reporte Semanal Gestionar Tramo';
    const CUADRILLA_SEMANAL_AGREGAR_GRUPOS = 'Cuadrilla Reporte Semanal Agregar Grupos y Cuadrillas';
    const CUADRILLA_SEMANAL_ASIGNAR_COSTOS = 'Cuadrilla Reporte Semanal Asignar Costos por Jornal';
    const CUADRILLA_SEMANAL_GESTIONAR_GASTOS = 'Cuadrilla Reporte Semanal Gestionar Gastos Adicionales';
    const CUADRILLA_SEMANAL_GESTIONAR_HORAS = 'Cuadrilla Reporte Semanal Gestionar Horas';

    // — Reporte Diario
    const CUADRILLA_DIARIO = 'Cuadrilla Reporte Diario';
    const CUADRILLA_DIARIO_GESTIONAR = 'Cuadrilla Reporte Diario Gestionar';

    // — Bonificaciones (sin Ver hijo — el acceso al módulo ya lo garantiza el padre)
    const CUADRILLA_BONIFICACION = 'Cuadrilla Bonificaciones';
    const CUADRILLA_BONIFICACION_AGREGAR_METODO = 'Cuadrilla Bonificaciones Agregar Método';
    const CUADRILLA_BONIFICACION_AGREGAR_RECOJO = 'Cuadrilla Bonificaciones Agregar Recojo';
    const CUADRILLA_BONIFICACION_ACTUALIZAR = 'Cuadrilla Bonificaciones Actualizar Bonificación';

    // — Resumen General
    const CUADRILLA_RESUMEN_GENERAL = 'Cuadrilla Resumen General';
    const CUADRILLA_RESUMEN_GENERAL_EXPORTAR = 'Cuadrilla Resumen General Exportar';

    // — Resumen Anual
    const CUADRILLA_RESUMEN_ANUAL = 'Cuadrilla Resumen Anual';
    const CUADRILLA_RESUMEN_ANUAL_EXPORTAR = 'Cuadrilla Resumen Anual Exportar';

    // =========================================================================
// DOMINIO: SISTEMA (usuarios, roles, auditoría, configuración)
// Tablas: users, roles, permissions, model_has_roles,
//         role_has_permissions, model_has_permissions
// =========================================================================

    // — Módulo raíz
    const SISTEMA = 'Sistema';

    // — Usuarios
    const SISTEMA_USUARIO = 'Usuarios Administrar';
    const SISTEMA_USUARIO_VER = 'Usuarios Ver';
    const SISTEMA_USUARIO_GESTIONAR = 'Usuarios Gestionar';
    const SISTEMA_USUARIO_ROL_PERMISOS = 'Usuarios Permisos x Rol Gestionar';

    // — Roles
    const SISTEMA_ROL = 'Roles';
    const SISTEMA_ROL_VER = 'Roles Ver';
    const SISTEMA_ROL_GESTIONAR = 'Roles Gestionar';

    // =========================================================================
    // DOMINIO: PLANILLA (asistencia, registros diarios, resúmenes, liquidaciones)
    // Tablas: plan_mensuales, plan_registros_diarios, plan_periodos,
    //         plan_permisos, plan_suspensiones, plan_conceptos_configs,
    //         plan_resumen_diario, plan_mensual_detalles, parametros_mensuales
    // =========================================================================

    // — Módulo raíz
    const PLANILLA = 'Planilla';

    // — Actividades Diarias
    const PLANILLA_ACTIVIDAD = 'Planilla Actividades Diarias';
    const PLANILLA_ACTIVIDAD_VER = 'Planilla Actividades Diarias Ver';
    const PLANILLA_ACTIVIDAD_GESTIONAR = 'Planilla Actividades Diarias Gestionar';

    // — Asistencia Mensual
    const PLANILLA_ASISTENCIA = 'Planilla Asistencia Mensual';
    const PLANILLA_ASISTENCIA_VER = 'Planilla Asistencia Mensual Ver';

    // — Permisos y Suspensiones
    const PLANILLA_SUSPENSION = 'Planilla Permisos y Suspensiones';
    const PLANILLA_SUSPENSION_VER = 'Planilla Permisos y Suspensiones Ver';
    const PLANILLA_SUSPENSION_GESTIONAR = 'Planilla Permisos y Suspensiones Gestionar';

    // — Resumen Mensual
    const PLANILLA_RESUMEN_MENSUAL = 'Planilla Resumen Mensual';
    const PLANILLA_RESUMEN_MENSUAL_VER = 'Planilla Resumen Mensual Ver';

    // — Resumen General
    const PLANILLA_RESUMEN_GENERAL = 'Planilla Resumen General';
    const PLANILLA_RESUMEN_GENERAL_VER = 'Planilla Resumen General Ver';

    // — Planilla Blanco (B+N)
    const PLANILLA_BLANCO = 'Planilla Blanco';
    const PLANILLA_BLANCO_VER = 'Planilla Blanco Ver';
    const PLANILLA_BLANCO_GESTIONAR = 'Planilla Blanco Gestionar';

    // — Familiares (vista general de asignación familiar desde planilla)
    const PLANILLA_FAMILIAR = 'Planilla Familiares';
    const PLANILLA_FAMILIAR_VER = 'Planilla Familiares Ver';
    const PLANILLA_FAMILIAR_GESTIONAR = 'Planilla Familiares Gestionar';

    // — Conceptos
    const PLANILLA_CONCEPTO = 'Planilla Conceptos';
    const PLANILLA_CONCEPTO_VER = 'Planilla Conceptos Ver';
    const PLANILLA_CONCEPTO_GESTIONAR = 'Planilla Conceptos Gestionar';

    // — Parámetros
    const PLANILLA_PARAMETRO = 'Planilla Parámetros';
    const PLANILLA_PARAMETRO_VER = 'Planilla Parámetros Ver';
    const PLANILLA_PARAMETRO_GESTIONAR = 'Planilla Parámetros Gestionar';

    // =========================================================================
    // DOMINIO: COCHINILLA
    // Tablas: cochinilla_*, venta_cochinillas, venta_facturada_cochinillas
    // =========================================================================

    const COCHINILLA = 'Cochinilla';
    const COCHINILLA_INGRESO = 'Cochinilla Ingreso';
    const COCHINILLA_VENTEADO = 'Cochinilla Venteado';
    const COCHINILLA_FILTRADO = 'Cochinilla Filtrado';
    const COCHINILLA_COSECHA = 'Cochinilla Cosecha Mamas';
    const COCHINILLA_INFESTACION = 'Cochinilla Infestación';
    // =========================================================================
// COCHINILLA — Venta
// Tablas: venta_cochinillas, venta_facturada_cochinillas, venta_cochinilla_reportes
// Roles típicos: Registrador de campo (entrega), Contabilidad (costos/facturación)
// =========================================================================

    const COCHINILLA_VENTA = 'Cochinilla Venta';

    // — Entrega (registrador de campo)
    const COCHINILLA_VENTA_ENTREGA_VER = 'Cochinilla Venta Ver Entrega';
    const COCHINILLA_VENTA_ENTREGA_REGISTRAR = 'Cochinilla Venta Registrar Entrega';

    // — Reporte de venta (supervisor / administración)
    const COCHINILLA_VENTA_REPORTE_VER = 'Cochinilla Venta Ver Reporte';
    const COCHINILLA_VENTA_REPORTE_GESTIONAR = 'Cochinilla Venta Gestionar Reporte';

    // — Costo y facturación (contabilidad)
    const COCHINILLA_VENTA_FACTURACION_VER = 'Cochinilla Venta Ver Facturación';
    const COCHINILLA_VENTA_FACTURACION_GESTIONAR = 'Cochinilla Venta Gestionar Facturación';
    // =========================================================================
// DOMINIO: CAMPO (labores, mano de obra, campos, siembras)
// Tablas: campos, labores, mano_obras, siembras, reg_labores,
//         reg_registro_diario, reg_resumen, reg_horas_acumuladas
// Nota: CAMPO_RIEGO es un submódulo separado por volumen y roles distintos.
//       Este bloque cubre la gestión base del campo agrícola.
// =========================================================================

    // — Módulo raíz
    const CAMPO = 'Campo';

    // — Labores de Campo
    const CAMPO_LABOR = 'Campo Labores';
    const CAMPO_LABOR_VER = 'Campo Labores Ver';
    const CAMPO_LABOR_GESTIONAR = 'Campo Labores Gestionar';

    // — Mano de Obra
    const CAMPO_MANO_OBRA = 'Campo Mano de Obra';
    const CAMPO_MANO_OBRA_VER = 'Campo Mano de Obra Ver';
    const CAMPO_MANO_OBRA_GESTIONAR = 'Campo Mano de Obra Gestionar';

    // — Campos (parcelas / unidades de cultivo)
    const CAMPO_PARCELA = 'Campo Campos';
    const CAMPO_PARCELA_VER = 'Campo Campos Ver';
    const CAMPO_PARCELA_GESTIONAR = 'Campo Campos Gestionar';

    // — Siembras
    const CAMPO_SIEMBRA = 'Campo Siembras';
    const CAMPO_SIEMBRA_VER = 'Campo Siembras Ver';
    const CAMPO_SIEMBRA_GESTIONAR = 'Campo Siembras Gestionar';

    // =========================================================================
// DOMINIO: CAMPO — Riego
// Tablas: reg_registro_diario, reg_labores, reg_resumen,
//         labores (tipo riego), campos
// Nota: Riego es un submódulo de CAMPO. Prefijo CAMPO_RIEGO para
//       diferenciarlo de labores de campo general (CAMPO_LABOR).
// =========================================================================

    // — Módulo raíz
    const CAMPO_RIEGO = 'Riego';

    // — Reporte Diario de Regadores
    const CAMPO_RIEGO_REPORTE = 'Riego Reporte Diario';
    const CAMPO_RIEGO_REPORTE_VER = 'Riego Reporte Diario Ver';
    const CAMPO_RIEGO_REPORTE_GESTIONAR = 'Riego Reporte Diario Gestionar';

    // — Labores en Riego
    const CAMPO_RIEGO_LABOR = 'Riego Labores';
    const CAMPO_RIEGO_LABOR_VER = 'Riego Labores Ver';
    const CAMPO_RIEGO_LABOR_GESTIONAR = 'Riego Labores Gestionar';

    // — Estado de Riegos
    const CAMPO_RIEGO_ESTADO = 'Riego Estado';
    const CAMPO_RIEGO_ESTADO_VER = 'Riego Estado Ver';

    // — Resumen Diario de Riegos
    const CAMPO_RIEGO_RESUMEN = 'Riego Resumen Diario';
    const CAMPO_RIEGO_RESUMEN_VER = 'Riego Resumen Diario Ver';

    // =========================================================================
    // DOMINIO: INSUMO (catálogo, compras, almacén, kardex)
    // Tablas: productos, ins_categorias, ins_subcategorias, ins_usos,
    //         nutrientes, producto_nutrientes, tabla_concentracion,
    //         compra_productos, almacen_producto_salidas, distribucion_combustibles,
    //         ins_kardexes, ins_kardex_movimientos, ins_kardex_reportes,
    //         ins_kardex_reporte_detalles, ins_kardex_reporte_categorias
    // =========================================================================

    // — Módulo raíz catálogo
    const INSUMO_CATALOGO = 'Producto y Nutrientes';

    // — Productos
    const INSUMO_PRODUCTO = 'Productos';
    const INSUMO_PRODUCTO_VER = 'Productos Ver';
    const INSUMO_PRODUCTO_GESTIONAR = 'Productos Gestionar';

    // — Categorías
    const INSUMO_CATEGORIA = 'Producto Categorías';
    const INSUMO_CATEGORIA_VER = 'Producto Categorías Ver';
    const INSUMO_CATEGORIA_GESTIONAR = 'Producto Categorías Gestionar';

    // — Subcategorías
    const INSUMO_SUBCATEGORIA = 'Producto Subcategorías';
    const INSUMO_SUBCATEGORIA_VER = 'Producto Subcategorías Ver';
    const INSUMO_SUBCATEGORIA_GESTIONAR = 'Producto Subcategorías Gestionar';

    // — Usos (fines/aplicaciones de cada producto)
    const INSUMO_USO = 'Producto Usos';
    const INSUMO_USO_VER = 'Producto Usos Ver';
    const INSUMO_USO_GESTIONAR = 'Producto Usos Gestionar';

    // — Nutrientes (solo lectura por ahora)
    const INSUMO_NUTRIENTE = 'Producto y Nutrientes Nutrientes';
    const INSUMO_NUTRIENTE_VER = 'Producto y Nutrientes Nutrientes Ver';

    // — Tabla de Concentración
    const INSUMO_CONCENTRACION = 'Producto y Nutrientes Tabla Concentración';
    const INSUMO_CONCENTRACION_VER = 'Producto y Nutrientes Tabla Concentración Ver';
    const INSUMO_CONCENTRACION_GESTIONAR = 'Producto y Nutrientes Tabla Concentración Gestionar';

    // — Módulo raíz almacén/kardex

    // — Módulo raíz
    const INSUMO = 'Kardex y Almacén';

    // — Compras (quien no tiene GESTIONAR solo ve y filtra la tabla)
    const INSUMO_COMPRA = 'Kardex y Almacén Compras';
    const INSUMO_COMPRA_GESTIONAR = 'Kardex y Almacén Compras Gestionar';

    // — Salida de Almacén Pesticidas y Fertilizantes
    const INSUMO_SALIDA = 'Kardex y Almacén Salida Insumos';
    const INSUMO_SALIDA_GESTIONAR = 'Kardex y Almacén Salida Insumos Gestionar';

    // — Salida de Combustible
    const INSUMO_COMBUSTIBLE = 'Kardex y Almacén Salida Combustible';
    const INSUMO_COMBUSTIBLE_GESTIONAR = 'Kardex y Almacén Salida Combustible Gestionar';

    // — Distribución de Combustible
    const INSUMO_DISTRIBUCION = 'Kardex y Almacén Distribución Combustible';
    const INSUMO_DISTRIBUCION_GESTIONAR = 'Kardex y Almacén Distribución Combustible Gestionar';

    // — Kardex de Insumos
    const INSUMO_KARDEX = 'Kardex y Almacén Kardex';
    const INSUMO_KARDEX_CREAR = 'Kardex y Almacén Kardex Crear';
    const INSUMO_KARDEX_ELIMINAR = 'Kardex y Almacén Kardex Eliminar';
    const INSUMO_KARDEX_ASIGNAR_MOVIMIENTOS = 'Kardex y Almacén Kardex Asignar Entradas y Salidas';
    const INSUMO_KARDEX_GENERAR_RESUMEN = 'Kardex y Almacén Kardex Generar Resumen';
    const INSUMO_KARDEX_IMPORTAR = 'Kardex y Almacén Kardex Importar Masivo';

    // — Reporte de Kardex
    const INSUMO_KARDEX_REPORTE = 'Kardex y Almacén Kardex Reporte';
    const INSUMO_KARDEX_REPORTE_VER = 'Kardex y Almacén Kardex Reporte Ver';
    const INSUMO_KARDEX_REPORTE_CREAR = 'Kardex y Almacén Kardex Reporte Crear';
    const INSUMO_KARDEX_REPORTE_ELIMINAR = 'Kardex y Almacén Kardex Reporte Eliminar';
    const INSUMO_KARDEX_REPORTE_GENERAR_RESUMEN = 'Kardex y Almacén Kardex Reporte Generar Resumen';

    // =========================================================================
// DOMINIO: CONTABILIDAD
// Tablas: costos_mensuales, costo_fdm_mensuals, costo_mano_indirectas,
//         costo_mensual_distribuciones, kardex_consolidados
// =========================================================================

    // — Módulo raíz
    const CONTABILIDAD = 'Contabilidad';

    // — Costos FDM
    const CONTABILIDAD_FDM = 'Contabilidad Costos FDM';
    const CONTABILIDAD_FDM_VER = 'Contabilidad Costos FDM Ver';
    const CONTABILIDAD_FDM_GESTIONAR = 'Contabilidad Costos FDM Gestionar';

    // — Costos Mensuales
    const CONTABILIDAD_COSTO_MENSUAL_LISTA = 'Contabilidad Costos Mensuales';
    const CONTABILIDAD_COSTO_MENSUAL_LISTA_VER = 'Contabilidad Costos Mensuales Ver';
    const CONTABILIDAD_COSTO_MENSUAL_LISTA_GESTIONAR = 'Contabilidad Costos Mensuales Gestionar';

    // — Costo Mensual (detalle individual)
    const CONTABILIDAD_COSTO_MENSUAL = 'Contabilidad Costo Mensual';
    const CONTABILIDAD_COSTO_MENSUAL_VER = 'Contabilidad Costo Mensual Ver';
    const CONTABILIDAD_COSTO_MENSUAL_GESTIONAR = 'Contabilidad Costo Mensual Gestionar';
    

    // =========================================================================
// DOMINIO: INSUMO — Proveedores
// Tablas: proveedores (vinculados a compra_productos)
// =========================================================================

    const INSUMO_PROVEEDOR = 'Proveedores';
    const INSUMO_PROVEEDOR_VER = 'Proveedores Ver';
    const INSUMO_PROVEEDOR_GESTIONAR = 'Proveedores Gestionar';

    // =========================================================================
// DOMINIO: CAMPO — Maquinarias
// Tablas: maquinarias, detalle_maquinaria_consumos
// =========================================================================

    const CAMPO_MAQUINARIA = 'Maquinarias';
    const CAMPO_MAQUINARIA_VER = 'Maquinarias Ver';
    const CAMPO_MAQUINARIA_GESTIONAR = 'Maquinarias Gestionar';



    // =========================================================================
// DOMINIO: REPORTE (reportes generales + auditoría)
// Tablas: auditorias, rep_actividades_diarias, reporte_costo_planillas,
//         rpt_distribucion_combustibles, v_reporte_actividades_diario
// =========================================================================

    const REPORTE = 'Reporte y Auditoría';
    const REPORTE_DIARIO = 'Reporte Diario';
    const REPORTE_DIARIO_VER = 'Reporte Diario Ver';
    const REPORTE_MENSUAL = 'Reporte Mensual';
    const REPORTE_MENSUAL_VER = 'Reporte Mensual Ver';
    const REPORTE_ANUAL = 'Reporte Anual';
    const REPORTE_ANUAL_VER = 'Reporte Anual Ver';
    const REPORTE_AUDITORIA = 'Auditoría';
    const REPORTE_AUDITORIA_VER = 'Auditoría Ver';

    // =========================================================================
// DOMINIO: PLANILLA — Configuración
// Tablas: plan_sp_desc, plan_sp_desc_hist, plan_tipo_asistencias
// Nota: Estas configuraciones aplican SOLO a trabajadores de planilla
//       (contrato formal con descuentos, seguros, AFP).
//       No aplican a cuadrilleros (pago por jornal/producción).
// =========================================================================

    const PLANILLA_CONFIG_AFP = 'Configuración Descuentos AFP';
    const PLANILLA_CONFIG_AFP_VER = 'Configuración Descuentos AFP Ver';
    const PLANILLA_CONFIG_AFP_GESTIONAR = 'Configuración Descuentos AFP Gestionar';

    const PLANILLA_CONFIG_ASISTENCIA = 'Configuración Tipo Asistencia';
    const PLANILLA_CONFIG_ASISTENCIA_VER = 'Configuración Tipo Asistencia Ver';
    const PLANILLA_CONFIG_ASISTENCIA_GESTIONAR = 'Configuración Tipo Asistencia Gestionar';
}