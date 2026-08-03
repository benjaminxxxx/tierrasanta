<?php

namespace App\Services\Planilla;

use App\Models\Configuracion;
use App\Models\PlanEmpleado;
use App\Models\PlanMensual;
use App\Models\PlanMensualPersonal;
use App\Models\PlanMensualSpDesc;
use App\Models\PlanRegistroDiario;
use App\Models\PlanSuspension;
use App\Services\PlanillaMensualServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;

class PlanillaServicio
{
    protected function obtenerOrdenGuardado(): array
    {
        $config = Configuracion::where('codigo', 'orden_planilla_asistencia')->first();

        if (!$config || !$config->valor) {
            return [];
        }

        $decodificado = json_decode($config->valor, true);

        return is_array($decodificado) ? $decodificado : [];
    }
    public function obtenerProyeccion($mes, $anio)
    {
        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->with([
                'planilla',
            ])
            ->first();

        if (!$planillaMensual) {
            return [];
        }

        return $planillaMensual->planilla;
    }
    /**
     * Cuenta días de suspensión por código, para todos los empleados dados,
     * solapados con el periodo mes/anio. Retorna:
     * [ plan_empleado_id => ['01' => 3, '20' => 15, ...], ... ]
     */
    protected function contarSuspensionesPorEmpleado(array $empleadosIds, int $mes, int $anio): array
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth()->endOfDay();

