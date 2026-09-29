<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\CampoCampania;
use App\Models\CostoMensual;
use App\Models\CuadActividadBono;
use App\Models\CuadRegistroDiario;
use App\Models\ResumenCostoDiario;
use App\Services\Costos\Data\DataReporteCampoServicio;
use App\Services\Planilla\PlanillaServicio;
use App\Support\CalculoHelper;
use App\Support\ExcelHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use Exception;

class ConsolidarReporteMensualCostos
{
    /**
     * Ejecuta el proceso de consolidación mensual de costos.
     *
     * @param int $anio
     * @param int $mes
     * @return CostoMensual
     */
    public function ejecutar(int $anio, int $mes): CostoMensual
    {
        // 1. Recopilar/calcular totales consolidados por campo
        $totalesCalculados = $this->recopilarTotalesPorCampo($anio, $mes);

        // Transacción: si el Excel falla, no quedan totales guardados a medias.
        return DB::transaction(function () use ($anio, $mes, $totalesCalculados) {
            // 2. Guardar primero los totales: el Excel lee costos_mensuales para la hoja 'COSTO TOTAL'
            $costoMensual = $this->guardarOActualizarCostoMensual(
                $anio,
                $mes,
                $totalesCalculados,
                null
            );

            // 3. Generar archivo Excel multipestaña y registrar su ruta
            $reporteFilePath = $this->generarReporteExcelMensual($anio, $mes);
            $costoMensual->update(['reporte_file' => $reporteFilePath]);

            return $costoMensual;
        });
    }

