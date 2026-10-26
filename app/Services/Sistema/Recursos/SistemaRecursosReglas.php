<?php

namespace App\Services\Sistema\Recursos;

use App\Services\Sistema\LibroExcel\SistemaLibroExcelReglas;

/**
 * Excel de recursos: catálogos que definen cómo trabaja la empresa (campos, labores, asistencia, parámetros, AFP,
 * maquinarias, grupos de cuadrilla, roles y usuarios). El mismo archivo se descarga y se sube en cualquier dominio, así
 * los dos sistemas se mantienen con la misma data.
 *
 * Cada hoja: [título, columnas que identifican la fila, columnas]. Columna: t = encabezado, tipo (texto, entero,
 * decimal, fecha, sino), req = obligatoria, op = lista de valores (OPCIONES), ref = debe existir en otra hoja
 * (hoja.columna), def = valor si viene vacía, lectura = solo informativa (no se importa), unico = no se repite.
 * Al importar nunca se borra nada: lo que está en el sistema y no en el Excel solo se informa.
 */
class SistemaRecursosReglas extends SistemaLibroExcelReglas
{
    public const NOMBRE_ARCHIVO = 'RECURSOS.xlsx';
    public const PREFIJO_ARCHIVO = 'RECURSOS';
    public const TITULO = 'Excel de recursos';
    public const MATRIZ = self::ROLES;
    public const TITULO_MATRIZ = self::TITULO_ROLES;

    /** La clave inicial de un usuario nuevo es la parte de su correo antes de la @ + este sufijo (flaviajg2026). */
    public const SUFIJO_CLAVE = '2026';

    public const ROLES = 'roles';