        $suspensiones = PlanSuspension::whereIn('plan_empleado_id', $empleadosIds)
            ->whereDate('fecha_inicio', '<=', $finMes)
            ->where(function ($q) use ($inicioMes) {
                $q->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $inicioMes);
            })
            ->with('tipoSuspension:id,codigo,grupo')
            ->get();

        $conteo = [];

        foreach ($suspensiones as $suspension) {
            $tipo = $suspension->tipoSuspension;

            if (!$tipo || !$tipo->codigo) {
                continue;
            }

            // Recorta el rango de la suspensión a los límites del mes consultado
            $inicioReal = Carbon::parse($suspension->fecha_inicio)->max($inicioMes);
            $finReal = $suspension->fecha_fin
                ? Carbon::parse($suspension->fecha_fin)->min($finMes)
                : $finMes;

            if ($finReal->lt($inicioReal)) {
                continue; // por seguridad, evita días negativos si algo no calzó
            }

            $dias = $inicioReal->diffInDays($finReal) + 1;

            $conteo[$suspension->plan_empleado_id][$tipo->codigo] =
                ($conteo[$suspension->plan_empleado_id][$tipo->codigo] ?? 0) + $dias;
        }

        return $conteo;
    }

    /**
     * Convierte el conteo por código a las columnas reales del modelo,
     * rellenando con 0 los códigos sin registro ese mes.
     */
    protected function mapearConteoAColumnas(array $conteoPorCodigo): array
    {
        $codigosSp = ['01', '02', '03', '04', '05', '06', '07', '08'];
        $codigosSi = ['20', '21', '22', '23', '24', '25', '26', '27'];

        $columnas = [];

        foreach ($codigosSp as $codigo) {
            $columnas["sp_{$codigo}"] = $conteoPorCodigo[$codigo] ?? 0;
        }

        foreach ($codigosSi as $codigo) {
            $columnas["si_{$codigo}"] = $conteoPorCodigo[$codigo] ?? 0;
        }

        return $columnas;
    }
    /**
     * Suma total_horas de plan_registros_diarios por empleado, para todo
     * el plan_mensual actual. Una sola query agregada.
     *
     * ⚠️ Asume que plan_mensual_detalles tiene una columna plan_empleado_id.
     * Ajusta el nombre si es distinto.
     */
    protected function obtenerTotalHorasPorEmpleado(int $planMensualId): array
    {
        return PlanRegistroDiario::query()
            ->join('plan_mensual_detalles', 'plan_registros_diarios.plan_det_men_id', '=', 'plan_mensual_detalles.id')
            ->where('plan_mensual_detalles.plan_mensual_id', $planMensualId)
            ->groupBy('plan_mensual_detalles.plan_empleado_id')
            ->selectRaw('plan_mensual_detalles.plan_empleado_id, SUM(plan_registros_diarios.total_horas) as total_horas')
            ->pluck('total_horas', 'plan_empleado_id')
            ->toArray();
    }
    public function generarProyeccion($mes, $anio)
    {
        $ordenGuardado = $this->obtenerOrdenGuardado();

        $empleados = app(PlanillaEmpleadoServicio::class)
            ->obtenerPlanillaAgraria($mes, $anio, $ordenGuardado);

        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->with([
                'detalle.empleado',
                'detalle.registrosDiarios',
                'planilla'
            ])
            ->first();

        if (!$planillaMensual) {
            throw new Exception('No hay planilla mensual generada');
        }

        // Precarga el conteo de suspensiones para TODOS los empleados de una sola vez
        $todosLosIds = $empleados->pluck('id')->toArray();
        $suspensionesPorEmpleado = $this->contarSuspensionesPorEmpleado($todosLosIds, $mes, $anio);
        $horasPorEmpleado = $this->obtenerTotalHorasPorEmpleado($planillaMensual->id);

        // Días calendario reales del mes (28-31), NO los días laborables configurados
        $diasDelMes = Carbon::create($anio, $mes, 1)->daysInMonth;


        return DB::transaction(function () use ($mes, $anio, $empleados, $planillaMensual, $suspensionesPorEmpleado, $horasPorEmpleado, $diasDelMes) {

            $empleadosIds = [];
            $errores = []; // agrupado por tipo de error
            $procesados = 0;
            $montoAsignacionFamiliar = $planillaMensual->asignacion_familiar;

            foreach ($empleados as $key => $empleado) {

                $remuneracionBasica = $planillaMensual->remuneracion_basica;
                $contrato = $empleado->contrato($mes, $anio);
                $sueldo = $empleado->sueldo($mes, $anio);

                if (!$contrato) {
                    $errores['sin_contrato'][] = $empleado->nombre_completo;
                    continue;
                }

                if (!$contrato->plan_sp_codigo) {
                    $errores['sin_sistema_pension'][] = $empleado->nombre_completo;
                    continue;
                }

                $edad = $empleado->edadContable($mes, $anio);

                if (!$edad) {
                    $errores['sin_edad_valida'][] = $empleado->nombre_completo;
                    continue;
                }

                $empleadosIds[] = $empleado->id;
                $procesados++;

                //El empleado puede tener una remuneracion basica 
                if ((float) $contrato->remuneracion_basica > 0) {
                    $remuneracionBasica = $contrato->remuneracion_basica;
                }

                $tieneAsignacion = $empleado
                    ->cantidadHijosConAsignacionFamiliar($mes, $anio) > 0;

                $montoAsignacionFamiliar = $tieneAsignacion ? $planillaMensual->asignacion_familiar : 0;
                $compensacionVacacional = $contrato->compensacion_vacacional;
                $codigoSp = $contrato->plan_sp_codigo;
                $esPensionista = (bool) $contrato->esta_jubilado;
                $esMayor65 = $edad >= 65;

                // Base de cálculo: suma de remuneración básica hasta compensación vacacional
                $sueldoBrutoBase = $remuneracionBasica
                    + 0 // bonificacion, ya la tienes fija en 0 en este punto
                    + $montoAsignacionFamiliar
                    + $compensacionVacacional;

                if ($esPensionista) {
                    $proyectadoDsctoAfpPrimaSeguro = 0;
                } else {
                    $descuentoSp = PlanMensualSpDesc::where('plan_mensual_id', $planillaMensual->id)
                        ->where('codigo', $codigoSp)
                        ->first();

                    if (!$descuentoSp) {
                        throw new Exception(
                            'El empleado ' . $empleado->nombre_completo .
                            ' tiene un código de sistema de pensión (' . $codigoSp . ') sin descuento configurado para este periodo.'
                        );
                    }

                    $porcentajeAplicable = $esMayor65
                        ? $descuentoSp->porcentaje_65
                        : $descuentoSp->porcentaje;

                    $proyectadoDsctoAfpPrimaSeguro = round($sueldoBrutoBase * $porcentajeAplicable / 100, 2);
                }

                // Conteo de suspensiones ya precargado, solo se mapea a columnas
                $conteoEmpleado = $suspensionesPorEmpleado[$empleado->id] ?? [];
                $columnasSuspension = $this->mapearConteoAColumnas($conteoEmpleado);
                // plame_dias_no_laborados = suma de TODOS los sp_ y si_
                $diasNoLaborados = array_sum($columnasSuspension);

                // plame_dias_laborados = días calendario del mes - no laborados
                $diasLaborados = $diasDelMes - $diasNoLaborados;
                $totalHorasEmpleado = $horasPorEmpleado[$empleado->id] ?? 0;

                // Solo suspensión perfecta (sp_01 a sp_08), para BETA 30%
                $sumaSuspensionPerfecta = array_sum(array_filter(
                    $columnasSuspension,
                    fn($valor, $clave) => str_starts_with($clave, 'sp_'),
                    ARRAY_FILTER_USE_BOTH
                ));

                // --- Ingresos PLAME ---

                // 0117: se duplica de compensación vacacional, ya calculada arriba
                $plame0117CompVacacional = $compensacionVacacional ?? 0;

                // 0118: rmv/30 * si_23 (descanso vacacional es suspensión IMPERFECTA, no perfecta)
                $diasVacacionales = $columnasSuspension['si_23'] ?? 0;
                $plame0118RemVacacional = round(($planillaMensual->rmv / 30) * $diasVacacionales, 2);

                // 0121: remuneracionBasica / total_horas configuradas * total_horas trabajadas por el empleado
                $totalHorasConfiguradas = $planillaMensual->total_horas;
                $plame0121RemJornalBasico = $totalHorasConfiguradas > 0
                    ? round(($remuneracionBasica / $totalHorasConfiguradas) * $totalHorasEmpleado, 2)
                    : 0;

                // 0201: montoAsignacionFamiliar / dias del mes * dias laborados
                $plame0201AsignacionFamiliar = $diasDelMes > 0
                    ? round(($montoAsignacionFamiliar / $diasDelMes) * $diasLaborados, 2)
                    : 0;

                // Remuneración bruta = SUMA(0118 : 0121 : 0201)
                $plameRemuneracionBruta = round(
                    $plame0118RemVacacional + $plame0121RemJornalBasico + $plame0201AsignacionFamiliar,
                    2
                );

                // 0406: 16.66% de la remuneración bruta
                $plame0406GratifFiestasNavidad = round($plameRemuneracionBruta * 0.1666, 2);

                // 0312: 6% de la gratificación (0406), no de la remuneración bruta directamente
                $plame0312BonifExtTemp = round($plame0406GratifFiestasNavidad * 0.06, 2);

                // 0904: 9.72% de la remuneración bruta
                $plame0904Cts = round($plameRemuneracionBruta * 0.0972, 2);

                // BETA 30%: ((30% * rmv) / dias_del_mes) * (dias_del_mes - suma_suspension_perfecta)
                $plame0314Beta30 = round(
                    ((0.30 * $planillaMensual->rmv) / $diasDelMes) * ($diasDelMes - $sumaSuspensionPerfecta),
                    2
                );

                // Base imponible para AFP: 0117 + 0118 + 0121 + 0201 (Y7:AB7 en tu Excel)
                $baseImponibleAfp = $plame0117CompVacacional
                    + $plame0118RemVacacional
                    + $plame0121RemJornalBasico
                    + $plame0201AsignacionFamiliar;

                $esSnp = $codigoSp === 'SNP';

                $plameDescuento0601ComisionAfpPct = 0;
                $plameDescuento0606PrimaSeguroAfp = 0;
                $plameDescuento0607Snp = 0;
                $plameDescuento0608SppAporteObligatorio = 0;

                if (!$esPensionista) {
                    // $descuentoSp ya fue obtenido más arriba para proyectado_dscto_afp_prima_seguro,
                    // se reutiliza aquí sin volver a consultar la BD.

                    // 0601: comisión AFP (0 si es SNP)
                    if (!$esSnp) {
                        $plameDescuento0601ComisionAfpPct = round($descuentoSp->comision / 100 * $baseImponibleAfp, 2);
                    }

                    // 0606: prima de seguro AFP. Mayores de 65 NO pagan prima (regla fija, no configurable).
                    if (!$esSnp && !$esMayor65) {
                        $plameDescuento0606PrimaSeguroAfp = round($descuentoSp->prima_seguros / 100 * $baseImponibleAfp, 2);
                    }

                    // 0607: SNP, solo aplica si el código ES SNP
                    if ($esSnp) {
                        $plameDescuento0607Snp = round($descuentoSp->aporte_obligatorio / 100 * $baseImponibleAfp, 2);
                    }

                    // 0608: aporte obligatorio SPP (0 si es SNP), sobre remuneración bruta + comp. vacacional
                    if (!$esSnp) {
                        $plameDescuento0608SppAporteObligatorio = round($descuentoSp->aporte_obligatorio / 100 * ($plameRemuneracionBruta + $plame0117CompVacacional), 2);
                    }
                }

                // 0605: siempre 0, según tu especificación
                $plameDescuento0605Renta5taRetenida = 0;

                // Aportes del empleador — usan proyeccion_sueldo_bruto (= $sueldoBrutoBase, ya calculado arriba)
                $plameAporteEmpleador0803Poliza = is_null($planillaMensual->vida_ley)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->vida_ley / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                $plameAporteEmpleador0805Sctr = is_null($planillaMensual->pension_sctr)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->pension_sctr / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                $plameAporteEmpleador0810Eps = is_null($planillaMensual->essalud_eps)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->essalud_eps / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                // 0804 EsSalud del PLAME: OJO, usa $baseImponibleAfp (0117+0118+0121+0201), NO $sueldoBrutoBase.
// Es una base distinta a la de vida_ley/sctr/eps, según tu fórmula =6%*SUMA(Y34:AB34)
                $plameAporteEmpleador0804Essalud = is_null($planillaMensual->essalud)
                    ? 0
                    : round($baseImponibleAfp * ($planillaMensual->essalud / 100), 2);

                // Neto a pagar: (0117+0118+0121+0201) + (0312+0314+0406+0904) - (0601+0605+0606+0607+0608)
                $plameNetoAPagar = round(
                    round($plame0117CompVacacional, 2)
                    + round($plame0118RemVacacional, 2)
                    + round($plame0121RemJornalBasico, 2)
                    + round($plame0201AsignacionFamiliar, 2)
                    + round($plame0312BonifExtTemp, 2)
                    + round($plame0314Beta30, 2)
                    + round($plame0406GratifFiestasNavidad, 2)
                    + round($plame0904Cts, 2)
                    - round($plameDescuento0601ComisionAfpPct, 2)
                    - round($plameDescuento0605Renta5taRetenida, 2)
                    - round($plameDescuento0606PrimaSeguroAfp, 2)
                    - round($plameDescuento0607Snp, 2)
                    - round($plameDescuento0608SppAporteObligatorio, 2),
                    2
                );

                $planillaMensual->planilla()->updateOrCreate(
                    [
                        'plan_empleado_id' => $empleado->id,
                    ],
                    array_merge([
                        'orden' => $key + 1,
                        'nombres' => $empleado->nombre_completo,
                        'sistema_pension' => $codigoSp,
                        'grupo' => $contrato->grupo?->codigo,
                        'empleado_grupo_color' => $contrato->grupo?->color,
                        'edad' => $edad,
                        'es_pensionista' => $contrato->esta_jubilado,
                        'remuneracion_basica' => $remuneracionBasica,
                        'bonificacion' => 0,
                        'asignacion_familiar' => $montoAsignacionFamiliar,
                        'compensacion_vacacional' => $compensacionVacacional,
                        'proyectado_dscto_afp_prima_seguro' => $proyectadoDsctoAfpPrimaSeguro,
                        'proyectado_sueldo_neto_total' => $sueldo,

                        'plame_dias_no_laborados' => $diasNoLaborados,
                        'plame_dias_laborados' => $diasLaborados,
                        'plame_total_horas' => round($totalHorasEmpleado, 2),

                        'plame_0117_comp_vacacional' => $plame0117CompVacacional,
                        'plame_0118_rem_vacacional' => $plame0118RemVacacional,
                        'plame_0121_rem_jornal_basico' => $plame0121RemJornalBasico,
                        'plame_0201_asignacion_familiar' => $plame0201AsignacionFamiliar,
                        'plame_remuneracion_bruta' => $plameRemuneracionBruta,
                        'plame_0312_bonif_ext_temp' => $plame0312BonifExtTemp,
                        'plame_0314_beta_30' => $plame0314Beta30,
                        'plame_0406_gratif_fiestas_navidad' => $plame0406GratifFiestasNavidad,
                        'plame_0904_cts' => $plame0904Cts,

                        'plame_descuento_0601_comision_afp_pct' => $plameDescuento0601ComisionAfpPct,
                        'plame_descuento_0605_renta_5ta_retenida' => $plameDescuento0605Renta5taRetenida,
                        'plame_descuento_0606_prima_seguro_afp' => $plameDescuento0606PrimaSeguroAfp,
                        'plame_descuento_0607_snp' => $plameDescuento0607Snp,
                        'plame_descuento_0608_spp_aporte_obligatorio' => $plameDescuento0608SppAporteObligatorio,

                        'plame_neto_a_pagar' => $plameNetoAPagar,

                        'plame_aporte_empleador_0803_poliza' => $plameAporteEmpleador0803Poliza,
                        'plame_aporte_empleador_0804_essalud' => $plameAporteEmpleador0804Essalud,
                        'plame_aporte_empleador_0805_sctr' => $plameAporteEmpleador0805Sctr,
                        'plame_aporte_empleador_0810_eps' => $plameAporteEmpleador0810Eps,

                    ], $columnasSuspension)
                );
            }

            // Solo se eliminan del detalle los que sí se pudieron evaluar
            // y ya no corresponden. Los que fallaron por falta de info
            // tampoco quedan en la planilla (ver nota abajo).
            $planillaMensual->planilla()
                ->whereNotIn('plan_empleado_id', $empleadosIds)
                ->delete();

            return [
                'procesados' => $procesados,
                'total' => count($empleados),
                'errores' => $errores,
                'resumen' => $this->formatearResumenErrores($errores),
            ];
        });
    }
    private function formatearResumenErrores(array $errores): array
    {
        $etiquetas = [
            'sin_contrato' => 'no tienen contrato activo en el mes seleccionado',
            'sin_sistema_pension' => 'no tienen un sistema de pensión asignado en su contrato',
            'sin_edad_valida' => 'no tienen una edad contable válida para el mes seleccionado',
        ];

        $resumen = [];

        foreach ($errores as $tipo => $nombres) {
            $resumen[] = [
                'tipo' => $tipo,
                'cantidad' => count($nombres),
                'mensaje' => count($nombres) . ' ' .
                    (count($nombres) === 1 ? 'persona' : 'personas') .
                    ' ' . ($etiquetas[$tipo] ?? 'presentan un error: ' . $tipo) . ': ' .
                    implode(', ', $nombres),
                'empleados' => $nombres,
            ];
        }

        return $resumen;
    }
    public function calcularGastosMensuales($mes, int $anio)
    {
        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->with(['detalle.empleado', 'detalle.registrosDiarios'])
            ->first();

        if (!$planillaMensual) {
            throw new Exception('No hay planilla mensual generada');
        }

        $horasEsperadasMes = $planillaMensual->dias_laborables * 8;

        foreach ($planillaMensual->detalle as $detalleMensual) {
            $empleado = $detalleMensual->empleado;

            if (!$empleado) {
                continue;
            }

            // 1. Obtener Sueldo Pactado / Proyectado
            $sueldoProyectado = $empleado->sueldo($mes, $anio);

            if ($sueldoProyectado && $horasEsperadasMes > 0) {
                // Valor de 1 hora de trabajo con precisión decimal
                $valorHora = $sueldoProyectado / $horasEsperadasMes;

                // 2. Procesar y actualizar los costos diarios
                $this->procesarCostosDiarios($detalleMensual, $valorHora);

                // 3. Calcular Sueldos de Planilla (Real Proyectado vs. Liquidado)
                $sueldoLiquidado = $this->calcularSueldoRealLiquidado($detalleMensual, $valorHora);

                // 4. Guardar datos consolidados en el detalle mensual
                $detalleMensual->sueldo_real_proyectado = $sueldoProyectado;
                $detalleMensual->sueldo_real_liquidado = $sueldoLiquidado;
                $detalleMensual->save();
            }
        }
    }

    /**
     * Calcula el costo exacto de cada día según sus horas y actualiza el registro diario.
     * Retorna la suma total de esos días para control general.
     */
    private function procesarCostosDiarios($detalleMensual, float $valorHora): float
    {
        $sumaCostosDiarios = 0;

        foreach ($detalleMensual->registrosDiarios as $registroDiario) {
            // Costo del día redondeado a 2 decimales para el registro diario individual
            $costoDia = round($valorHora * $registroDiario->total_horas, 2);

            $registroDiario->costo_dia = $costoDia;
            $registroDiario->save();

            $sumaCostosDiarios += $costoDia;
        }

        return round($sumaCostosDiarios, 2);
    }

    /**
     * Calcula el sueldo liquidado directo a partir del total exacto de horas
     * para evitar imprecisiones por redondeos acumulados.
     */
    private function calcularSueldoRealLiquidado($detalleMensual, float $valorHora): float
    {
        $totalHorasTrabajadas = $detalleMensual->registrosDiarios->sum('total_horas');

        return round($valorHora * $totalHorasTrabajadas, 2);
    }
}