    /**
     * Recopila los totales calculados sumando la información real de todos los campos en el mes/año.
     *
     * @param int $anio
     * @param int $mes
     * @return array
     */
    public function recopilarTotalesPorCampo(int $anio, int $mes): array
    {
        // 1. Obtener la suma del costo real pagado de planilla y bono desde el servicio
        $planillaMensual = collect(app(PlanillaServicio::class)->obtenerProyeccion($mes, $anio));

        // Pagado de planilla = sueldo proporcional + aportes + vacaciones pagadas + bono de asistencia
        $costoPlanillaPagado = app(ConsolidarManoObraIndirectaServicio::class)->totalPagado($anio, $mes)['total'];
        $costoBonoProductividadPagado = (float) $planillaMensual->sum('bono_productividad');

        // 2. Obtener la suma de los costos asignados a campo desde resumen_costo_diarios
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth()->format('Y-m-d');
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d');

        // Calculado de planilla = costo en campo + mano de obra indirecta (lo pagado sin trabajo en campo)
        $costoManoObraIndirecta = (float) ResumenCostoDiario::where('origen_tipo', ConsolidarManoObraIndirectaServicio::ORIGEN)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->sum('costo_total');
        $costoPlanillaCalculado = (float) ResumenCostoDiario::where('origen_tipo', 'planilla')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->sum('costo_total') + $costoManoObraIndirecta;

        $costoBonoProductividadCalculado = (float) ResumenCostoDiario::whereIn('origen_tipo', ['planilla_bono_productividad'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->sum('costo_total');

        // Servicios en campo: pagado = tabla fuente (por fecha del trabajo), calculado = lo consolidado por campo.
        // Deben coincidir; la diferencia aparece si falta reconsolidar.
        $costoServicioCampoPagado = app(ConsolidarCostoServiciosCampoServicio::class)
            ->costoPagadoEnRango($fechaInicio, $fechaFin);

        $costoServicioCampoCalculado = (float) ResumenCostoDiario::where('origen_tipo', ConsolidarCostoServiciosCampoServicio::ORIGEN)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->sum('costo_total');

        // Insumos: "pagado" = costo de todas las salidas del mes según el kardex,
        // calculado = lo consolidado por campo (con campaña, FDM o SIN CAMPAÑA). Deben coincidir.
        $costoSalidasInsumos = app(ConsolidarCostoInsumosServicio::class)->costoSalidasEnRango($fechaInicio, $fechaFin);

        $costoInsumosCalculado = ResumenCostoDiario::whereIn('origen_tipo', ConsolidarCostoInsumosServicio::ORIGENES)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->selectRaw('origen_tipo, SUM(costo_total) as costo')
            ->groupBy('origen_tipo')
            ->pluck('costo', 'origen_tipo');

        return [
            'costo_planilla' => round($costoPlanillaPagado, 2),
            'costo_bono_productividad' => round($costoBonoProductividadPagado, 2),
            'costo_pesticida' => round($costoSalidasInsumos['pesticida'], 2),
            'costo_fertilizante' => round($costoSalidasInsumos['fertilizante'], 2),
            'costo_pesticida_calculado' => round((float) ($costoInsumosCalculado['pesticida'] ?? 0), 2),
            'costo_fertilizante_calculado' => round((float) ($costoInsumosCalculado['fertilizante'] ?? 0), 2),
            'costo_servicio_campo' => round($costoServicioCampoPagado, 2),
            'costo_planilla_calculado' => round($costoPlanillaCalculado, 2),
            'costo_mano_obra_indirecta' => round($costoManoObraIndirecta, 2),
            'costo_bono_productividad_calculado' => round($costoBonoProductividadCalculado, 2),
            'costo_servicio_campo_calculado' => round($costoServicioCampoCalculado, 2),
            // Cuadrilla = jornal + bonos que se pagan con el jornal
            'costo_cuadrilla' => round($this->costoCuadrillaPagado($fechaInicio, $fechaFin), 2),
            'costo_cuadrilla_calculado' => round((float) ResumenCostoDiario::where('origen_tipo', 'cuadrilla')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->sum('costo_total'), 2),
            // Cuadrilla bono = bonos que se pagan aparte (hoja COSTO CUADRILLA BONOS), comparados por separado
            'costo_cuadrilla_bono' => round((float) $this->bonosCuadrillaAparte($fechaInicio, $fechaFin)->sum('total_bono'), 2),
            'costo_cuadrilla_bono_calculado' => round((float) ResumenCostoDiario::where('origen_tipo', 'cuadrilla_bono')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->sum('costo_total'), 2),
            'costo_maquinaria_calculado' => 0.00,
            'costo_gastos_generales_calculado' => 0.00,
        ];
    }

    /**
     * Costo de cuadrilla según los registros diarios (lo que corresponde pagar), sin desgloses:
     * jornal por las horas no destajo + bonos que se pagan con el jornal. Los bonos que se pagan
     * aparte van en su propia columna (costo_cuadrilla_bono). Comisiones y gastos adicionales no
     * entran aquí. Debe coincidir con lo consolidado por campo; si no, hay registros cuyo detalle
     * de horas no suma el total del día (ver tarea pendiente de cuadrilla) o campos sin campaña.
     */
    private function costoCuadrillaPagado(string $fechaInicio, string $fechaFin): float
    {
        return (float) CuadRegistroDiario::with('actividadesBonos')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->sum(fn(CuadRegistroDiario $rd) => $rd->total_pago_jornal);
    }

    /**
     * Discriminador de CUADRILLA (hoja COSTO CUADRILLA): lo pagado (jornal + bonos con jornal)
     * repartido por actividad, para ubicar la diferencia con lo consolidado por campo.
     * La suma de "total" es igual al costo pagado de cuadrilla (total_pago_jornal del mes).
     *
     * - Una fila por tramo del detalle de horas: jornal/8 × horas (0 si es destajo) + el bono con
     *   jornal de la actividad del mismo campo y labor.
     * - Bonos con jornal sin tramo que les corresponda: fila propia.
     * - Si las horas del registro no coinciden con el detalle: fila "DIFERENCIA" por ese monto.
     * - Marcas en la labor: [destajo], [campo sin campaña] (va al resumen como SIN CAMPAÑA; FDM no se marca),
     *   [riego] (el resumen lo costea desde el reporte de riego, no desde este tramo).
     *
     * @return array<int, array{fecha:string, cuadrillero:string, campo:string, labor:string, jornal:float, bono:float}>
     */
    public function discriminadorCuadrilla(string $fechaInicio, string $fechaFin): array
    {
        $registros = CuadRegistroDiario::with(['cuadrillero', 'detalleHoras.labores', 'actividadesBonos.actividad', 'actividadesBonos.metodo'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->sortBy(fn($r) => [$r->fecha, $r->cuadrillero?->nombres]);

        // Campañas vigentes en el rango, para marcar tramos en campos sin campaña
        $campanias = CampoCampania::where('fecha_inicio', '<=', $fechaFin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio))
            ->get()
            ->groupBy('campo');
        $tieneCampania = function (?string $campo, string $fecha) use ($campanias): bool {
            foreach ($campanias[$campo] ?? [] as $c) {
                if (Carbon::parse($c->fecha_inicio)->toDateString() <= $fecha
                    && (!$c->fecha_fin || Carbon::parse($c->fecha_fin)->toDateString() >= $fecha)) {
                    return true;
                }
            }
            return false;
        };

        $filas = [];
        foreach ($registros as $rd) {
            $fecha = Carbon::parse($rd->fecha)->toDateString();
            $cuadrillero = $rd->cuadrillero?->nombres ?? '-';
            $jornal = (float) ($rd->costo_personalizado_dia ?: $rd->jornal_aplicado);
            // Solo bonos con jornal (los que se pagan aparte van en COSTO CUADRILLA BONOS)
            $bonos = $rd->actividadesBonos->where('se_paga_con_jornal', true)->where('total_bono', '>', 0)->values();
            // Labores a destajo del día: bono con método sin estándar (mismo criterio que el trigger)
            $laboresDestajo = $rd->actividadesBonos
                ->filter(fn($b) => $b->metodo_id && $b->metodo && $b->metodo->estandar === null)
                ->map(fn($b) => (string) $b->actividad?->codigo_labor)
                ->all();
            $usados = [];
            $jornalDetalle = 0.0;
            $minutosDetalle = 0;

            foreach ($rd->detalleHoras as $d) {
                $minutos = CalculoHelper::obtenerDiferenciaMinutos($d->hora_inicio, $d->hora_fin);
                $minutosDetalle += $minutos;

                $esDestajo = in_array((string) $d->codigo_labor, $laboresDestajo, true);
                $costoJornal = $esDestajo ? 0.0 : $jornal / 8 * ($minutos / 60);
                $jornalDetalle += $costoJornal;

                // Bono con jornal de la actividad del mismo campo y labor (una sola vez)
                $bono = 0.0;
                foreach ($bonos as $i => $b) {
                    if (!isset($usados[$i]) && $b->actividad?->campo === $d->campo_nombre
                        && (string) $b->actividad?->codigo_labor === (string) $d->codigo_labor) {
                        $bono += (float) $b->total_bono;
                        $usados[$i] = true;
                    }
                }

                $marcas = array_filter([
                    $esDestajo ? '[destajo]' : null,
                    $d->campo_nombre !== 'FDM' && !$tieneCampania($d->campo_nombre, $fecha) ? '[campo sin campaña]' : null,
                    $d->campo_nombre === 'FDM' && (string) $d->codigo_labor === '81' ? '[riego]' : null,
                ]);
                $filas[] = [
                    'fecha' => $fecha,
                    'cuadrillero' => $cuadrillero,
                    'campo' => $d->campo_nombre,
                    'labor' => trim((is_object($d->labores) ? ($d->labores->nombre_labor ?? $d->codigo_labor) : $d->codigo_labor) . ' ' . implode(' ', $marcas)),
                    // Sin redondear: la suma de miles de filas debe dar exacto lo pagado
                    'jornal' => $costoJornal,
                    'bono' => $bono,
                ];
            }

            // Bonos con jornal cuya actividad no tiene tramo en el detalle
            foreach ($bonos as $i => $b) {
                if (isset($usados[$i])) {
                    continue;
                }
                $campo = $b->actividad?->campo ?? '-';
                $filas[] = [
                    'fecha' => $fecha,
                    'cuadrillero' => $cuadrillero,
                    'campo' => $campo,
                    'labor' => trim(($b->actividad?->nombre_labor ?? 'Bono') . ' [bono sin tramo en el detalle]'
                        . ($campo !== 'FDM' && !$tieneCampania($campo, $fecha) ? ' [campo sin campaña]' : '')),
                    'jornal' => 0.0,
                    'bono' => (float) $b->total_bono,
                ];
            }

            // Lo pagado por jornal (horas registradas) vs lo repartido en el detalle
            $diferencia = (float) $rd->costo_dia - $jornalDetalle;
            if (abs($diferencia) >= 0.0001) {
                $filas[] = [
                    'fecha' => $fecha,
                    'cuadrillero' => $cuadrillero,
                    'campo' => '-',
                    'labor' => sprintf('DIFERENCIA: %s h registradas vs %s h en detalle', rtrim(rtrim(number_format((float) $rd->total_horas, 2, '.', ''), '0'), '.'), rtrim(rtrim(number_format($minutosDetalle / 60, 2, '.', ''), '0'), '.')),
                    'jornal' => $diferencia,
                    'bono' => 0.0,
                ];
            }
        }

        return $filas;
    }

    /**
     * Bonos de cuadrilla que NO se pagan con el jornal (se_paga_con_jornal = false), de registros
     * diarios del rango, estén o no pagados. Fuente de la hoja COSTO CUADRILLA BONOS.
     */
    public function bonosCuadrillaAparte(string $fechaInicio, string $fechaFin)
    {
        return CuadActividadBono::with(['registroDiario.cuadrillero', 'actividad', 'metodo', 'producciones'])
            ->where('se_paga_con_jornal', false)
            ->where('total_bono', '>', 0) // mismo criterio que el resumen por campo
            ->whereHas('registroDiario', fn($q) => $q->whereBetween('fecha', [$fechaInicio, $fechaFin]))
            ->get()
            ->sortBy(fn($b) => [$b->registroDiario->fecha, $b->registroDiario->cuadrillero?->nombres])
            ->values();
    }

    /**
     * Genera el libro Excel consolidado mensual procesando la pestaña 'COSTO LABORAL PLANILLA'
     * y la pestaña 'COSTO POR CAMPO' con la data unificada de resumen_costo_diarios.
     *
     * @param int $anio
     * @param int $mes
     * @return string Ruta del archivo guardado en el storage público
     */
    public function generarReporteExcelMensual(int $anio, int $mes): string
    {
        // 1. Cargar la plantilla principal
        $spreadsheet = ExcelHelper::cargarPlantilla('bdd_costo_mensual.xlsx');

        // ==========================================
        // PARTE A: PESTAÑA 'COSTO LABORAL PLANILLA'
        // ==========================================
        $hojaPlanilla = $spreadsheet->getSheetByName('COSTO LABORAL PLANILLA');

        if (!$hojaPlanilla) {
            throw new Exception("No se ha configurado la pestaña 'COSTO LABORAL PLANILLA' en la plantilla bdd_costo_mensual.xlsx.");
        }

        // A1. Obtener datos de la planilla
        $planillaMensual = collect(app(PlanillaServicio::class)->obtenerProyeccion($mes, $anio));
        $totalRegistrosPlanilla = $planillaMensual->count();

        if ($totalRegistrosPlanilla === 0) {
            throw new Exception("No hay datos de planilla para el periodo seleccionado ({$mes}/{$anio}).");
        }

        // A2. Cabecera (Fila 5)
        $nombreMes = mb_strtoupper(Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName);
        $hojaPlanilla->setCellValue("A5", "{$nombreMes} {$anio}");
        $hojaPlanilla->setCellValue("C5", $planillaMensual->sum('pagado_sueldo_bruto_negro'));
        $hojaPlanilla->getStyle("C5")->getNumberFormat()->setFormatCode('#,##0.00');
        $hojaPlanilla->setCellValue("E5", $totalRegistrosPlanilla);
        $hojaPlanilla->setCellValue("G5", now()->format('d/m/Y H:i:s'));

        // A3. Poblar Tabla de Planilla
        $tablaPlanilla = $hojaPlanilla->getTableByName('tblCostoPlanillaMensual');
        $filaInicioPlanilla = $tablaPlanilla ? (ExcelHelper::primeraFila($tablaPlanilla) + 1) : 8;

        if ($totalRegistrosPlanilla > 1) {
            $hojaPlanilla->insertNewRowBefore($filaInicioPlanilla + 1, $totalRegistrosPlanilla - 1);
        }

        $filaActual = $filaInicioPlanilla;
        foreach ($planillaMensual as $index => $empleado) {
            $nombre = is_array($empleado) ? ($empleado['nombres'] ?? '') : ($empleado->nombres ?? '');
            $sueldoPagado = is_array($empleado) ? ($empleado['sueldo_pagado'] ?? 0) : ($empleado->sueldo_pagado ?? 0);
            $aportesTrabajador = is_array($empleado) ? ($empleado['aportes_trabajador'] ?? 0) : ($empleado->aportes_trabajador ?? 0);
            $aportesEmpleador = is_array($empleado) ? ($empleado['aportes_empleador'] ?? 0) : ($empleado->aportes_empleador ?? 0);
            $bonoProductividad = is_array($empleado) ? ($empleado['bono_productividad'] ?? 0) : ($empleado->bono_productividad ?? 0);
            $costoTotal = is_array($empleado)
                ? ($empleado['costo_total_empresa'] ?? $empleado['pagado_sueldo_bruto_negro'] ?? 0)
                : ($empleado->costo_total_empresa ?? $empleado->pagado_sueldo_bruto_negro ?? 0);

            $hojaPlanilla->setCellValue("A{$filaActual}", $index + 1);
            $hojaPlanilla->setCellValue("B{$filaActual}", $nombre);
            $hojaPlanilla->setCellValue("C{$filaActual}", $sueldoPagado);
            $hojaPlanilla->setCellValue("D{$filaActual}", $aportesTrabajador);
            $hojaPlanilla->setCellValue("E{$filaActual}", $aportesEmpleador);
            $hojaPlanilla->setCellValue("F{$filaActual}", $bonoProductividad);
            $hojaPlanilla->setCellValue("G{$filaActual}", $costoTotal);

            $hojaPlanilla->getStyle("C{$filaActual}:F{$filaActual}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            $filaActual++;
        }

        // A4. Fila de Totales al final de la tabla
        $filaTotalesPlanilla = $filaActual;
        $filaUltimoDatoPlanilla = $filaActual - 1;

        $hojaPlanilla->setCellValue("B{$filaTotalesPlanilla}", "TOTAL GENERAL");
        $hojaPlanilla->setCellValue("C{$filaTotalesPlanilla}", "=SUM(C{$filaInicioPlanilla}:C{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("D{$filaTotalesPlanilla}", "=SUM(D{$filaInicioPlanilla}:D{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("E{$filaTotalesPlanilla}", "=SUM(E{$filaInicioPlanilla}:E{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("F{$filaTotalesPlanilla}", "=SUM(F{$filaInicioPlanilla}:F{$filaUltimoDatoPlanilla})");

        $hojaPlanilla->getStyle("B{$filaTotalesPlanilla}:F{$filaTotalesPlanilla}")->getFont()->setBold(true);
        $hojaPlanilla->getStyle("C{$filaTotalesPlanilla}:F{$filaTotalesPlanilla}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        if ($tablaPlanilla) {
            ExcelHelper::actualizarRangoTabla($tablaPlanilla, $filaTotalesPlanilla);
        }

        // ==========================================
        // PARTE A2: PESTAÑA 'COSTO CUADRILLA BONOS'
        // Bonos de cuadrilla que NO se pagan con el jornal (se_paga_con_jornal = false), del mes.
        // Su total va a COSTO TOTAL columna E (no a CUADRILLA) y se compara con 'Cuadrilla bono'.
        // ==========================================
        $hojaBonos = $spreadsheet->getSheetByName('COSTO CUADRILLA BONOS');

        if ($hojaBonos) {
            $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
            $bonos = $this->bonosCuadrillaAparte($inicioMes->toDateString(), $inicioMes->copy()->endOfMonth()->toDateString());

            $tablaBonos = $hojaBonos->getTableByName('tblBonoCuadrilla');
            $filaInicioBonos = $tablaBonos ? (ExcelHelper::primeraFila($tablaBonos) + 1) : 8;

            if ($bonos->count() > 1) {
                $hojaBonos->insertNewRowBefore($filaInicioBonos + 1, $bonos->count() - 1);
            }

            $fila = $filaInicioBonos;
            foreach ($bonos as $i => $bono) {
                $actividad = $bono->actividad;
                $hojaBonos->setCellValue("A{$fila}", $i + 1);
                $hojaBonos->setCellValue("B{$fila}", Carbon::parse($bono->registroDiario->fecha)->format('d/m/Y'));
                $hojaBonos->setCellValue("C{$fila}", $bono->registroDiario->cuadrillero?->nombres ?? '-');
                $hojaBonos->setCellValue("D{$fila}", $actividad?->campo ?? '-');
                $hojaBonos->setCellValue("E{$fila}", $actividad?->nombre_labor ?? $actividad?->codigo_labor ?? '-');
                $hojaBonos->setCellValue("F{$fila}", (float) $bono->producciones->sum('produccion'));
                $hojaBonos->setCellValue("G{$fila}", $bono->bono_manual ? 'Manual' : ($bono->metodo?->titulo ?? '-'));
                $hojaBonos->setCellValue("H{$fila}", (float) $bono->total_bono);
                $fila++;
            }
            $ultimaFilaBonos = max($filaInicioBonos, $fila - 1);

            $hojaBonos->getStyle("F{$filaInicioBonos}:F{$ultimaFilaBonos}")->getNumberFormat()->setFormatCode('#,##0.00');
            $hojaBonos->getStyle("H{$filaInicioBonos}:H{$ultimaFilaBonos}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($tablaBonos) {
                ExcelHelper::actualizarRangoTabla($tablaBonos, $ultimaFilaBonos);
            }

            // Cabecera
            $hojaBonos->setCellValue('A5', mb_strtoupper($inicioMes->locale('es')->monthName) . " {$anio}");
            $hojaBonos->setCellValue('D5', "=SUM(H{$filaInicioBonos}:H{$ultimaFilaBonos})");
            $hojaBonos->getStyle('D5')->getNumberFormat()->setFormatCode('#,##0.00');
            $hojaBonos->setCellValue('F5', $bonos->pluck('registroDiario.cuadrillero_id')->unique()->count());
            $hojaBonos->setCellValue('H5', now()->format('d/m/Y H:i:s'));
        }

        // ==========================================
        // PARTE A3: PESTAÑA 'COSTO CUADRILLA' (discriminador de cuadrilla)
        // Lo pagado (jornal + bonos con jornal) por actividad; su total = COSTO TOTAL D5.
        // Se ubica por su tabla (tblCostoCuadrilla) para no depender del nombre de la hoja.
        // ==========================================
        $hojaCuadrilla = null;
        $tablaCuadrilla = null;
        foreach ($spreadsheet->getWorksheetIterator() as $hoja) {
            if ($t = $hoja->getTableByName('tblCostoCuadrilla')) {
                [$hojaCuadrilla, $tablaCuadrilla] = [$hoja, $t];
                break;
            }
        }

        if ($hojaCuadrilla) {
            if ($hojaCuadrilla->getTitle() !== 'COSTO CUADRILLA' && !$spreadsheet->getSheetByName('COSTO CUADRILLA')) {
                $hojaCuadrilla->setTitle('COSTO CUADRILLA');
            }

            $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
            $filasCuadrilla = $this->discriminadorCuadrilla($inicioMes->toDateString(), $inicioMes->copy()->endOfMonth()->toDateString());

            $filaInicioCuad = ExcelHelper::primeraFila($tablaCuadrilla) + 1;
            if (count($filasCuadrilla) > 1) {
                $hojaCuadrilla->insertNewRowBefore($filaInicioCuad + 1, count($filasCuadrilla) - 1);
            }

            $fila = $filaInicioCuad;
            foreach ($filasCuadrilla as $i => $f) {
                $hojaCuadrilla->setCellValue("A{$fila}", $i + 1);
                $hojaCuadrilla->setCellValue("B{$fila}", Carbon::parse($f['fecha'])->format('d/m/Y'));
                $hojaCuadrilla->setCellValue("C{$fila}", $f['cuadrillero']);
                $hojaCuadrilla->setCellValue("D{$fila}", $f['campo']);
                $hojaCuadrilla->setCellValue("E{$fila}", $f['labor']);
                $hojaCuadrilla->setCellValue("F{$fila}", $f['jornal']);
                $hojaCuadrilla->setCellValue("G{$fila}", $f['bono']);
                $hojaCuadrilla->setCellValue("H{$fila}", "=F{$fila}+G{$fila}");
                $fila++;
            }
            $ultimaFilaCuad = max($filaInicioCuad, $fila - 1);

            $hojaCuadrilla->getStyle("F{$filaInicioCuad}:H{$ultimaFilaCuad}")->getNumberFormat()->setFormatCode('#,##0.00');
            ExcelHelper::actualizarRangoTabla($tablaCuadrilla, $ultimaFilaCuad);

            $hojaCuadrilla->setCellValue('A5', mb_strtoupper($inicioMes->locale('es')->monthName) . " {$anio}");
            $hojaCuadrilla->setCellValue('D5', "=SUM(H{$filaInicioCuad}:H{$ultimaFilaCuad})");
            $hojaCuadrilla->getStyle('D5')->getNumberFormat()->setFormatCode('#,##0.00');
            $hojaCuadrilla->setCellValue('F5', collect($filasCuadrilla)->pluck('cuadrillero')->unique()->count());
            $hojaCuadrilla->setCellValue('H5', now()->format('d/m/Y H:i:s'));
        }

        // ==========================================
        // PARTE B: PESTAÑA 'COSTO POR CAMPO'
        // ==========================================
        $hojaCampo = $spreadsheet->getSheetByName('COSTO POR CAMPO');

        if ($hojaCampo) {
            // B1. Obtener el rango de fechas para el mes completo
            $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d');

            // B2. Obtener data unificada mediante DataReporteCampoServicio
            $datosCampo = app(DataReporteCampoServicio::class)->obtenerDataUnificada($fechaInicio, $fechaFin);
            $totalDatosCampo = count($datosCampo);

            if ($totalDatosCampo > 0) {
                $filaInicioCampo = 5;

                // B4. Poblar datos en la hoja: de una sola vez (fila por fila con estilo tardaba ~20 s con 10 mil filas)
                $filas = array_map(fn($dato) => [
                    $dato['fecha'] ?? '',
                    $dato['campania'] ?? '',
                    $dato['campo'] ?? '',
                    $dato['tipo_gasto'] ?? '',
                    $dato['detalle_labor'] ?? '',
                    $dato['trabajador'] ?? '',
                    $dato['horas'] ?? '',
                    $dato['cantidad_jornales'] ?? '',
                    $dato['cantidad'] ?? '',
                    $dato['proveedor'] ?? '',
                    $dato['n_documento'] ?? '',
                    $dato['costo'] ?? '',
                    $dato['observacion'] ?? '',
                ], $datosCampo);
                $hojaCampo->fromArray($filas, null, "A{$filaInicioCampo}", true);
                $filaCampo = $filaInicioCampo + count($filas);
                $hojaCampo->getStyle("L{$filaInicioCampo}:L" . ($filaCampo - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }
        }

        // L. Fila de Totales al final de la tabla
        $filaTotalesCampo = $filaCampo;
        $filaUltimoDatoCampo = $filaCampo - 1;

        $hojaCampo->setCellValue("K{$filaTotalesCampo}", "TOTAL GENERAL");
        $hojaCampo->setCellValue("L{$filaTotalesCampo}", "=SUM(L{$filaInicioCampo}:L{$filaUltimoDatoCampo})");

        $hojaCampo->getStyle("K{$filaTotalesCampo}:L{$filaTotalesCampo}")->getFont()->setBold(true);
        $hojaCampo->getStyle("K{$filaTotalesCampo}:L{$filaTotalesCampo}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // ==========================================
// PARTE C: PESTAÑA 'COSTO TOTAL'
// ==========================================
        $hojaCostoTotal = $spreadsheet->getSheetByName('COSTO TOTAL');

        if ($hojaCostoTotal) {
            // 1. Obtener la última fila con datos de la hoja 'COSTO POR CAMPO'
            // Si no hay datos, por defecto apuntará hasta la fila 5
            $ultimaFilaCampo = isset($filaCampo) ? ($filaCampo - 1) : 5;
            if ($ultimaFilaCampo < 5) {
                $ultimaFilaCampo = 5;
            }

            // 2. Determinar la fila del TOTAL en 'COSTO LABORAL PLANILLA' (por defecto fila 5 si no hay datos)
            $filaTotalBono = isset($filaTotalesPlanilla) ? $filaTotalesPlanilla : 5;

            // C1. Obtener la entidad de costos mensual de la BDD o del servicio
            $costoMensual = CostoMensual::where('anio', $anio)
                ->where('mes', $mes)
                ->first(); // O traerlo mediante tu servicio correspondiente

            // SUMAR.SI sobre 'COSTO POR CAMPO': columna D (tipo de gasto) y L (costo)
            $sumaPorTipo = fn(string $origen) => "=SUMIF('COSTO POR CAMPO'!D5:D{$ultimaFilaCampo}, \""
                . ResumenCostoDiario::etiquetaOrigen($origen)
                . "\", 'COSTO POR CAMPO'!L5:L{$ultimaFilaCampo})";

            // Columnas: B planilla | C planilla bono | D cuadrilla | E cuadrilla bono | F maquinaria |
            //           G pesticidas | H fertilizantes | I servicios campos | J generales | K costo total
            // Fila 5 = costo pagado; fila 6 = costo por campo totalizado; fila 7 = diferencial
            $columnas = [
                // B: pagado total (hoja CUADRE PLANILLA) vs campo + mano de obra indirecta
                'B' => [
                    (float) ($costoMensual->costo_planilla ?? 0),
                    $sumaPorTipo('planilla') . '+' . substr($sumaPorTipo(ConsolidarManoObraIndirectaServicio::ORIGEN), 1),
                ],
                // C5: TOTAL GENERAL de la columna F (bono) en 'COSTO LABORAL PLANILLA'
                'C' => ["='COSTO LABORAL PLANILLA'!F{$filaTotalBono}", $sumaPorTipo('planilla_bono_productividad')],
                // D: jornal + bonos que se pagan con el jornal
                'D' => [(float) ($costoMensual->costo_cuadrilla ?? 0), $sumaPorTipo('cuadrilla')],
                // E: bonos de cuadrilla que se pagan aparte (hoja 'COSTO CUADRILLA BONOS')
                'E' => [(float) ($costoMensual->costo_cuadrilla_bono ?? 0), $sumaPorTipo('cuadrilla_bono')],
                'F' => [(float) ($costoMensual->costo_maquinaria ?? 0), (float) ($costoMensual->costo_maquinaria_calculado ?? 0)],
                'G' => [(float) ($costoMensual->costo_pesticida ?? 0), $sumaPorTipo('pesticida')],
                'H' => [(float) ($costoMensual->costo_fertilizante ?? 0), $sumaPorTipo('fertilizante')],
                // I: servicios en campo (tabla servicios_campo_detalles)
                'I' => [(float) ($costoMensual->costo_servicio_campo ?? 0), $sumaPorTipo(ConsolidarCostoServiciosCampoServicio::ORIGEN)],
                'J' => [(float) ($costoMensual->costo_gastos_generales ?? 0), (float) ($costoMensual->costo_gastos_generales_calculado ?? 0)],
            ];

            foreach ($columnas as $col => [$pagado, $calculado]) {
                $hojaCostoTotal->setCellValue("{$col}5", $pagado);
                $hojaCostoTotal->setCellValue("{$col}6", $calculado);
                $hojaCostoTotal->setCellValue("{$col}7", "={$col}5-{$col}6"); // la plantilla no las trae todas
            }

            // Formato numérico en soles a toda la matriz (incluye K = costo total)
            $hojaCostoTotal->getStyle("B5:K7")
                ->getNumberFormat()
                ->setFormatCode('"S/" #,##0.00');
        }

        // PARTE D: PESTAÑA 'CUADRE PLANILLA' (se crea aquí; no viene en la plantilla)
        $this->escribirHojaCuadrePlanilla($spreadsheet, $anio, $mes);

        // ==========================================
        // PARTE C: GUARDAR ARCHIVO
        // ==========================================
        $mesFormateado = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $folderPath = "reporte/{$anio}-{$mesFormateado}";
        $fileName = "REPORTE_CONSOLIDADO_MENSUAL_{$anio}_{$mesFormateado}.xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        Storage::disk('public')->makeDirectory($folderPath);

        $writer = new Xlsx($spreadsheet);
        // Las fórmulas (SUMAR.SI sobre miles de filas) las calcula Excel al abrir el archivo
        $writer->setPreCalculateFormulas(false);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
    /**
     * Hoja 'CUADRE PLANILLA': lo pagado a los trabajadores de planilla contra lo que llega a la BDD
     * (costo en campo + mano de obra indirecta por concepto), con el detalle de lo que no cuadra.
     */
    private function escribirHojaCuadrePlanilla(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, int $anio, int $mes): void
    {
        $moi = app(ConsolidarManoObraIndirectaServicio::class);
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $pagado = $moi->totalPagado($anio, $mes);
        $campo = (float) ResumenCostoDiario::where('origen_tipo', 'planilla')->whereBetween('fecha', [$inicio, $fin])->sum('costo_total');
        $conceptos = $moi->totalesPorConcepto($inicio, $fin);
        $diferencias = $moi->diferenciasPorTrabajador($anio, $mes);

        if ($existente = $spreadsheet->getSheetByName('CUADRE PLANILLA')) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($existente));
        }
        $hoja = $spreadsheet->createSheet();
        $hoja->setTitle('CUADRE PLANILLA');

        $soles = '"S/" #,##0.00';
        $titulo = fn(string $celda) => $hoja->getStyle($celda)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '31869B']],
        ]);
        $bordes = fn(string $rango) => $hoja->getStyle($rango)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $nombreMes = Carbon::create($anio, $mes, 1)->translatedFormat('F Y');
        $hoja->setCellValue('A1', 'CUADRE DE PLANILLA — ' . mb_strtoupper($nombreMes));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->setCellValue('A2', 'Costo en campo + mano de obra indirecta debe ser igual a lo pagado a los trabajadores de planilla.');
        $hoja->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('7F7F7F');

        // 1) Pagado
        $f = 4;
        $hoja->setCellValue("A{$f}", 'PAGADO A TRABAJADORES DE PLANILLA');
        $hoja->setCellValue("C{$f}", 'MONTO');
        $titulo("A{$f}:C{$f}");
        $iniPagado = $f + 1;
        foreach ([
            'Sueldo proporcional + aportes del trabajador y del empleador' => $pagado['sueldo_aportes'],
            'Vacaciones pagadas (monto decidido por la empresa)' => $pagado['vacaciones_neto_pagadas'],
            'Bonificación 100% asistencia' => $pagado['bonificacion_asistencia'],
        ] as $texto => $monto) {
            $f++;
            $hoja->setCellValue("A{$f}", $texto);
            $hoja->setCellValue("C{$f}", round($monto, 2));
        }
        $f++;
        $filaTotalPagado = $f;
        $hoja->setCellValue("A{$f}", 'TOTAL PAGADO');
        $hoja->setCellValue("C{$f}", "=SUM(C{$iniPagado}:C" . ($f - 1) . ')');
        $hoja->getStyle("A{$f}:C{$f}")->getFont()->setBold(true);
        $bordes('A4:C' . $f);

        // 2) Costo en la BDD
        $f += 2;
        $inicioCosto = $f;
        $hoja->setCellValue("A{$f}", 'COSTO REGISTRADO EN LA BDD');
        $hoja->setCellValue("B{$f}", 'HORAS');
        $hoja->setCellValue("C{$f}", 'MONTO');
        $titulo("A{$f}:C{$f}");
        $f++;
        $iniCosto = $f;
        $hoja->setCellValue("A{$f}", 'Costo en campo (horas asistidas con detalle por campo y riego)');
        $hoja->setCellValue("C{$f}", round($campo, 2));
        $hoja->getStyle("A{$f}")->getFont()->setBold(true);
        $f++;
        $hoja->setCellValue("A{$f}", 'Mano de obra indirecta:');
        $hoja->getStyle("A{$f}")->getFont()->setBold(true);
        foreach ($conceptos as $concepto => $t) {
            $f++;
            $hoja->setCellValue("A{$f}", '   ' . $concepto);
            $hoja->setCellValue("B{$f}", $t['horas'] > 0 ? round($t['horas'], 2) : null);
            $hoja->setCellValue("C{$f}", round($t['costo'], 2));
        }
        $f++;
        $filaTotalCosto = $f;
        $hoja->setCellValue("A{$f}", 'TOTAL COSTO (campo + mano de obra indirecta)');
        $hoja->setCellValue("C{$f}", "=SUM(C{$iniCosto}:C" . ($f - 1) . ')');
        $hoja->getStyle("A{$f}:C{$f}")->getFont()->setBold(true);
        $bordes("A{$inicioCosto}:C{$f}");

        // 3) Diferencia
        $f += 2;
        $hoja->setCellValue("A{$f}", 'DIFERENCIA (pagado − costo)');
        $hoja->setCellValue("C{$f}", "=C{$filaTotalPagado}-C{$filaTotalCosto}");
        $hoja->getStyle("A{$f}:C{$f}")->getFont()->setBold(true)->setSize(12);
        $hoja->getStyle("A{$f}:C{$f}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');
        $bordes("A{$f}:C{$f}");

        // 4) Trabajadores cuyo costo en campo no es tarifa × horas asistidas
        if ($diferencias) {
            $f += 2;
            $hoja->setCellValue("A{$f}", 'Banco de horas de riego: horas trabajadas en campo por encima de las pagadas este mes (explica la diferencia)');
            $hoja->getStyle("A{$f}")->getFont()->setBold(true);
            $f++;
            $inicioTabla = $f;
            foreach (['TRABAJADOR', 'HORAS EN CAMPO', 'HORAS PAGADAS', 'COSTO EN CAMPO', 'COSTO PAGADO', 'PROVISIÓN (A PAGAR)'] as $i => $cab) {
                $hoja->setCellValue(chr(65 + $i) . $f, $cab);
            }
            $titulo("A{$f}:F{$f}");
            foreach ($diferencias as $d) {
                $f++;
                $hoja->fromArray([
                    $d['trabajador'], round($d['horas_campo'], 2), round($d['horas_asistidas'], 2),
                    round($d['costo_campo'], 2), round($d['costo_esperado'], 2), round($d['diferencia'], 2),
                ], null, "A{$f}");
            }
            $hoja->getStyle("D{$inicioTabla}:F{$f}")->getNumberFormat()->setFormatCode($soles);
            $bordes("A{$inicioTabla}:F{$f}");
            $f++;
            $hoja->setCellValue("A{$f}", 'Las horas de riego que pasan el jornal van al banco de horas y se pagan otro día (uso de horas acumuladas). Positivo = trabajadas y aún no pagadas; negativo = pagadas este mes por horas trabajadas antes.');
            $hoja->getStyle("A{$f}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('7F7F7F');
        }

        $hoja->getStyle("C4:C{$filaTotalCosto}")->getNumberFormat()->setFormatCode($soles);
        $hoja->getStyle("C" . ($filaTotalCosto + 2))->getNumberFormat()->setFormatCode($soles);
        $hoja->getColumnDimension('A')->setWidth(62);
        foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
            $hoja->getColumnDimension($col)->setWidth(18);
        }
    }

    /**
     * Crea o actualiza el registro en la tabla costos_mensuales para el año y mes dados.
     *
     * @param int $anio
     * @param int $mes
     * @param array $totalesCalculados
     * @param string|null $reporteFilePath null = conservar el archivo actual
     * @return CostoMensual
     */
    public function guardarOActualizarCostoMensual(
        int $anio,
        int $mes,
        array $totalesCalculados,
        ?string $reporteFilePath
    ): CostoMensual {
        $datos = array_merge($totalesCalculados, [
            'estado' => 'consolidado',
            'calculado_en' => now(),
            'calculado_por' => auth()->id(),
        ]);
        if ($reporteFilePath !== null) {
            $datos['reporte_file'] = $reporteFilePath;
        }

        return CostoMensual::updateOrCreate(
            [
                'anio' => $anio,
                'mes' => $mes,
            ],
            $datos
        );
    }
}