    public const HOJAS = [
        // Una sola fila: datos de la empresa de este dominio (los usa el kardex en Excel y caja)
        'empresa' => ['Empresa', ['ruc'], [
            'ruc' => ['t' => 'RUC', 'tipo' => 'texto', 'req' => true],
            'razon_social' => ['t' => 'Razón social', 'tipo' => 'texto', 'req' => true],
            'establecimiento' => ['t' => 'Establecimiento (dirección)', 'tipo' => 'texto', 'req' => true],
        ]],
        'campos' => ['Campos', ['nombre'], [
            'nombre' => ['t' => 'Campo', 'tipo' => 'texto', 'req' => true],
            'padre' => ['t' => 'Campo padre', 'tipo' => 'texto', 'ref' => 'campos.nombre'],
            'grupo' => ['t' => 'Grupo', 'tipo' => 'texto'],
            'orden' => ['t' => 'Orden', 'tipo' => 'entero'],
            'etapa' => ['t' => 'Etapa', 'tipo' => 'texto'],
            'area' => ['t' => 'Área (ha)', 'tipo' => 'decimal'],
            'alias' => ['t' => 'Alias (separados por coma)', 'tipo' => 'texto'],
            'bloqueado' => ['t' => 'Bloqueado (ya no se usa)', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'mano_obra' => ['Mano de obra', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true, 'unico' => true],
        ]],
        'tipos_suspension' => ['Tipos de suspensión', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'grupo' => ['t' => 'Grupo', 'tipo' => 'texto', 'req' => true, 'op' => 'grupo_suspension'],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'descripcion_corta' => ['t' => 'Descripción corta', 'tipo' => 'texto', 'req' => true],
            'incluye_domingos' => ['t' => 'Incluye domingos', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'tipos_asistencia' => ['Tipos de asistencia', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'horas_jornal' => ['t' => 'Horas de jornal', 'tipo' => 'entero', 'def' => '8'],
            'color' => ['t' => 'Color', 'tipo' => 'texto'],
            'tipo' => ['t' => 'Tipo', 'tipo' => 'texto', 'op' => 'tipo_asistencia', 'def' => 'ASISTENCIA'],
            'afecta_sueldo' => ['t' => 'Afecta sueldo', 'tipo' => 'sino', 'def' => 'SI'],
            'porcentaje_remunerado' => ['t' => '% remunerado', 'tipo' => 'decimal', 'def' => '100'],
            'requiere_documento' => ['t' => 'Requiere documento', 'tipo' => 'sino', 'def' => 'NO'],
            'acumula_vacaciones' => ['t' => 'Acumula vacaciones', 'tipo' => 'sino', 'def' => 'NO'],
            'acumula_asistencia' => ['t' => 'Acumula asistencia', 'tipo' => 'sino', 'def' => 'NO'],
            'tipo_suspension' => ['t' => 'Tipo de suspensión (código)', 'tipo' => 'texto', 'ref' => 'tipos_suspension.codigo'],
            'sin_suspension' => ['t' => 'Sin suspensión', 'tipo' => 'sino', 'def' => 'NO'],
            'rol' => ['t' => 'Rol', 'tipo' => 'texto', 'op' => 'rol_asistencia'],
            'criterio_bono' => ['t' => 'Criterio bono asistencia', 'tipo' => 'texto', 'op' => 'criterio_bono', 'def' => 'no_afecta'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        // Unidades en que se mide lo que produce una labor (KG, LAVADOR, CAJA…)
        'unidades_produccion' => ['Unidades de producción', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'nombre' => ['t' => 'Nombre', 'tipo' => 'texto', 'req' => true],
            'decimales' => ['t' => 'Decimales', 'tipo' => 'entero', 'def' => '2'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'labores' => ['Labores', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'entero', 'req' => true],
            'nombre' => ['t' => 'Labor', 'tipo' => 'texto', 'req' => true],
            'mano_obra' => ['t' => 'Mano de obra (código)', 'tipo' => 'texto', 'ref' => 'mano_obra.codigo'],
            'tipo_asistencia' => ['t' => 'Tipo de asistencia (código)', 'tipo' => 'texto', 'ref' => 'tipos_asistencia.codigo'],
            'rol_campania' => ['t' => 'Rol en la campaña', 'tipo' => 'texto', 'op' => 'rol_campania'],
            'vigente_desde' => ['t' => 'Vigente desde', 'tipo' => 'fecha'],
            'estandar' => ['t' => 'Estándar de producción', 'tipo' => 'entero'],
            'registra_produccion' => ['t' => 'Registra producción', 'tipo' => 'sino', 'def' => 'NO'],
            'unidades' => ['t' => 'Unidad de producción (código)', 'tipo' => 'texto', 'ref' => 'unidades_produccion.codigo'],
            'se_paga_con_jornal' => ['t' => 'Bono se paga con el jornal', 'tipo' => 'sino', 'def' => 'NO'],
            'activa' => ['t' => 'Activa', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'tramos' => ['Tramos de bono', ['codigo', 'n'], [
            'codigo' => ['t' => 'Código de labor', 'tipo' => 'entero', 'req' => true, 'ref' => 'labores.codigo'],
            'hasta' => ['t' => 'Hasta', 'tipo' => 'decimal', 'req' => true],
            'monto' => ['t' => 'Monto (S/)', 'tipo' => 'decimal', 'req' => true],
        ]],
        'riego' => ['Labores de riego', ['nombre'], [
            'nombre' => ['t' => 'Labor', 'tipo' => 'texto', 'req' => true],
            'es_riego' => ['t' => 'Es riego', 'tipo' => 'sino', 'def' => 'NO'],
            'es_apoyo_riego' => ['t' => 'Es apoyo de riego', 'tipo' => 'sino', 'def' => 'NO'],
            'consumo_m3_hora' => ['t' => 'Consumo m³/hora', 'tipo' => 'decimal'],
        ]],
        'nutrientes' => ['Nutrientes', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'nombre' => ['t' => 'Nombre', 'tipo' => 'texto', 'req' => true],
            'unidad' => ['t' => 'Unidad', 'tipo' => 'texto', 'def' => '%'],
        ]],
        'configuracion' => ['Configuración', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'valor' => ['t' => 'Valor', 'tipo' => 'texto'],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto'],
        ]],
        'configuracion_fecha' => ['Configuración por fecha', ['codigo', 'desde'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true, 'ref' => 'configuracion.codigo'],
            'valor' => ['t' => 'Valor', 'tipo' => 'decimal', 'req' => true],
            'desde' => ['t' => 'Desde (día 1 del mes)', 'tipo' => 'fecha', 'req' => true],
            'hasta' => ['t' => 'Hasta (se calcula sola)', 'tipo' => 'fecha', 'lectura' => true],
            'motivo' => ['t' => 'Motivo del cambio', 'tipo' => 'texto'],
        ]],
        'sp' => ['Descuentos SP', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'referencia' => ['t' => 'AFP / sistema', 'tipo' => 'texto', 'req' => true],
            'orden' => ['t' => 'Orden', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'tipo' => ['t' => 'Tipo de comisión', 'tipo' => 'texto'],
            'porcentaje' => ['t' => '%', 'tipo' => 'decimal', 'req' => true],
            'porcentaje_65' => ['t' => '% mayores de 65', 'tipo' => 'decimal', 'req' => true],
            'color' => ['t' => 'Color', 'tipo' => 'texto'],
        ]],
        'sp_fecha' => ['Descuentos SP por fecha', ['codigo', 'desde'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true, 'ref' => 'sp.codigo'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'hasta' => ['t' => 'Hasta', 'tipo' => 'fecha'],
            'porcentaje' => ['t' => '%', 'tipo' => 'decimal', 'req' => true],
            'porcentaje_65' => ['t' => '% mayores de 65', 'tipo' => 'decimal', 'req' => true],
        ]],
        'afp' => ['AFP primas y comisiones', ['afp', 'desde'], [
            'afp' => ['t' => 'AFP', 'tipo' => 'texto', 'req' => true],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'comision_flujo' => ['t' => 'Comisión flujo %', 'tipo' => 'decimal'],
            'comision_saldo' => ['t' => 'Comisión saldo %', 'tipo' => 'decimal'],
            'prima_seguros' => ['t' => 'Prima de seguros %', 'tipo' => 'decimal'],
            'aporte_obligatorio' => ['t' => 'Aporte obligatorio %', 'tipo' => 'decimal'],
            'remuneracion_maxima' => ['t' => 'Remuneración máxima asegurable', 'tipo' => 'decimal'],
            'motivo' => ['t' => 'Motivo del cambio', 'tipo' => 'texto'],
        ]],
        'maquinarias' => ['Maquinarias', ['nombre'], [
            'nombre' => ['t' => 'Nombre', 'tipo' => 'texto', 'req' => true],
            'alias_blanco' => ['t' => 'Alias kardex blanco', 'tipo' => 'texto'],
            'combustible' => ['t' => 'Combustible (producto)', 'tipo' => 'texto'],
            'usa_distribucion' => ['t' => 'Usa distribución', 'tipo' => 'sino', 'def' => 'SI'],
            'consumo_modo' => ['t' => 'Modo de consumo', 'tipo' => 'texto', 'op' => 'consumo_modo'],
            'consumo_estimado' => ['t' => 'Consumo estimado', 'tipo' => 'decimal'],
            'placa' => ['t' => 'Placa', 'tipo' => 'texto'],
        ]],
        'grupos' => ['Grupos de cuadrilla', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'nombre' => ['t' => 'Nombre', 'tipo' => 'texto', 'req' => true],
            'color' => ['t' => 'Color', 'tipo' => 'texto', 'req' => true],
            'modalidad_pago' => ['t' => 'Modalidad de pago', 'tipo' => 'texto', 'op' => 'modalidad_pago', 'def' => 'mensual'],
            'costo_dia' => ['t' => 'Costo día sugerido', 'tipo' => 'decimal', 'req' => true],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'observaciones' => ['Observaciones cochinilla', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'es_cosecha_mama' => ['t' => 'Es cosecha mamá', 'tipo' => 'sino', 'def' => 'NO'],
            'es_vendible' => ['t' => 'Es vendible', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'usuarios' => ['Usuarios', ['correo'], [
            'nombre' => ['t' => 'Nombre', 'tipo' => 'texto', 'req' => true],
            'correo' => ['t' => 'Correo', 'tipo' => 'texto', 'req' => true],
            'roles' => ['t' => 'Roles (separados por coma)', 'tipo' => 'texto'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
            'clave' => ['t' => 'Clave nueva', 'tipo' => 'texto', 'secreta' => true],
            'clave_inicial' => ['t' => 'Clave si es nuevo (referencia)', 'tipo' => 'texto', 'lectura' => true],
        ]],
    ];

    /** Hoja de roles: una fila por permiso y una columna por rol (X = tiene el permiso). Super Admin tiene todo. */
    public const TITULO_ROLES = 'Roles y permisos';

    public const OPCIONES = [
        'sino' => ['SI', 'NO'],
        'grupo_suspension' => ['SP', 'SI'],
        'tipo_asistencia' => ['ASISTENCIA', 'PERMISO', 'LICENCIA', 'VACACIONES'],
        'rol_asistencia' => ['renuncia'],
        'criterio_bono' => ['no_afecta', 'afecta', 'revisar'],
        'rol_campania' => ['cosecha', 'neutra'],
        'modalidad_pago' => ['mensual', 'quincenal', 'semanal', 'variado'],
        'consumo_modo' => ['km', 'hora'],
    ];

    public static function claveInicial(string $correo): string
    {
        return mb_strtolower(trim(explode('@', $correo)[0])) . self::SUFIJO_CLAVE;
    }
}
