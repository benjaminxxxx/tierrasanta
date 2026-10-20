<?php

namespace App\Services\Costos\ManoObraIndirecta;

/**
 * Mano de obra indirecta (costo operativo de /costos/mensual) = todo lo que se gasta en FDM en el mes, separado en
 * blanco y negro (reglas del usuario, 19/10/2026):
 *
 * - Planilla en FDM (labores, riego, horas pagadas sin trabajo en campo que la BDD manda a FDM, vacaciones pagadas):
 *   cada sol se separa con la proporción del trabajador en el mes: blanco = PLAME (ingresos + aportes del empleador),
 *   negro = lo pagado por encima. Bono de asistencia y bono de productividad: negro.
 * - Cuadrilla en FDM (jornal y bonos): negro (informal al 100 %).
 * - Gastos adicionales de grupos de cuadrilla (movilidad, comisión…): por ahora todo a FDM, negro. POR CONFIRMAR: la
 *   movilidad podría ir a "Servicios fundo".
 * - Combustible: máquina sin distribución → 100 % FDM; máquina con distribución → la parte de horas en FDM. Una
 *   máquina que usa distribución y aún no la tiene deja el cálculo "no concluyente". Blanco/negro según el kardex.
 * - Insumos con salida al campo FDM y servicios de campo en FDM: blanco/negro según su kardex / tipo de costo.
 */
class CostosManoObraIndirectaReglas
{
    public const CAMPO_FDM = 'FDM';

    /** Mes desde el que se pide calcular (tarea pendiente). Antes no hay PLAME cargado con lo pagado. */
    public const DESDE = '2026-01';

    /** Secciones del cálculo, en orden: clave => [título, explicación] */
    public const SECCIONES = [
        'planilla' => ['Planilla en FDM', 'Horas de planilla en FDM (labores, riego, feriados, descansos, vacaciones pagadas). '
            . 'Cada sol se separa con la proporción blanco/negro del trabajador en el mes: blanco = PLAME + aportes del empleador; '
            . 'negro = lo pagado por encima. Bono de asistencia y bono de productividad: negro.'],
        'cuadrilla' => ['Cuadrilla en FDM', 'Jornal prorrateado por las horas en FDM y bonos de actividades en FDM. Todo negro (informal).'],
        'gastos_grupo' => ['Gastos adicionales de grupos de cuadrilla', 'Movilidad, comisión y otros gastos de los grupos del mes contable. '
            . 'Por ahora van completos a FDM (negro). POR CONFIRMAR: la movilidad podría pasar a Servicios fundo.'],
        'combustible' => ['Combustible', 'Máquina sin distribución: todo su consumo es FDM. Máquina con distribución: solo la parte de horas en FDM '
            . '(horas FDM ÷ horas distribuidas × costo de la salida). Blanco o negro según el almacén (kardex) de la salida.'],
        'insumos' => ['Insumos con salida a FDM', 'Productos del almacén con salida al campo FDM, al costo de su kardex. Blanco o negro según el almacén.'],
        'servicios' => ['Servicios de campo en FDM', 'Servicios externos registrados en el campo FDM, según su tipo de costo.'],
        'otros' => ['Otros gastos FDM registrados a mano', 'Gastos adicionales de FDM registrados en la pantalla antigua "Costos Generales FDM" '
            . '(tabla costo_fdm_mensuals). Esa pantalla pasó a legacy: solo se leen los que ya existían.'],
    ];

    /** Conceptos de mano_obra_indirecta (ConsolidarManoObraIndirectaServicio) que son negro aunque el trabajador tenga blanco. */
    public const CONCEPTOS_NEGRO = ['Bonificación 100% asistencia'];

    public static function esFdm(?string $campo): bool
    {
        return mb_strtoupper(trim((string) $campo)) === self::CAMPO_FDM;
    }

    /** Separa un monto con la proporción blanco (0..1). */
    public static function separar(float $monto, float $proporcionBlanco): array
    {
        $blanco = round($monto * $proporcionBlanco, 2);
        return [$blanco, round($monto - $blanco, 2)];
    }
}
