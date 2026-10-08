<?php

namespace App\Services\Planilla;

use App\Models\Configuracion;
use App\Models\PlanEmpleado;
use App\Models\PlanMensual;
use App\Models\PlanMensualPersonal;
use App\Models\PlanMensualSpDesc;
use App\Models\PlanRegistroDiario;
use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Services\Planilla\PlanillaMensualServicio;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\Plame\PlanillaPlameProceso;
use App\Support\ExcelHelper;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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
    /*se usara otra mejorada
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
    }*/
    public function obtenerProyeccion($mes, $anio)
    {
        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->with(['planilla.planMensual']) // Carga el padre para reutilizar mes y anio en el Accessor
            ->first();

        if (!$planillaMensual) {
            return [];
        }

        // 1. Consultar todos los bonos del mes agrupados por empleado en UNA sola Query masiva
        $bonosProductividad = DB::table('plan_registros_diarios')
            ->join('plan_mensual_detalles', 'plan_mensual_detalles.id', '=', 'plan_registros_diarios.plan_det_men_id')
            ->join('plan_mensuales', 'plan_mensuales.id', '=', 'plan_mensual_detalles.plan_mensual_id')
            ->where('plan_mensuales.mes', $mes)
            ->where('plan_mensuales.anio', $anio)
            ->select('plan_mensual_detalles.plan_empleado_id', DB::raw('SUM(plan_registros_diarios.total_bono) as total'))
            ->groupBy('plan_mensual_detalles.plan_empleado_id')
            ->pluck('total', 'plan_empleado_id');

        // 2. Asignar el valor a cada registro de personal
        return $planillaMensual->planilla->map(function ($persona) use ($bonosProductividad) {
            // Asignamos una propiedad dinámica para acelerar la exportación del Excel
            $persona->bono_productividad = (float) ($bonosProductividad[$persona->plan_empleado_id] ?? 0);
            return $persona;
        });
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
    /** Horas de una jornada: lo que se descuenta por cada día de suspensión que tiene horas registradas. */
    private const HORAS_JORNADA = 8;

    /**
     * Horas registradas en días de suspensión (descanso médico, licencia con goce…), como máximo una jornada
     * por día. Esos días se pagan (entran al jornal básico) pero no son jornada trabajada en el PLAME.
     *
     * @return array<int, float> plan_empleado_id => horas
     */
    protected function obtenerHorasEnSuspensionPorEmpleado(int $planMensualId): array
    {
        return PlanRegistroDiario::query()
            ->join('plan_mensual_detalles', 'plan_registros_diarios.plan_det_men_id', '=', 'plan_mensual_detalles.id')
            ->join('plan_tipo_asistencias', 'plan_tipo_asistencias.codigo', '=', 'plan_registros_diarios.asistencia')
            ->where('plan_mensual_detalles.plan_mensual_id', $planMensualId)
            ->whereNotNull('plan_tipo_asistencias.plan_tipo_suspension_id')
            ->groupBy('plan_mensual_detalles.plan_empleado_id')
            ->selectRaw('plan_mensual_detalles.plan_empleado_id, SUM(LEAST(?, plan_registros_diarios.total_horas)) as horas', [self::HORAS_JORNADA])
            ->pluck('horas', 'plan_empleado_id')
            ->map(fn($h) => (float) $h)
            ->toArray();
    }

    public function generarProyeccion($mes, $anio)
    {
        // BDD de costos: regenerar la mano de obra de estas fechas al terminar la petición
        \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio(sprintf("%04d-%02d-01", $anio, $mes), \Illuminate\Support\Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString());
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
        $horasSuspensionPorEmpleado = $this->obtenerHorasEnSuspensionPorEmpleado($planillaMensual->id);

        // Días calendario reales del mes (28-31), NO los días laborables configurados
        $diasDelMes = Carbon::create($anio, $mes, 1)->daysInMonth;


        return DB::transaction(function () use ($mes, $anio, $empleados, $planillaMensual, $suspensionesPorEmpleado, $horasPorEmpleado, $horasSuspensionPorEmpleado, $diasDelMes) {

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
                $bonificacion = (float) $contrato->bonificacion;

                // Base de cálculo: suma de remuneración básica hasta compensación vacacional
                $sueldoBrutoBase = $remuneracionBasica
                    + $bonificacion // bonificacion, ya la tienes fija en 0 en este punto
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

                    $proyectadoDsctoAfpPrimaSeguro = $sueldoBrutoBase * $porcentajeAplicable / 100;
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

                // 0118: valor hora (RMV / 240, a 3 decimales) × 8 × días de descanso vacacional (si_23), como la planilla oficial
                $diasVacacionales = $columnasSuspension['si_23'] ?? 0;
                $plame0118RemVacacional = PlanillaPlameProceso::remuneracionVacacional((float) $planillaMensual->rmv, $diasVacacionales);

                // 0121: (remuneracionBasica + bonificación del contrato) / total_horas configuradas * total_horas trabajadas.
                // La bonificación del contrato no es un pago suelto: legalmente es parte de su básico mensual,
                // así que entra al 0121 y con él a gratificación, CTS, AFP y EsSalud (como en el PLAME real).
                $totalHorasConfiguradas = $planillaMensual->total_horas;
                $plame0121RemJornalBasico = $totalHorasConfiguradas > 0
                    ? round((($remuneracionBasica + $bonificacion) / $totalHorasConfiguradas) * $totalHorasEmpleado, 2)
                    : 0;

                // 0201: asignación / días del mes × (días del mes − suspensión perfecta). El descanso médico y las
                // licencias con goce (imperfecta) se pagan y no la reducen
                $plame0201AsignacionFamiliar = PlanillaPlameProceso::asignacionFamiliar((float) $montoAsignacionFamiliar, $diasDelMes, $sumaSuspensionPerfecta);

                // Aportes del empleador: sobre el sueldo bruto proyectado ($sueldoBrutoBase)
                $plameAporteEmpleador0803Poliza = is_null($planillaMensual->vida_ley)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->vida_ley / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                $plameAporteEmpleador0805Sctr = is_null($planillaMensual->pension_sctr)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->pension_sctr / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                $plameAporteEmpleador0810Eps = is_null($planillaMensual->essalud_eps)
                    ? 0
                    : round($sueldoBrutoBase * ($planillaMensual->essalud_eps / 100) * PlanMensualPersonal::FACTOR_SEGURO, 2);

                // Conceptos en cadena (bruta, gratificación, CTS, BETA, descuentos, EsSalud, neto) con los ajustes manuales
                // del trabajador (Ajustes PLAME), que se conservan al regenerar
                $existente = $planillaMensual->planilla->firstWhere('plan_empleado_id', $empleado->id);
                $plame = PlanillaPlameProceso::calcular([
                    'calculados' => [
                        '0117' => (float) $plame0117CompVacacional,
                        '0118' => $plame0118RemVacacional,
                        '0121' => $plame0121RemJornalBasico,
                        '0201' => $plame0201AsignacionFamiliar,
                        '0803' => $plameAporteEmpleador0803Poliza,
                        '0805' => $plameAporteEmpleador0805Sctr,
                        '0810' => $plameAporteEmpleador0810Eps,
                    ],
                    'rmv' => (float) $planillaMensual->rmv,
                    'dias_mes' => $diasDelMes,
                    'dias_suspension_perfecta' => $sumaSuspensionPerfecta,
                    'codigo_sp' => $codigoSp,
                    'es_pensionista' => $esPensionista,
                    'es_mayor_65' => $esMayor65,
                    'descuento_sp' => $esPensionista ? null : $descuentoSp,
                    'pct_essalud' => $planillaMensual->essalud === null ? null : (float) $planillaMensual->essalud,
                ], $existente?->plame_ajustes ?? []);

                $dataPlanilla = array_merge([
                    'orden' => $key + 1,
                    'nombres' => $empleado->nombre_completo,
                    'sistema_pension' => $codigoSp,
                    'grupo' => $contrato->grupo?->codigo,
                    'empleado_grupo_color' => $contrato->grupo?->color,
                    'edad' => $edad,
                    'es_pensionista' => $contrato->esta_jubilado,
                    'remuneracion_basica' => $remuneracionBasica,
                    'bonificacion' => $bonificacion,
                    'asignacion_familiar' => $montoAsignacionFamiliar,
                    'compensacion_vacacional' => $compensacionVacacional,
                    'proyectado_dscto_afp_prima_seguro' => $proyectadoDsctoAfpPrimaSeguro,
                    'proyectado_sueldo_neto_total' => $sueldo,

                    'plame_dias_no_laborados' => $diasNoLaborados,
                    'plame_dias_laborados' => $diasLaborados,
                    'plame_total_horas' => round($totalHorasEmpleado, 2),
                    // Lo que se declara como jornada: sin las horas de los días de suspensión (se pagan, no se trabajaron)
                    'plame_horas_jornada' => round($totalHorasEmpleado - ($horasSuspensionPorEmpleado[$empleado->id] ?? 0), 2),

                    // Lo que calcula el sistema de cada concepto (las columnas guardan el valor final, con ajustes)
                    'plame_calculados' => $plame['calculados'],
                ], $plame['columnas'], $columnasSuspension);

                // Vacaciones: espejo del ajuste 0118 y exceso pagado en negro (sobre el 0118 final)
                if ($existente) {
                    $dataPlanilla += app(PlanillaPlameProceso::class)->camposVacaciones($existente, $plame['columnas']);
                }

                $planillaMensual->planilla()->updateOrCreate(
                    [
                        'plan_empleado_id' => $empleado->id,
                    ],
                    $dataPlanilla
                );
            }

            // Solo se eliminan del detalle los que sí se pudieron evaluar
            // y ya no corresponden. Los que fallaron por falta de info
            // tampoco quedan en la planilla (ver nota abajo).
            $planillaMensual->planilla()
                ->whereNotIn('plan_empleado_id', $empleadosIds)
                ->delete();

            $this->actualizarCostosDiarios($planillaMensual, (int) $mes, (int) $anio);

            return [
                'procesados' => $procesados,
                'total' => count($empleados),
                'errores' => $errores,
                'resumen' => $this->formatearResumenErrores($errores),
            ];
        });
    }
    public function generarExcelPlanilla($mes, $anio)
    {
        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (!$planillaMensual) {
            throw new Exception('No hay planilla mensual generada para este periodo.');
        }

        $empleados = $planillaMensual->planilla()
            ->orderBy('orden')
            ->get();

        if ($empleados->isEmpty()) {
            throw new Exception('No hay empleados proyectados para generar el Excel. Genera la proyección primero.');
        }

        $spreadsheet = ExcelHelper::cargarPlantilla('planilla_proyectado.xlsx');

        $hoja = $spreadsheet->getSheetByName('PROYECTADA');
        $hojaAsistencia = $spreadsheet->getSheetByName('REPORTE DIARIO');

        if (!$hoja) {
            throw new Exception('No se ha configurado un formato para el documento a exportar.');
        }
        if (!$hojaAsistencia) {
            throw new Exception('No existe la hora de asistencia.');
        }

        // --- 1. Actualizar tabla_descuentos con el snapshot vigente de ESTE periodo ---
        $this->actualizarTablaDescuentosEnHoja($spreadsheet, $planillaMensual->id);

        // Encabezado del periodo
        $nombreMes = Carbon::create($anio, $mes, 1)->translatedFormat('F');
        $hoja->setCellValue('B4', 'MES DE ' . mb_strtoupper($nombreMes) . ' ' . $anio);

        // Celdas de referencia usadas por las fórmulas ($R$1, $R$2, $R$3)
        $hoja->setCellValue('R1', $planillaMensual->total_horas);
        $hoja->setCellValue('R2', $planillaMensual->dias_laborables);
        $hoja->setCellValue('R3', Carbon::create($anio, $mes, 1)->daysInMonth);

        // Porcentajes/valores del periodo, inyectados como literales en las fórmulas
        $rmv = $planillaMensual->rmv;
        $pctGratificaciones = $planillaMensual->gratificaciones;
        $pctEssaludGratif = $planillaMensual->essalud_gratificaciones;
        $pctBeta30 = $planillaMensual->beta30;
        $pctEssalud = $planillaMensual->essalud;
        $pctVidaLey = $planillaMensual->vida_ley;
        $pctPensionSctr = $planillaMensual->pension_sctr;
        $pctEssaludEps = $planillaMensual->essalud_eps;

        $fila = 7;

        foreach ($empleados as $index => $empleado) {
            $orden = $index + 1;
            $f = $fila; // atajo para legibilidad

            // --- Datos de entrada (valores, no fórmulas) ---
            $hoja->setCellValue("A{$f}", $orden);
            $hoja->setCellValue("B{$f}", $empleado->nombres);
            $hoja->setCellValue("C{$f}", $empleado->sistema_pension);

            $hoja->setCellValue("D{$f}", $empleado->remuneracion_basica);
            $hoja->setCellValue("E{$f}", $empleado->bonificacion);
            $hoja->setCellValue("F{$f}", $empleado->asignacion_familiar);
            $hoja->setCellValue("G{$f}", $empleado->compensacion_vacacional);

            // --- H: sueldo bruto ---
            $hoja->setCellValue("H{$f}", "=SUM(D{$f}:G{$f})");

            // --- I: descuento AFP/prima seguro, personalizado por condición del empleado ---
            $hoja->setCellValue("I{$f}", $this->formulaDsctoAfp($empleado, $f));

            // --- J: sueldo neto ---
            $hoja->setCellValue("J{$f}", "=H{$f}-I{$f}");

            // --- K: CTS (9.72% fijo, constante legal, no configurable por periodo) ---
            $hoja->setCellValue("K{$f}", "=((D{$f}+E{$f}+F{$f})*(9.72%))");

            // --- L: Gratificaciones ---
            $hoja->setCellValue("L{$f}", "=(D{$f}+E{$f}+F{$f})*({$pctGratificaciones}%)");

            // --- M: EsSalud sobre gratificaciones ---
            $hoja->setCellValue("M{$f}", "=L{$f}*{$pctEssaludGratif}%");

            // --- N: BETA 30%, sobre la RMV del periodo ---
            $hoja->setCellValue("N{$f}", "=({$rmv}*{$pctBeta30}%)/(30)*30");

            // --- O: EsSalud sobre sueldo bruto ---
            $hoja->setCellValue("O{$f}", "=H{$f}*{$pctEssalud}%");

            // --- P: Vida ley ---
            $hoja->setCellValue("P{$f}", "=((H{$f}*({$pctVidaLey}%))*1.18)");

            // --- Q: Pensión SCTR ---
            $hoja->setCellValue("Q{$f}", "=(H{$f}*({$pctPensionSctr}%))*1.18");

            // --- R: EsSalud EPS ---
            $hoja->setCellValue("R{$f}", "=(H{$f}*({$pctEssaludEps}%))*1.18");

            // --- S: sueldo neto + beneficios ---
            $hoja->setCellValue("S{$f}", "=J{$f}+K{$f}+L{$f}+M{$f}+N{$f}");

            // --- T: sueldo bruto + beneficios + aportes empleador ---
            $hoja->setCellValue("T{$f}", "=H{$f}+K{$f}+L{$f}+M{$f}+N{$f}+O{$f}+P{$f}+Q{$f}+R{$f}");

            // --- U: jornal diario ---
            $hoja->setCellValue("U{$f}", "=T{$f}/\$R\$2");

            // --- V: costo hora ---
            $hoja->setCellValue("V{$f}", "=+U{$f}/8");

            // --- Bloque secundario X-AF ---
            $hoja->setCellValue("X{$f}", "=A{$f}");
            $hoja->setCellValue("Y{$f}", "=B{$f}");
            $hoja->setCellValue("Z{$f}", "=AA{$f}-S{$f}");

            // AA: entrada manual, lo que se le paga completo si no falta
            $hoja->setCellValue("AA{$f}", $empleado->proyectado_sueldo_neto_total);

            $hoja->setCellValue("AB{$f}", "=T{$f}+Z{$f}");
            $hoja->setCellValue("AC{$f}", "=AB{$f}/\$R\$2");
            $hoja->setCellValue("AD{$f}", "=+AC{$f}/8");
            $hoja->setCellValue("AF{$f}", "=Z{$f}/\$R\$1");

            $fila++;
        }

        $this->llenarHojaPlame($spreadsheet, $planillaMensual, $empleados);
        $this->llenarHojaAsistencia($hojaAsistencia, $mes, $anio, $empleados);
        $this->llenarHojaDescuentoAfp($spreadsheet, $mes, $anio);

        $folderPath = 'planilla/' . $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        $fileName = "Planilla_Proyectada_{$mes}_{$anio}.xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        Storage::disk('public')->makeDirectory($folderPath);

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
    protected function llenarHojaDescuentoAfp($spreadsheet, $mes, $anio): void
    {
        $hoja = $spreadsheet->getSheetByName('DESCUENTO AFP');

        if (!$hoja) {
            throw new Exception('No se ha configurado la hoja "DESCUENTO AFP" en la plantilla.');
        }

        // Fuente correcta: PlanPrimaComisionHistorico, NO plan_mensual_sp_desc
        // (esa tabla es por código de modalidad, esta hoja es por AFP; además
        // plan_mensual_sp_desc no guarda comision_saldo ni remuneracion_maxima_asegurable)
        $registros = PlanillaMensualServicio::obtenerDescuentosVigentes($mes, $anio);

        if ($registros->isEmpty()) {
            throw new Exception('No hay comisiones/primas AFP configuradas para este periodo.');
        }

        $filaInicial = 4;

        foreach ($registros as $indice => $registro) {
            $fila = $filaInicial + $indice;

            $hoja->setCellValue("A{$fila}", $registro->referencia);

            $hoja->setCellValue("B{$fila}", $registro->comision_flujo / 100);
            $hoja->getStyle("B{$fila}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $hoja->setCellValue("C{$fila}", $registro->comision_saldo / 100);
            $hoja->getStyle("C{$fila}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $hoja->setCellValue("D{$fila}", $registro->prima_seguros / 100);
            $hoja->getStyle("D{$fila}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $hoja->setCellValue("E{$fila}", $registro->aporte_obligatorio / 100);
            $hoja->getStyle("E{$fila}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            // Remuneración máxima asegurable: monto, no porcentaje
            $hoja->setCellValue("F{$fila}", $registro->remuneracion_maxima_asegurable);
            $hoja->getStyle("F{$fila}")->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
    }
    /*
    protected function llenarHojaAsistencia($hojaAsistencia, $mes, $anio, $empleados): void
    {
        $filaInicial = 7;
        $totalEmpleados = count($empleados);
        $filaFinal = $filaInicial + $totalEmpleados - 1;
        $fechaInicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFinMes = $fechaInicioMes->copy()->endOfMonth();
        $diasEnElMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        // 1. Obtener Mapa de Tipo de Asistencia (Color y Descripción por defecto)
        $tiposAsistencia = PlanTipoAsistencia::where('activo', 1)->get();
        $mapaAsistencia = [];
        foreach ($tiposAsistencia as $tipo) {
            $mapaAsistencia[$tipo->codigo] = [
                'color' => !empty($tipo->color) ? ltrim($tipo->color, '#') : null,
                'descripcion' => $tipo->descripcion ?? '',
            ];
        }

        // Obtener los registros del mes agrupados por plan_empleado_id y por el DÍA del mes
        $registros = PlanRegistroDiario::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->with('detalleMensual')
            ->get();

        // Mapeamos los datos en una estructura [ plan_empleado_id => [ dia => total_horas ] ]
        $asistenciaPorEmpleado = [];
        foreach ($registros as $reg) {
            $empleadoId = $reg->detalleMensual->plan_empleado_id ?? null;
            if ($empleadoId) {
                $dia = (int) Carbon::parse($reg->fecha)->format('j');
                $asistenciaPorEmpleado[$empleadoId][$dia] = [
                    'horas' => $reg->total_horas,
                    'codigo' => $reg->asistencia ?? 'A',
                ];
            }
        }

        // 3. Cargar Suspensiones que intersecten el mes actual
        $empleadosIds = $empleados->pluck('plan_empleado_id')->toArray();
        $suspensiones = PlanSuspension::with('tipoSuspension')
            ->whereIn('plan_empleado_id', $empleadosIds)
            ->where(function ($query) use ($fechaInicioMes, $fechaFinMes) {
                $query->whereBetween('fecha_inicio', [$fechaInicioMes, $fechaFinMes])
                    ->orWhereBetween('fecha_fin', [$fechaInicioMes, $fechaFinMes])
                    ->orWhere(function ($q) use ($fechaInicioMes, $fechaFinMes) {
                        $q->where('fecha_inicio', '<=', $fechaInicioMes)
                            ->where('fecha_fin', '>=', $fechaFinMes);
                    });
            })
            ->get();
        // Mapear suspensiones por [empleado_id][YYYY-MM-DD]
        $mapaSuspensiones = [];
        foreach ($suspensiones as $susp) {
            $inicio = Carbon::parse($susp->fecha_inicio);
            $fin = Carbon::parse($susp->fecha_fin);

            // Iterar los días del rango de la suspensión
            for ($dt = $inicio->copy(); $dt->lte($fin); $dt->addDay()) {
                $mapaSuspensiones[$susp->plan_empleado_id][$dt->format('Y-m-d')] = $susp;
            }
        }

        // Iniciales de los días en español (Lunes = L, Martes = M, Miércoles = X, etc.)
        $inicialesDias = [
            1 => 'L', // Lunes
            2 => 'M', // Martes
            3 => 'M', // Miércoles
            4 => 'J', // Jueves
            5 => 'V', // Viernes
            6 => 'S', // Sábado
            7 => 'D', // Domingo
        ];

        // Columna inicial donde empiezan los días (Día 1 = Columna D = 4)
        $columnaInicialDias = 4;// Columna D (Índice 4)
        $columnasDomingo = [];  // Para almacenar las columnas que son domingo

        // 2. Llenar Encabezados de Días (Filas 4, 5 y 6)
        for ($dia = 1; $dia <= 31; $dia++) {
            $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);

            if ($dia <= $diasEnElMes) {
                $fecha = Carbon::createFromDate($anio, $mes, $dia);
                // ✅ OPCIÓN 2 (Recomendada en Carbon): Usar el método directo
                $diaSemanaNro = $fecha->dayOfWeekIso;
                // Fila 4: Fórmula de recuento adaptada a la última fila real
                $hojaAsistencia->setCellValue("{$colLetter}4", "=COUNTIF({$colLetter}{$filaInicial}:{$colLetter}{$filaFinal}, \">7\")");
                $hojaAsistencia->setCellValue("{$colLetter}5", $inicialesDias[$diaSemanaNro]);
                $hojaAsistencia->setCellValue("{$colLetter}6", $dia);

                // Identificar si es Domingo (7)
                if ($diaSemanaNro == 7) {
                    $columnasDomingo[] = $colLetter;
                }
            } else {
                // Limpiar cabeceras sobrantes en meses < 31 días
                $hojaAsistencia->setCellValue("{$colLetter}4", null);
                $hojaAsistencia->setCellValue("{$colLetter}5", null);
                $hojaAsistencia->setCellValue("{$colLetter}6", null);
            }
        }

        // 3. Llenar Datos de Empleados
        $fila = $filaInicial;

        foreach ($empleados as $indice => $empleado) {
            $f = $fila;
            $hojaAsistencia->setCellValue("A{$f}", $indice + 1);
            $hojaAsistencia->setCellValue("B{$f}", "-");
            $hojaAsistencia->setCellValue("C{$f}", $empleado->nombres);

            // Iterar siempre los 31 días correspondientes a las columnas
            for ($dia = 1; $dia <= 31; $dia++) {
                $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);

                if ($dia <= $diasEnElMes) {
                    $fechaDiaStr = Carbon::createFromDate($anio, $mes, $dia)->format('Y-m-d');
                    $datosDia = $asistenciaPorEmpleado[$empleado->id][$dia] ?? ['horas' => 0, 'codigo' => 'A'];

                    $codigoAsistencia = $datosDia['codigo'];
                    $horas = $datosDia['horas'];

                    // A. Regla del valor en la celda: solo si es 'F' muestra 'F', los demás muestran sus horas
                    if ($codigoAsistencia === 'F') {
                        $hojaAsistencia->setCellValue("{$colLetter}{$f}", 'F');
                    } else {
                        $hojaAsistencia->setCellValue("{$colLetter}{$f}", $horas > 0 ? $horas : 0);
                    }

                    // B. Aplicar Color de Fondo si el código no es 'A' y tiene color configurado
                    if ($codigoAsistencia !== 'A' && !empty($mapaAsistencia[$codigoAsistencia]['color'])) {
                        $hojaAsistencia->getStyle("{$colLetter}{$f}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $mapaAsistencia[$codigoAsistencia]['color']],
                            ],
                        ]);
                    }

                    // C. Generar Comentario / Nota de Excel
                    $textoComentario = null;

                    if (isset($mapaSuspensiones[$empleado->id][$fechaDiaStr])) {
                        // C1. Si existe una suspensión registrada
                        $susp = $mapaSuspensiones[$empleado->id][$fechaDiaStr];
                        $tSusp = $susp->tipoSuspension;

                        // Prioriza la descripcion_corta si está llena
                        $nombreSuspension = !empty($tSusp->descripcion_corta)
                            ? $tSusp->descripcion_corta
                            : ($tSusp->descripcion ?? 'Suspensión');

                        $fInicio = Carbon::parse($susp->fecha_inicio)->format('d/m/Y');
                        $fFin = Carbon::parse($susp->fecha_fin)->format('d/m/Y');

                        $textoComentario = ($fInicio === $fFin)
                            ? "{$nombreSuspension} el {$fInicio}"
                            : "{$nombreSuspension} del {$fInicio} al {$fFin}";

                        if (!empty($susp->observaciones)) {
                            $textoComentario .= "\nObs: " . $susp->observaciones;
                        }

                    } elseif ($codigoAsistencia !== 'A') {
                        // C2. Si no tiene suspensión, usa la descripción por defecto de plan_tipo_asistencias
                        $textoComentario = $mapaAsistencia[$codigoAsistencia]['descripcion'] ?? null;
                    }

                    // Insertar el comentario si se generó algún texto
                    if ($textoComentario) {
                        $comentario = $hojaAsistencia->getComment("{$colLetter}{$f}");
                        $comentario->getText()->createTextRun($textoComentario);
                    }

                } else {
                    $hojaAsistencia->setCellValue("{$colLetter}{$f}", null);
                }
            }
            $fila++;
        }

        // 4. Aplicar Ancho y Color #FFC000 a las columnas Domingo (Filas 5 a $filaFinal)
        foreach ($columnasDomingo as $colDom) {
            // Redimensionar ancho de columna (3.11 puntos)
            $hojaAsistencia->getColumnDimension($colDom)->setWidth(3.11);

            // Estilo de fondo en el rango completo del domingo (Fila 5 hasta última fila de datos)
            $hojaAsistencia->getStyle("{$colDom}5:{$colDom}{$filaFinal}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000'],
                ],
            ]);
        }

        // 5. Agregar Bordes desde A7 hasta AI{ultimaFila}
        // La columna AI corresponde al día 31 (Columna D + 31 - 1 = Columna 34 -> AI)
        $hojaAsistencia->getStyle("A7:AI{$filaFinal}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }*/
    protected function llenarHojaAsistencia($hojaAsistencia, $mes, $anio, $empleados): void
    {
        $filaInicial = 7;
        $totalEmpleados = count($empleados);
        $filaFinal = $filaInicial + $totalEmpleados - 1;
        $fechaInicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFinMes = $fechaInicioMes->copy()->endOfMonth();
        $diasEnElMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        $columnaInicialDias = 4; // Columna D
        $ultimaColDia = Coordinate::stringFromColumnIndex($columnaInicialDias + $diasEnElMes - 1);

        // ... (todo el bloque existente de mapaAsistencia, registros, mapaSuspensiones,
        //      inicialesDias, encabezados de días fila 4-6, y el foreach de empleados
        //      que llena A-C y los días D..último, con colores y comentarios,
        //      SIN CAMBIOS respecto a tu código actual) ...

        // 1. Obtener Mapa de Tipo de Asistencia (Color y Descripción por defecto)
        $tiposAsistencia = PlanTipoAsistencia::where('activo', 1)->get();
        $mapaAsistencia = [];
        foreach ($tiposAsistencia as $tipo) {
            $mapaAsistencia[$tipo->codigo] = [
                'color' => !empty($tipo->color) ? ltrim($tipo->color, '#') : null,
                'descripcion' => $tipo->descripcion ?? '',
            ];
        }

        // Obtener los registros del mes agrupados por plan_empleado_id y por el DÍA del mes
        $registros = PlanRegistroDiario::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->with('detalleMensual')
            ->get();

        // Mapeamos los datos en una estructura [ plan_empleado_id => [ dia => total_horas ] ]
        $asistenciaPorEmpleado = [];
        foreach ($registros as $reg) {
            $empleadoId = $reg->detalleMensual->plan_empleado_id ?? null;
            if ($empleadoId) {
                $dia = (int) Carbon::parse($reg->fecha)->format('j');
                $asistenciaPorEmpleado[$empleadoId][$dia] = [
                    'horas' => $reg->total_horas,
                    'codigo' => $reg->asistencia ?? 'A',
                ];
            }
        }

        // 3. Cargar Suspensiones que intersecten el mes actual
        $empleadosIds = $empleados->pluck('plan_empleado_id')->toArray();
        $suspensiones = PlanSuspension::with('tipoSuspension')
            ->whereIn('plan_empleado_id', $empleadosIds)
            ->where(function ($query) use ($fechaInicioMes, $fechaFinMes) {
                $query->whereBetween('fecha_inicio', [$fechaInicioMes, $fechaFinMes])
                    ->orWhereBetween('fecha_fin', [$fechaInicioMes, $fechaFinMes])
                    ->orWhere(function ($q) use ($fechaInicioMes, $fechaFinMes) {
                        $q->where('fecha_inicio', '<=', $fechaInicioMes)
                            ->where('fecha_fin', '>=', $fechaFinMes);
                    });
            })
            ->get();
        // Mapear suspensiones por [empleado_id][YYYY-MM-DD]
        $mapaSuspensiones = [];
        foreach ($suspensiones as $susp) {
            $inicio = Carbon::parse($susp->fecha_inicio);
            $fin = Carbon::parse($susp->fecha_fin);

            // Iterar los días del rango de la suspensión
            for ($dt = $inicio->copy(); $dt->lte($fin); $dt->addDay()) {
                $mapaSuspensiones[$susp->plan_empleado_id][$dt->format('Y-m-d')] = $susp;
            }
        }

        // Iniciales de los días en español (Lunes = L, Martes = M, Miércoles = X, etc.)
        $inicialesDias = [
            1 => 'L', // Lunes
            2 => 'M', // Martes
            3 => 'M', // Miércoles
            4 => 'J', // Jueves
            5 => 'V', // Viernes
            6 => 'S', // Sábado
            7 => 'D', // Domingo
        ];

        // Columna inicial donde empiezan los días (Día 1 = Columna D = 4)
        $columnaInicialDias = 4;// Columna D (Índice 4)
        $columnasDomingo = [];  // Para almacenar las columnas que son domingo

        // 2. Llenar Encabezados de Días (Filas 4, 5 y 6)
        for ($dia = 1; $dia <= 31; $dia++) {
            $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);

            if ($dia <= $diasEnElMes) {
                $fecha = Carbon::createFromDate($anio, $mes, $dia);
                // ✅ OPCIÓN 2 (Recomendada en Carbon): Usar el método directo
                $diaSemanaNro = $fecha->dayOfWeekIso;
                // Fila 4: Fórmula de recuento adaptada a la última fila real
                $hojaAsistencia->setCellValue("{$colLetter}4", "=COUNTIF({$colLetter}{$filaInicial}:{$colLetter}{$filaFinal}, \">7\")");
                $hojaAsistencia->setCellValue("{$colLetter}5", $inicialesDias[$diaSemanaNro]);
                $hojaAsistencia->setCellValue("{$colLetter}6", $dia);

                // Identificar si es Domingo (7)
                if ($diaSemanaNro == 7) {
                    $columnasDomingo[] = $colLetter;
                }
            } else {
                // Limpiar cabeceras sobrantes en meses < 31 días
                $hojaAsistencia->setCellValue("{$colLetter}4", null);
                $hojaAsistencia->setCellValue("{$colLetter}5", null);
                $hojaAsistencia->setCellValue("{$colLetter}6", null);
            }
        }

        // 3. Llenar Datos de Empleados
        $fila = $filaInicial;

        foreach ($empleados as $indice => $empleado) {
            $f = $fila;
            $hojaAsistencia->setCellValue("A{$f}", $indice + 1);
            $hojaAsistencia->setCellValue("B{$f}", "-");
            $hojaAsistencia->setCellValue("C{$f}", $empleado->nombres);

            // Iterar siempre los 31 días correspondientes a las columnas
            for ($dia = 1; $dia <= 31; $dia++) {
                $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);

                if ($dia <= $diasEnElMes) {
                    $fechaDiaStr = Carbon::createFromDate($anio, $mes, $dia)->format('Y-m-d');
                    $datosDia = $asistenciaPorEmpleado[$empleado->plan_empleado_id][$dia] ?? ['horas' => 0, 'codigo' => 'A'];

                    $codigoAsistencia = $datosDia['codigo'];
                    $horas = $datosDia['horas'];

                    // A. Regla del valor en la celda: solo si es 'F' muestra 'F', los demás muestran sus horas
                    if ($codigoAsistencia === 'F') {
                        $hojaAsistencia->setCellValue("{$colLetter}{$f}", 'F');
                    } else {
                        $hojaAsistencia->setCellValue("{$colLetter}{$f}", $horas > 0 ? $horas : 0);
                    }

                    // B. Aplicar Color de Fondo si el código no es 'A' y tiene color configurado
                    if ($codigoAsistencia !== 'A' && !empty($mapaAsistencia[$codigoAsistencia]['color'])) {
                        $hojaAsistencia->getStyle("{$colLetter}{$f}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $mapaAsistencia[$codigoAsistencia]['color']],
                            ],
                        ]);
                    }

                    // C. Generar Comentario / Nota de Excel
                    $textoComentario = null;

                    if (isset($mapaSuspensiones[$empleado->plan_empleado_id][$fechaDiaStr])) {
                        // C1. Si existe una suspensión registrada
                        $susp = $mapaSuspensiones[$empleado->plan_empleado_id][$fechaDiaStr];
                        $tSusp = $susp->tipoSuspension;

                        // Prioriza la descripcion_corta si está llena
                        $nombreSuspension = !empty($tSusp->descripcion_corta)
                            ? $tSusp->descripcion_corta
                            : ($tSusp->descripcion ?? 'Suspensión');

                        $fInicio = Carbon::parse($susp->fecha_inicio)->format('d/m/Y');
                        $fFin = Carbon::parse($susp->fecha_fin)->format('d/m/Y');

                        $textoComentario = ($fInicio === $fFin)
                            ? "{$nombreSuspension} el {$fInicio}"
                            : "{$nombreSuspension} del {$fInicio} al {$fFin}";

                        if (!empty($susp->observaciones)) {
                            $textoComentario .= "\nObs: " . $susp->observaciones;
                        }

                    } elseif ($codigoAsistencia !== 'A') {
                        // C2. Si no tiene suspensión, usa la descripción por defecto de plan_tipo_asistencias
                        $textoComentario = $mapaAsistencia[$codigoAsistencia]['descripcion'] ?? null;
                    }

                    // Insertar el comentario si se generó algún texto
                    if ($textoComentario) {
                        $comentario = $hojaAsistencia->getComment("{$colLetter}{$f}");
                        $comentario->getText()->createTextRun($textoComentario);
                    }

                } else {
                    $hojaAsistencia->setCellValue("{$colLetter}{$f}", null);
                }
            }
            $fila++;
        }

        // 4. Aplicar Ancho y Color #FFC000 a las columnas Domingo (Filas 5 a $filaFinal)
        foreach ($columnasDomingo as $colDom) {
            // Redimensionar ancho de columna (3.11 puntos)
            $hojaAsistencia->getColumnDimension($colDom)->setWidth(3.11);

            // Estilo de fondo en el rango completo del domingo (Fila 5 hasta última fila de datos)
            $hojaAsistencia->getStyle("{$colDom}5:{$colDom}{$filaFinal}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000'],
                ],
            ]);
        }

        // 5. Agregar Bordes desde A7 hasta AI{ultimaFila}
        // La columna AI corresponde al día 31 (Columna D + 31 - 1 = Columna 34 -> AI)
        $hojaAsistencia->getStyle("A7:AI{$filaFinal}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        // ============================================================
        // NUEVO: Fila de TOTALES por día, debajo de la tabla de horas
        // ============================================================
        $filaTotalesHoras = $filaFinal + 1;
        $hojaAsistencia->setCellValue("C{$filaTotalesHoras}", 'TOTALES');

        for ($dia = 1; $dia <= $diasEnElMes; $dia++) {
            $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);
            $hojaAsistencia->setCellValue(
                "{$colLetter}{$filaTotalesHoras}",
                "=SUM({$colLetter}{$filaInicial}:{$colLetter}{$filaFinal})"
            );
        }

        // Fila de respiro (en blanco, intencional)
        $filaEspacio = $filaTotalesHoras + 1;

        // ============================================================
        // NUEVO: Bloque de COSTO POR HORA (headers + datos por empleado)
        // ============================================================
        $filaHeaderDiasCosto = $filaEspacio + 1;    // equivalente a la fila 5 (letras de día)
        $filaHeaderNumDiasCosto = $filaHeaderDiasCosto + 1; // equivalente a la fila 6 (números de día)
        $filaCostoInicial = $filaHeaderNumDiasCosto + 1;
        $filaCostoFinal = $filaCostoInicial + $totalEmpleados - 1;

        $hojaAsistencia->setCellValue("A{$filaHeaderDiasCosto}", 'Nº');
        $hojaAsistencia->setCellValue("B{$filaHeaderDiasCosto}", 'Nº ORDEN');
        $hojaAsistencia->setCellValue("C{$filaHeaderDiasCosto}", 'NOMBRES');

        // Columna AI: "TOTAL" en la fila de letras, "Monto S/." en la fila de números
        $hojaAsistencia->setCellValue("AI{$filaHeaderDiasCosto}", 'TOTAL');
        $hojaAsistencia->setCellValue("AI{$filaHeaderNumDiasCosto}", 'Monto S/.');

        for ($dia = 1; $dia <= $diasEnElMes; $dia++) {
            $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);
            $fecha = Carbon::createFromDate($anio, $mes, $dia);
            $diaSemanaNro = $fecha->dayOfWeekIso;

            $hojaAsistencia->setCellValue("{$colLetter}{$filaHeaderDiasCosto}", $inicialesDias[$diaSemanaNro]);
            $hojaAsistencia->setCellValue("{$colLetter}{$filaHeaderNumDiasCosto}", $dia);
        }

        $filaCosto = $filaCostoInicial;

        foreach ($empleados as $indice => $empleado) {
            // Fila correspondiente de ESTE MISMO empleado en la tabla de horas de arriba.
            // Como ambos bloques iteran la misma colección en el mismo orden,
            // no hace falta buscar por nombre (VLOOKUP): el offset es directo.
            $rowHoras = $filaInicial + $indice;
            $rowCosto = $filaCosto;

            $hojaAsistencia->setCellValue("A{$rowCosto}", $indice + 1);

            // B: costo por hora "real" del empleado (incluye diferencia/bonificación),
            // tomado directo de PROYECTADA columna AD (proyectado_sueldo_por_hora),
            // sin VLOOKUP porque el orden entre hojas es idéntico.
            $hojaAsistencia->setCellValue("B{$rowCosto}", "=PROYECTADA!AD{$rowHoras}");

            $hojaAsistencia->setCellValue("C{$rowCosto}", $empleado->nombres);

            for ($dia = 1; $dia <= $diasEnElMes; $dia++) {
                $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);

                // El valor de horas de este día se lee de la tabla de horas (rowHoras),
                // no de esta propia fila. $B{$rowCosto} es el costo/hora constante del empleado.
                $hojaAsistencia->setCellValue(
                    "{$colLetter}{$rowCosto}",
                    "=IF({$colLetter}{$rowHoras}=\"F\",0,+\$B{$rowCosto}*{$colLetter}{$rowHoras})"
                );
            }

            // AI: total monto del mes para este empleado = suma de todos los días de costo
            $hojaAsistencia->setCellValue(
                "AI{$rowCosto}",
                "=SUM(D{$rowCosto}:{$ultimaColDia}{$rowCosto})"
            );

            $filaCosto++;
        }

        // ============================================================
        // NUEVO: Fila de TOTALES del bloque de costo (última fila)
        // ============================================================
        $filaTotalesCosto = $filaCostoFinal + 1;
        $hojaAsistencia->setCellValue("C{$filaTotalesCosto}", 'TOTALES');

        for ($dia = 1; $dia <= $diasEnElMes; $dia++) {
            $colLetter = Coordinate::stringFromColumnIndex($columnaInicialDias + $dia - 1);
            $hojaAsistencia->setCellValue(
                "{$colLetter}{$filaTotalesCosto}",
                "=SUM({$colLetter}{$filaCostoInicial}:{$colLetter}{$filaCostoFinal})"
            );
        }

        // Total general del monto pagado (columna AI, todas las filas de costo)
        $hojaAsistencia->setCellValue(
            "AI{$filaTotalesCosto}",
            "=SUM(AI{$filaCostoInicial}:AI{$filaCostoFinal})"
        );

        // ============================================================
        // Estilos: domingos (ya existentes) + bordes extendidos a todo
        // el rango, incluyendo ambos bloques nuevos
        // ============================================================
        foreach ($columnasDomingo as $colDom) {
            $hojaAsistencia->getColumnDimension($colDom)->setWidth(3.11);

            $hojaAsistencia->getStyle("{$colDom}5:{$colDom}{$filaTotalesCosto}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000'],
                ],
            ]);
        }

        $hojaAsistencia->getStyle("A{$filaInicial}:{$ultimaColDia}{$filaTotalesCosto}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // ============================================================
// NUEVO: Fondo #00B0F0 y texto blanco en toda la segunda lista
// (bloque de costo por hora), desde la columna B en adelante
// ============================================================
        $hojaAsistencia->getStyle("B{$filaHeaderDiasCosto}:B{$filaTotalesCosto}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);

        $hojaAsistencia->getStyle(
            "D{$filaCostoInicial}:AI{$filaTotalesCosto}"
        )->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    ],
                    'font' => [
                        'size' => 10,
                    ],
                    'numberFormat' => [
                        'formatCode' => '#,##0.00;-#,##0.00;-',
                    ],
                ]);

        // También bordear la columna AI en ambos bloques (queda fuera del rango de arriba)
        $hojaAsistencia->getStyle("AI{$filaInicial}:AI{$filaTotalesCosto}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }
    /** Columna de cada concepto en la hoja PLAME de la plantilla */
    private const COLUMNAS_HOJA_PLAME = [
        '0117' => 'Y', '0118' => 'Z', '0121' => 'AA', '0201' => 'AB',
        '0312' => 'AD', '0314' => 'AE', '0406' => 'AF', '0904' => 'AG',
        '0601' => 'AH', '0605' => 'AI', '0606' => 'AJ', '0607' => 'AK', '0608' => 'AL',
        '0803' => 'AN', '0804' => 'AO', '0805' => 'AP', '0810' => 'AQ',
    ];

    protected function llenarHojaPlame($spreadsheet, PlanMensual $planillaMensual, $empleados): void
    {
        $hoja = $spreadsheet->getSheetByName('PLAME');

        if (!$hoja) {
            throw new Exception('No se ha configurado la hoja PLAME en la plantilla.');
        }

        $diasDelMes = Carbon::create($planillaMensual->anio, $planillaMensual->mes, 1)->daysInMonth;

        // Celdas de referencia para las fórmulas ($AP$1, $AP$2, $AP$3)
        $hoja->setCellValue('AP1', $planillaMensual->total_horas);
        $hoja->setCellValue('AP2', $planillaMensual->dias_laborables);
        $hoja->setCellValue('AP3', $diasDelMes);

        // Valores de configuración del periodo, inyectados como literales en las fórmulas
        $rmv = $planillaMensual->rmv;
        $pctBeta30 = $planillaMensual->beta30;
        $pctGratificaciones = $planillaMensual->gratificaciones;
        $pctEssaludGratif = $planillaMensual->essalud_gratificaciones;
        $pctCts = $planillaMensual->cts;
        $pctEssalud = $planillaMensual->essalud;

        $fila = 7;

        foreach ($empleados as $empleado) {
            $f = $fila;

            // --- A-U: datos fijos (valores, no fórmulas) ---
            $hoja->setCellValue("A{$f}", $empleado->orden);
            $hoja->setCellValue("B{$f}", $empleado->nombres);
            $hoja->setCellValue("C{$f}", $empleado->sistema_pension);
            $hoja->setCellValue("D{$f}", $empleado->es_pensionista ? 'SI' : 'NO');
            $hoja->setCellValue("E{$f}", $empleado->edad);

            // Suspensión perfecta (F-M)
            $hoja->setCellValue("F{$f}", $empleado->sp_01);
            $hoja->setCellValue("G{$f}", $empleado->sp_02);
            $hoja->setCellValue("H{$f}", $empleado->sp_03);
            $hoja->setCellValue("I{$f}", $empleado->sp_04);
            $hoja->setCellValue("J{$f}", $empleado->sp_05);
            $hoja->setCellValue("K{$f}", $empleado->sp_06);
            $hoja->setCellValue("L{$f}", $empleado->sp_07);
            $hoja->setCellValue("M{$f}", $empleado->sp_08);

            // Suspensión imperfecta (N-U)
            $hoja->setCellValue("N{$f}", $empleado->si_20);
            $hoja->setCellValue("O{$f}", $empleado->si_21);
            $hoja->setCellValue("P{$f}", $empleado->si_22);
            $hoja->setCellValue("Q{$f}", $empleado->si_23);
            $hoja->setCellValue("R{$f}", $empleado->si_24);
            $hoja->setCellValue("S{$f}", $empleado->si_25);
            $hoja->setCellValue("T{$f}", $empleado->si_26);
            $hoja->setCellValue("U{$f}", $empleado->si_27);

            // --- V en adelante: fórmulas ---

            // V: días no laborados = suma de todas las suspensiones
            $hoja->setCellValue("V{$f}", "=SUM(F{$f}:U{$f})");

            // W: días laborados = días del mes - días no laborados
            $hoja->setCellValue("W{$f}", "=\$AP\$3-V{$f}");

            // X: horas de jornada ordinaria del PLAME (las registradas sin los días de suspensión)
            $hoja->setCellValue("X{$f}", $empleado->plame_horas_jornada ?? "='REPORTE DIARIO'!AI{$f}");

            // Y: 0117 COMP. VACACIONAL, viene tal cual de PROYECTADA
            $hoja->setCellValue("Y{$f}", "=PROYECTADA!G{$f}");

            // Z: 0118 REM. VAC. = valor hora (RMV/240 a 3 decimales) × 8 × días vacacionales (si_23, columna Q)
            $hoja->setCellValue("Z{$f}", "=ROUND({$rmv}/240,3)*8*Q{$f}");

            // AA: 0121 REM. JORN. BAS. Con las horas registradas (incluyen descanso médico y licencias con goce), no las de X
            $hoja->setCellValue("AA{$f}", "=SUM(PROYECTADA!D{$f}:E{$f})/\$AP\$1*'REPORTE DIARIO'!AI{$f}");

            // AB: 0201 ASIG. FAMILIAR
            // (solo descuenta la suspensión perfecta, F:M; descanso médico y licencia con goce no la reducen)
            $hoja->setCellValue("AB{$f}", "=PROYECTADA!F{$f}/\$AP\$3*(\$AP\$3-SUM(F{$f}:M{$f}))");

            // AC: REMUNERACIÓN BRUTA = SUMA(Y:AB)
            $hoja->setCellValue("AC{$f}", "=SUM(Z{$f}:AB{$f})");

            // AD: 0312 BONIF. EXT. TEMP. = % essalud_gratificaciones * AF (0406 gratificaciones)
            $hoja->setCellValue("AD{$f}", "={$pctEssaludGratif}%*AF{$f}");

            // AE: 0314 BETA 30%
            $hoja->setCellValue("AE{$f}", "=((({$pctBeta30}%*{$rmv})/\$AP\$3)*(\$AP\$3-SUM(F{$f}:M{$f})))");

            // AF: 0406 GRATIF. FIEST. P. NAV.
            $hoja->setCellValue("AF{$f}", "={$pctGratificaciones}%*AC{$f}");

            // AG: 0904 CTS
            $hoja->setCellValue("AG{$f}", "={$pctCts}%*AC{$f}");

            // AH: 0601 COMI. AFP % — vía VLOOKUP contra el rango de la tabla ($AS$8:$AW$17)
            $hoja->setCellValue("AH{$f}", "=IF(OR(D{$f}=\"SI\",C{$f}=\"SNP\"),0,VLOOKUP(C{$f},\$AS\$8:\$AW\$17,2,FALSE)*SUM(Y{$f}:AB{$f}))");

            // AI: 0605 RENTA 5TA CAT. RET. — siempre 0
            $hoja->setCellValue("AI{$f}", 0);

            // AJ: 0606 PRIMA DE SEG. AFP — columna 3 o 4 según edad
            $hoja->setCellValue("AJ{$f}", "=IF(OR(D{$f}=\"SI\",C{$f}=\"SNP\"),0,VLOOKUP(C{$f},\$AS\$8:\$AW\$17,IF(E{$f}>65,4,3),FALSE)*SUM(Y{$f}:AB{$f}))");

            // AK: 0607 SNP — 13% fijo (regla legal SNP)
            $hoja->setCellValue("AK{$f}", "=IF(D{$f}=\"SI\",0,IF(C{$f}=\"SNP\",13%*SUM(Y{$f}:AB{$f}),0))");

            // AL: 0608 SPP APORT. OBL. — columna 5 (APORTE)
            $hoja->setCellValue("AL{$f}", "=IF(C{$f}=\"SNP\",0,IF(D{$f}=\"SI\",0,VLOOKUP(C{$f},\$AS\$8:\$AW\$17,5,FALSE)*(AC{$f}+Y{$f})))");
            // AM: NETO A PAGAR — tal cual
            $hoja->setCellValue("AM{$f}", "=SUMPRODUCT(ROUND(Y{$f}:AB{$f},2))+SUMPRODUCT(ROUND(AD{$f}:AG{$f},2))-SUMPRODUCT(ROUND(AH{$f}:AL{$f},2))");

            // AN: 0803 PÓLIZA — tal cual, viene de PROYECTADA
            $hoja->setCellValue("AN{$f}", "=PROYECTADA!P{$f}");

            // AO: 0804 ESSALUD
            $hoja->setCellValue("AO{$f}", "={$pctEssalud}%*SUM(Y{$f}:AB{$f})");

            // AP: 0805 SCTR — tal cual, viene de PROYECTADA
            $hoja->setCellValue("AP{$f}", "=PROYECTADA!Q{$f}");

            // AQ: 0810 EPS — tal cual, viene de PROYECTADA
            $hoja->setCellValue("AQ{$f}", "=PROYECTADA!R{$f}");

            // Ajustes manuales (Ajustes PLAME / vacaciones personalizadas): va el monto en vez de la fórmula, resaltado
            // y con una nota que dice que no es fórmula y cuánto calculaba el sistema
            foreach ($empleado->plame_ajustes ?? [] as $codigo => $ajuste) {
                $columna = self::COLUMNAS_HOJA_PLAME[$codigo] ?? null;
                if (!$columna) {
                    continue;
                }
                $celda = "{$columna}{$f}";
                $hoja->setCellValue($celda, (float) $ajuste['monto']);
                $hoja->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
                $comentario = $hoja->getComment($celda);
                $comentario->setWidth('280pt')->setHeight('80pt');
                $comentario->getText()->createTextRun(
                    "MONTO PERSONALIZADO (no es fórmula).\n"
                    . ($ajuste['motivo'] ?? null ? "Motivo: {$ajuste['motivo']}\n" : '')
                    . 'Calculado por el sistema: S/ ' . number_format((float) ($empleado->calculadoPlame((string) $codigo) ?? 0), 2)
                );
            }

            $fila++;
        }
    }
    /**
     * Construye la fórmula de descuento AFP/prima seguro (columna I),
     * personalizada según la condición ya conocida del empleado
     * (pensionista / SNP / mayor de 65), consultando tabla_descuentos.
     */
    protected function formulaDsctoAfp(PlanMensualPersonal $empleado, int $fila): string
    {
        if ($empleado->es_pensionista) {
            return '0';
        }

        // Fila correspondiente al sistema de pensión en la tabla de descuentos
        $filaDescuento = match ($empleado->sistema_pension) {
            'HAB F' => 6,
            'INT F' => 7,
            'PRI F' => 8,
            'PRO F' => 9,
            'SNP' => 10,
            'HAB M' => 11,
            'INT M' => 12,
            'PRI M' => 13,
            'PRO M' => 14,
            default => null,
        };

        if ($filaDescuento === null) {
            return '0';
        }

        // Comisión
        $comision = "\$AL\${$filaDescuento}";

        // Prima según edad
        $prima = $empleado->edad >= 65
            ? "\$AN\${$filaDescuento}"
            : "\$AM\${$filaDescuento}";

        // Aporte obligatorio
        $aporte = "\$AO\${$filaDescuento}";

        return "=H{$fila}*({$comision}+{$prima}+{$aporte})";
    }

    /**
     * Sobrescribe la tabla_descuentos de la hoja "DESCUENTO AFP" con el
     * snapshot vigente de este plan_mensual_id, para que las fórmulas
     * BUSCARV de la hoja PROYECTADA usen los porcentajes correctos del periodo.
     */
    protected function actualizarTablaDescuentosEnHoja($spreadsheet, int $planMensualId): void
    {
        $hojaDescuentos = $spreadsheet->getSheetByName('DESCUENTO AFP');

        if (!$hojaDescuentos) {
            throw new Exception('La plantilla no tiene la hoja "DESCUENTO AFP" con tabla_descuentos.');
        }

        $registros = PlanMensualSpDesc::where('plan_mensual_id', $planMensualId)
            ->orderByRaw("FIELD(codigo, 'HAB F','INT F','PRI F','PRO F','SNP','HAB M','INT M','PRI M','PRO M')")
            ->get();

        if ($registros->isEmpty()) {
            throw new Exception('No hay snapshot de descuentos AFP/SNP para este periodo. Genera la proyección primero.');
        }

        // ⚠️ Asume que tabla_descuentos empieza en la fila 7 (según tu captura),
        // columnas: SEGURO, %DESC, %PRIMA, %PRIMA>65, APORTE.
        // Ajusta la fila inicial si tu plantilla real difiere.
        $filaInicio = 7;

        foreach ($registros as $i => $r) {
            $fila = $filaInicio + $i;

            $hojaDescuentos->setCellValue("AK{$fila}", $r->codigo);
            $hojaDescuentos->setCellValue("AL{$fila}", $r->comision / 100);
            $hojaDescuentos->setCellValue("AM{$fila}", $r->prima_seguros / 100);
            $hojaDescuentos->setCellValue("AN{$fila}", 0); // % PRIMA > 65 siempre 0
            $hojaDescuentos->setCellValue("AO{$fila}", $r->aporte_obligatorio / 100);
        }
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
    /**
     * Costo de cada día de cada empleado (plan_registros_diarios.costo_dia), a partir de sus horas registradas.
     * Corre al generar la planilla (antes era el botón "Recalcular pagos en planilla" de la asistencia mensual).
     *
     * Valor hora = sueldo acordado / horas del mes (plan_mensuales.total_horas), igual que el sueldo
     * pagado de la planilla (PlanMensualPersonal::sueldo_pagado).
     */
    private function actualizarCostosDiarios(PlanMensual $planillaMensual, int $mes, int $anio): void
    {
        $horasMes = (float) ($planillaMensual->total_horas ?: $planillaMensual->dias_laborables * 8);
        if ($horasMes <= 0) {
            return;
        }

        foreach ($planillaMensual->detalle as $detalleMensual) {
            $sueldo = $detalleMensual->empleado?->sueldo($mes, $anio);
            if (!$sueldo) {
                continue;
            }
            $valorHora = $sueldo / $horasMes;

            foreach ($detalleMensual->registrosDiarios as $registroDiario) {
                $registroDiario->costo_dia = round($valorHora * (float) $registroDiario->total_horas, 2);
                $registroDiario->save(); // solo escribe si cambió
            }
        }
    }
}

