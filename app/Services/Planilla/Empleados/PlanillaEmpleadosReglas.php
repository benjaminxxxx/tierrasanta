<?php

namespace App\Services\Planilla\Empleados;

use App\Services\Sistema\LibroExcel\SistemaLibroExcelReglas;

/**
 * Excel de empleados (solo planilla): grupos, cargos, empleados (con su persona), contratos, sueldos, historial de
 * cargos y derecho habientes. El empleado se identifica por su DNI en todas las hojas.
 * Vigencia (SISTEMA_VIGENCIA_DESDE): contratos, sueldos y cargos que terminaron antes no se leen, y los que siguen
 * vigentes empiezan en esa fecha (contrato simulado de la empresa nueva). Sin la variable se leen tal cual.
 */
class PlanillaEmpleadosReglas extends SistemaLibroExcelReglas
{
    public const PREFIJO_ARCHIVO = 'EMPLEADOS';
    public const TITULO = 'Excel de empleados';

    public const HOJAS = [
        'grupos' => ['Grupos de planilla', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'color' => ['t' => 'Color', 'tipo' => 'texto', 'req' => true],
        ]],
        'cargos' => ['Cargos', ['nombre'], [
            'nombre' => ['t' => 'Cargo', 'tipo' => 'texto', 'req' => true],
            'cupo_maximo' => ['t' => 'Cupo máximo', 'tipo' => 'entero'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'empleados' => ['Empleados', ['dni'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true],
            'apellido_paterno' => ['t' => 'Apellido paterno', 'tipo' => 'texto'],
            'apellido_materno' => ['t' => 'Apellido materno', 'tipo' => 'texto'],
            'nombres' => ['t' => 'Nombres', 'tipo' => 'texto', 'req' => true],
            'fecha_nacimiento' => ['t' => 'Fecha de nacimiento', 'tipo' => 'fecha'],
            'genero' => ['t' => 'Género', 'tipo' => 'texto', 'op' => 'genero'],
            'email' => ['t' => 'Correo', 'tipo' => 'texto', 'unico' => true],
            'telefono' => ['t' => 'Teléfono', 'tipo' => 'texto'],
            'direccion' => ['t' => 'Dirección', 'tipo' => 'texto'],
            'fecha_ingreso' => ['t' => 'Fecha de ingreso', 'tipo' => 'fecha'],
            'orden' => ['t' => 'Orden', 'tipo' => 'entero'],
            'comentarios' => ['t' => 'Comentarios', 'tipo' => 'texto'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'contratos' => ['Contratos', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'hasta' => ['t' => 'Hasta', 'tipo' => 'fecha'],
            'tipo_contrato' => ['t' => 'Tipo de contrato', 'tipo' => 'texto', 'op' => 'tipo_contrato', 'def' => 'indefinido'],
            'tipo_planilla' => ['t' => 'Planilla', 'tipo' => 'texto', 'op' => 'tipo_planilla', 'def' => 'agraria'],
            'tipo_ingreso' => ['t' => 'Tipo de ingreso', 'tipo' => 'texto', 'op' => 'tipo_ingreso', 'def' => 'planilla'],
            'grupo' => ['t' => 'Grupo (código)', 'tipo' => 'texto', 'ref' => 'grupos.codigo'],
            'estado' => ['t' => 'Estado', 'tipo' => 'texto', 'op' => 'estado_contrato', 'def' => 'activo'],
            'remuneracion_basica' => ['t' => 'Remuneración básica', 'tipo' => 'decimal'],
            'bonificacion' => ['t' => 'Bonificación', 'tipo' => 'decimal'],
            'compensacion_vacacional' => ['t' => 'Compensación vacacional', 'tipo' => 'decimal'],
            'sistema_pension' => ['t' => 'Sistema de pensión (código SP)', 'tipo' => 'texto'],
            'esta_jubilado' => ['t' => 'Jubilado', 'tipo' => 'sino', 'def' => 'NO'],
            'suspension_cuarta' => ['t' => 'Suspensión de 4ta', 'tipo' => 'sino', 'def' => 'NO'],
            'beneficios_mensuales' => ['t' => 'Beneficios mensuales', 'tipo' => 'sino', 'def' => 'NO'],
            'modalidad_pago' => ['t' => 'Modalidad de pago', 'tipo' => 'texto', 'op' => 'modalidad_pago', 'def' => 'mensual'],
            'metodo_pago' => ['t' => 'Método de pago', 'tipo' => 'texto'],
            'banco' => ['t' => 'Banco', 'tipo' => 'texto'],
            'tipo_cuenta' => ['t' => 'Tipo de cuenta', 'tipo' => 'texto'],
            'moneda_cuenta' => ['t' => 'Moneda de la cuenta', 'tipo' => 'texto', 'op' => 'moneda'],
            'numero_cuenta' => ['t' => 'Número de cuenta', 'tipo' => 'texto'],
            'banco_secundario' => ['t' => 'Banco (diferencia)', 'tipo' => 'texto'],
            'tipo_cuenta_secundaria' => ['t' => 'Tipo de cuenta (diferencia)', 'tipo' => 'texto'],
            'moneda_cuenta_secundaria' => ['t' => 'Moneda (diferencia)', 'tipo' => 'texto', 'op' => 'moneda'],
            'numero_cuenta_secundaria' => ['t' => 'Número de cuenta (diferencia)', 'tipo' => 'texto'],
            'fecha_fin_prueba' => ['t' => 'Fin del periodo de prueba', 'tipo' => 'fecha'],
            'motivo_cese_sunat' => ['t' => 'Motivo de cese (SUNAT)', 'tipo' => 'texto'],
            'comentario_cese' => ['t' => 'Comentario del cese', 'tipo' => 'texto'],
        ]],
        'sueldos' => ['Sueldos', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'hasta' => ['t' => 'Hasta', 'tipo' => 'fecha'],
            'sueldo' => ['t' => 'Sueldo', 'tipo' => 'decimal', 'req' => true],
        ]],
        'cargos_empleado' => ['Cargos por empleado', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'cargo' => ['t' => 'Cargo', 'tipo' => 'texto', 'req' => true, 'ref' => 'cargos.nombre'],
            'grupo' => ['t' => 'Grupo (código)', 'tipo' => 'texto', 'ref' => 'grupos.codigo'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'hasta' => ['t' => 'Hasta', 'tipo' => 'fecha'],
            'motivo' => ['t' => 'Motivo del cambio', 'tipo' => 'texto'],
        ]],
        'derecho_habientes' => ['Derecho habientes', ['documento', 'dni'], [
            'dni' => ['t' => 'DNI del empleado', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'rol' => ['t' => 'El empleado es', 'tipo' => 'texto', 'req' => true, 'op' => 'rol_familiar'],
            'documento' => ['t' => 'Documento del familiar', 'tipo' => 'texto', 'req' => true],
            'tipo_documento' => ['t' => 'Tipo de documento', 'tipo' => 'texto', 'def' => 'DNI'],
            'nombres' => ['t' => 'Nombres del familiar', 'tipo' => 'texto', 'req' => true],
            'fecha_nacimiento' => ['t' => 'Fecha de nacimiento', 'tipo' => 'fecha', 'req' => true],
            'tipo' => ['t' => 'Parentesco', 'tipo' => 'texto', 'req' => true, 'op' => 'parentesco'],
            'discapacidad_severa' => ['t' => 'Discapacidad severa', 'tipo' => 'sino', 'def' => 'NO'],
            'pension_no_contributiva' => ['t' => 'Percibe pensión no contributiva', 'tipo' => 'sino', 'def' => 'NO'],
            'inicio_estudios' => ['t' => 'Inicio de estudios', 'tipo' => 'fecha'],
            'fin_estudios' => ['t' => 'Fin de estudios', 'tipo' => 'fecha'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
    ];

    public const OPCIONES = [
        'genero' => ['M', 'F'],
        'tipo_contrato' => ['plazo fijo', 'indefinido', 'temporal'],
        'tipo_planilla' => ['agraria', 'oficina', 'general', 'mype', 'construccion'],
        'tipo_ingreso' => ['planilla', 'honorarios'],
        'estado_contrato' => ['activo', 'en_prueba', 'finalizado'],
        'modalidad_pago' => ['mensual', 'quincenal', 'semanal'],
        'moneda' => ['PEN', 'USD'],
        'rol_familiar' => ['padre', 'madre', 'conyuge', 'tutor'],
        'parentesco' => ['hijo', 'conyuge'],
    ];

    /** Hojas con periodo (desde / hasta) a las que se aplica la vigencia del sistema. */
    public const HOJAS_CON_PERIODO = ['contratos', 'sueldos', 'cargos_empleado'];
}
