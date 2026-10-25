<?php

namespace App\Services\Planilla\Empleados;

use App\Services\Planilla\Laboral\PlanillaLaboralReglas;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelReglas;

/**
 * Excel de empleados (solo planilla). El empleado se identifica por su DNI en todas las hojas.
 * - Empleados: datos de la persona (sin historial).
 * - Contratos: periodo legal (inicio y fin; puede haber días sin contrato entre uno y otro).
 * - Asignaciones y Remuneraciones: partículas del contrato, solo "Desde" (valen hasta la siguiente o el fin del contrato).
 * - Pensiones y Pagos: partículas del empleado, solo "Desde" (siguen al empleado entre contratos).
 * "Anular = SI" quita esa fila del sistema (contrato o partícula). Nada más se borra.
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
            'orden' => ['t' => 'Orden', 'tipo' => 'entero'],
            'comentarios' => ['t' => 'Comentarios', 'tipo' => 'texto'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
            'usuario' => ['t' => 'Usuario del sistema', 'tipo' => 'texto', 'lectura' => true],
        ]],
        'contratos' => ['Contratos', ['dni', 'inicio'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'inicio' => ['t' => 'Inicio', 'tipo' => 'fecha', 'req' => true],
            'fin' => ['t' => 'Fin', 'tipo' => 'fecha'],
            'tipo_contrato' => ['t' => 'Tipo de contrato', 'tipo' => 'texto', 'op' => 'tipo_contrato', 'def' => 'indefinido'],
            'tipo_planilla' => ['t' => 'Planilla', 'tipo' => 'texto', 'op' => 'tipo_planilla', 'def' => 'agraria'],
            'tipo_ingreso' => ['t' => 'Tipo de ingreso', 'tipo' => 'texto', 'op' => 'tipo_ingreso', 'def' => 'planilla'],
            'fin_prueba' => ['t' => 'Fin del periodo de prueba', 'tipo' => 'fecha'],
            'motivo_cese_sunat' => ['t' => 'Motivo de cese (SUNAT)', 'tipo' => 'texto'],
            'comentario_cese' => ['t' => 'Comentario del cese', 'tipo' => 'texto'],
            'anular' => ['t' => 'Anular', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'asignaciones' => ['Asignaciones', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'cargo' => ['t' => 'Cargo', 'tipo' => 'texto', 'ref' => 'cargos.nombre'],
            'grupo' => ['t' => 'Grupo (código)', 'tipo' => 'texto', 'ref' => 'grupos.codigo'],
            'motivo' => ['t' => 'Motivo', 'tipo' => 'texto'],
            'anular' => ['t' => 'Anular', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'remuneraciones' => ['Remuneraciones', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'sueldo' => ['t' => 'Sueldo', 'tipo' => 'decimal'],
            'remuneracion_basica' => ['t' => 'Remuneración básica (opcional)', 'tipo' => 'decimal'],
            'bonificacion' => ['t' => 'Bonificación', 'tipo' => 'decimal'],
            'compensacion_vacacional' => ['t' => 'Compensación vacacional', 'tipo' => 'decimal'],
            'modalidad_pago' => ['t' => 'Modalidad de pago', 'tipo' => 'texto', 'op' => 'modalidad_pago', 'def' => 'mensual'],
            'beneficios_mensuales' => ['t' => 'Beneficios cada mes', 'tipo' => 'sino', 'def' => 'NO'],
            'motivo' => ['t' => 'Motivo', 'tipo' => 'texto'],
            'anular' => ['t' => 'Anular', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'pensiones' => ['Pensiones', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'sistema_pension' => ['t' => 'Sistema de pensión (código SP)', 'tipo' => 'texto'],
            'esta_jubilado' => ['t' => 'Jubilado', 'tipo' => 'sino', 'def' => 'NO'],
            'suspension_cuarta' => ['t' => 'Suspensión de 4ta', 'tipo' => 'sino', 'def' => 'NO'],
            'motivo' => ['t' => 'Motivo', 'tipo' => 'texto'],
            'anular' => ['t' => 'Anular', 'tipo' => 'sino', 'def' => 'NO'],
        ]],
        'pagos' => ['Cuentas de pago', ['dni', 'desde'], [
            'dni' => ['t' => 'DNI', 'tipo' => 'texto', 'req' => true, 'ref' => 'empleados.dni'],
            'desde' => ['t' => 'Desde', 'tipo' => 'fecha', 'req' => true],
            'metodo_pago' => ['t' => 'Método de pago', 'tipo' => 'texto'],
            'banco' => ['t' => 'Banco', 'tipo' => 'texto'],
            'tipo_cuenta' => ['t' => 'Tipo de cuenta', 'tipo' => 'texto'],
            'moneda_cuenta' => ['t' => 'Moneda de la cuenta', 'tipo' => 'texto', 'op' => 'moneda'],
            'numero_cuenta' => ['t' => 'Número de cuenta', 'tipo' => 'texto'],
            'banco_secundario' => ['t' => 'Banco (diferencia)', 'tipo' => 'texto'],
            'tipo_cuenta_secundaria' => ['t' => 'Tipo de cuenta (diferencia)', 'tipo' => 'texto'],
            'moneda_cuenta_secundaria' => ['t' => 'Moneda (diferencia)', 'tipo' => 'texto', 'op' => 'moneda'],
            'numero_cuenta_secundaria' => ['t' => 'Número de cuenta (diferencia)', 'tipo' => 'texto'],
            'motivo' => ['t' => 'Motivo', 'tipo' => 'texto'],
            'anular' => ['t' => 'Anular', 'tipo' => 'sino', 'def' => 'NO'],
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
        'tipo_contrato' => PlanillaLaboralReglas::TIPOS_CONTRATO,
        'tipo_planilla' => PlanillaLaboralReglas::TIPOS_PLANILLA,
        'tipo_ingreso' => PlanillaLaboralReglas::TIPOS_INGRESO,
        'modalidad_pago' => PlanillaLaboralReglas::MODALIDADES_PAGO,
        'moneda' => ['PEN', 'USD'],
        'rol_familiar' => ['padre', 'madre', 'conyuge', 'tutor'],
        'parentesco' => ['hijo', 'conyuge'],
    ];

    /** Partículas del contrato (su "Desde" debe caer dentro de un contrato del empleado). */
    public const PARTICULAS_CONTRATO = ['asignaciones' => 'La asignación', 'remuneraciones' => 'La remuneración'];

    /** Partículas del empleado (siguen al empleado entre contratos). */
    public const PARTICULAS_EMPLEADO = ['pensiones' => 'La pensión', 'pagos' => 'La cuenta de pago'];
}
