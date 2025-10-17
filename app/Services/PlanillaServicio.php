<?php

namespace App\Services;

use App\Models\CampoCampania;
use App\Models\PlanEmpleado;
use App\Models\PlanillaBlanco;
use App\Models\PlanillaBlancoDetalle;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanSueldo;
use App\Models\ReporteCostoPlanilla;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioCampos;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlanillaServicio
{
    public function guardarSueldosMasivos($cambios, $mesVigencia, $anioVigencia)
    {
        
        if (empty($cambios)) {
            throw new Exception('No se proporcionaron cambios para procesar.');
        }

        if (!$mesVigencia || !$anioVigencia) {
            throw new Exception('Debe seleccionar el mes y el año de vigencia.');
        }
        $fechaInicio = Carbon::create($anioVigencia, $mesVigencia, 1)->startOfDay();

        // 🔍 Obtener todos los IDs de empleados que vienen en los cambios
        $empleadoIds = collect($cambios)->pluck('empleado_id')->toArray();

        // ⚠️ Validar que ninguno tenga un sueldo con fecha >= $fechaInicio
        $conflictos = PlanSueldo::whereIn('plan_empleado_id', $empleadoIds)
            ->where('fecha_inicio', '>=', $fechaInicio)
            ->pluck('plan_empleado_id')
            ->unique()
            ->toArray();

        if (!empty($conflictos)) {
            $nombres = PlanEmpleado::whereIn('id', $conflictos)
                ->pluck('nombres')
                ->implode(', ');

            throw new Exception("Los siguientes empleados ya tienen sueldos vigentes desde {$fechaInicio->format('d/m/Y')}: {$nombres}");
        }

        foreach ($cambios as $cambio) {
       
            $empleado = PlanEmpleado::findOrFail($cambio['empleado_id']);
            $nuevoSueldo = $cambio['nuevo_sueldo'];
            $ultimoSueldo = $empleado->ultimoSueldo;

            if ($ultimoSueldo) {
                $this->_finalizarSueldo($ultimoSueldo, $fechaInicio);
            }

            PlanSueldo::create([
                'plan_empleado_id'   => $empleado->id,
                'sueldo'        => $nuevoSueldo,
                'fecha_inicio'  => $fechaInicio,
            ]);
        }
    }
    private function _finalizarSueldo($ultimoSueldo, Carbon $fechaInicioSueldo): void
    {
        $ultimoSueldo->update([
            'fecha_fin' => $fechaInicioSueldo->copy()->subDay()->format('Y-m-d')
        ]);
    }
    public static function procesarExcelPlanillaDetalle($planillaBlanco)
    {
        if (!$planillaBlanco) {
            return;
        }
        if (!$planillaBlanco->excel) {
            return;
        }

        $fullPath = Storage::disk('public')->path($planillaBlanco->excel);
        $spreadsheet = IOFactory::load($fullPath);

        $sheet = $spreadsheet->getSheetByName('PLANILLA');
        if (!$sheet) {
            throw new Exception("El Excel no tiene una hoja llamada 'PLANILLA'");
        }
        $rows = $sheet->toArray();

        $indiceInicio = 6;
        $orden = 0;

        for ($i = $indiceInicio; $i < count($rows); $i++) {
            $orden++;
            $fila = $rows[$i];
            $documento = $fila[1];
            $nombres = $fila[2];
            $spp_snp = $fila[4];
            $remuneracion_basica = (float) str_replace(',', '', $fila[5]);
            $asignacion_familiar = (float) str_replace(',', '', $fila[7]);
            $compensacion_vacacional = (float) str_replace(',', '', $fila[8]);
            $sueldo_bruto = (float) str_replace(',', '', $fila[9]);
            $dscto_afp_seguro = (float) str_replace(',', '', $fila[10]);
            $cts = (float) str_replace(',', '', $fila[11]);
            $gratificaciones = (float) str_replace(',', '', $fila[12]);
            $essalud_gratificaciones = (float) str_replace(',', '', $fila[13]);
            $beta_30 = (float) str_replace(',', '', $fila[14]);

            $essalud = (float) str_replace(',', '', $fila[15]);
            $vida_ley = (float) str_replace(',', '', $fila[16]);
            $pension_sctr = (float) str_replace(',', '', $fila[17]);
            $essalud_eps = (float) str_replace(',', '', $fila[18]);
            $sueldo_neto = (float) str_replace(',', '', $fila[19]);
            $rem_basica_asg_fam_essalud_cts_grat_beta = (float) str_replace(',', '', $fila[21]);
            $jornal_diario = (float) str_replace(',', '', $fila[22]);
            $costo_hora = (float) str_replace(',', '', $fila[23]);

            $negro_diferencia_bonificacion = (float) str_replace(',', '', $fila[28]);
            $negro_sueldo_neto_total = (float) str_replace(',', '', $fila[29]);
            $negro_sueldo_bruto = (float) str_replace(',', '', $fila[30]);
            $negro_sueldo_por_dia = (float) str_replace(',', '', $fila[31]);
            $negro_sueldo_por_dia_total = (float) str_replace(',', '', $fila[32]);
            $negro_sueldo_por_hora = (float) str_replace(',', '', $fila[33]);
            $negro_sueldo_por_hora_total = (float) str_replace(',', '', $fila[34]);
            $negro_otros_bonos_acumulados = (float) str_replace(',', '', $fila[35]);
            $negro_sueldo_final_empleado = (float) str_replace(',', '', $fila[36]);
            $negro_diferencia_por_hora = (float) str_replace(',', '', $fila[38]);
            $negro_diferencia_real = (float) str_replace(',', '', $fila[39]);
            $esta_jubilado = $fila[40];

            if (!$documento) {
                continue;
            }

            PlanMensualDetalle::updateOrCreate(
                [
                    'plan_mensual_id' => $planillaBlanco->id,
                    'documento' => $documento
                ],
                [
                    'nombres' => $nombres,
                    'spp_snp' => $spp_snp,
                    'remuneracion_basica' => $remuneracion_basica,
                    'asignacion_familiar' => $asignacion_familiar,
                    'compensacion_vacacional' => $compensacion_vacacional,
                    'sueldo_bruto' => $sueldo_bruto,
                    'dscto_afp_seguro' => $dscto_afp_seguro,
                    'cts' => $cts,
                    'gratificaciones' => $gratificaciones,
                    'essalud_gratificaciones' => $essalud_gratificaciones,
                    'beta_30' => $beta_30,
                    'essalud' => $essalud,
                    'vida_ley' => $vida_ley,
                    'pension_sctr' => $pension_sctr,
                    'essalud_eps' => $essalud_eps,
                    'sueldo_neto' => $sueldo_neto,
                    'rem_basica_asg_fam_essalud_cts_grat_beta' => $rem_basica_asg_fam_essalud_cts_grat_beta,
                    'jornal_diario' => $jornal_diario,
                    'costo_hora' => $costo_hora,

                    // Nuevos campos con prefijo 'negro_'
                    'negro_diferencia_bonificacion' => $negro_diferencia_bonificacion,
                    'negro_sueldo_neto_total' => $negro_sueldo_neto_total,
                    'negro_sueldo_bruto' => $negro_sueldo_bruto,
                    'negro_sueldo_por_dia' => $negro_sueldo_por_dia,
                    'negro_sueldo_por_dia_total' => $negro_sueldo_por_dia_total,
                    'negro_sueldo_por_hora' => $negro_sueldo_por_hora,
                    'negro_sueldo_por_hora_total' => $negro_sueldo_por_hora_total,
                    'negro_diferencia_por_hora' => $negro_diferencia_por_hora,
                    'negro_otros_bonos_acumulados' => $negro_otros_bonos_acumulados,
                    'negro_sueldo_final_empleado' => $negro_sueldo_final_empleado,
                    'esta_jubilado' => $esta_jubilado,
                    'negro_diferencia_real' => $negro_diferencia_real,

                    'orden' => $orden
                ]
            );
        }
    }
    public static function obtenerBonosPlanilla($anio, $mes)
    {
        $reporteDiario = PlanRegistroDiario::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        $registros = [];
        foreach ($reporteDiario as $reporte) {
            $registros[$reporte->detalleMensual->documento]['dia_' . Carbon::parse($reporte->fecha)->format('d')] = $reporte->total_bono;
        }
        return $registros;
    }
    public static function calcularGastoPlanilla($campoCampaniaId)
    {
        $campoCampania = CampoCampania::find($campoCampaniaId);
        if (!$campoCampania) {
            throw new Exception("La campaña no existe.");
        }

        $campoCampania->reporteCostoPlanilla()->delete();

        $fechaInicio = $campoCampania->fecha_inicio;
        $fechaFin = $campoCampania->fecha_fin;
        $campo = $campoCampania->campo;

        /**
         * con whereBetween no funciona cuando fechaFin es null, a veces una campaña no tiene fecha final
         * luego obtendriamos en ese rango de fecha un monton de registros, digamos unos 500, pero no todos se van a trabajar
         * al ejecutar whereHas solo obtenemos los registros donde el detalle tenga el campo que queremos calcular los totales
         */
        $query = ReporteDiario::whereDate('fecha', '>=', $fechaInicio);
        if ($fechaFin) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }

        $query->whereHas('detalles', function ($q) use ($campo) {
            $q->where('campo', $campo);
        });

        $reporteDiario = $query->get()->keyBy('id');

        $detalles = $reporteDiario->flatMap(function ($reporte) use ($campo) {
            return $reporte->detalles()->where('campo', $campo)->get();
        });

        /**
         * Ahora necesitamos evaluar cada detalle para ajustar algunos valores tomando en cuenta lo siguiente:
         * cada reporteDiario representa un trabajo por planilla por una fecha esppecifica, y a veces se trabaja 9 horas y se descuenta 1 hora para cuadrar 8 horas
         * entonces si hay un total de 8 horas y se deben considerar 7 el calculo seria 7/8 = 0.875
         * de tal modo que 8 * 0.875 seria 7 y de esa manera obtendriamos el valor real
         * obtener el descuento que se hizo ese dia de la tabla reporte_diario_campos
         */
        $reporteDiarioCamposQuery = ReporteDiarioCampos::whereDate('fecha', '>=', $fechaInicio);
        if ($fechaFin) {
            $reporteDiarioCamposQuery->whereDate('fecha', '<=', $fechaFin);
        }
        $reporteDiarioCampos = $reporteDiarioCamposQuery->pluck('descuento_minutos', 'fecha')->toArray();

        /**
         * Para obtener una lista de precios por hora se reqiuere saber primero si ya hay planilla de ese mes
         * al generar la planilla en PlanillaBlanco se obtiene su detalle en PlanillaBlancoDetralle y ese registro tiene 
         * la propiedad negro_sueldo_por_hora_total, la idea es obtener todas las planillas dentro del rango
         * luego obtener el detalle de cada empleado y luego crear un array por cada dia del mes, para asi sacar un costo dia
         */
        $planillas = self::obtenerPlanillas($fechaInicio, $fechaFin);

        /**
         * Para obtener los bonos debemos considerar el registro de productiviad, guardado en 
         * como se van a recorrer los detalles de reporte diario, pueda que algunos registros en registro de productividad tengan valores, y no sean procesados
         * entonces se hare una lista de productividad, luego, se ira quitando esa lista, luego si al final del foreach aun hay valores se mostrara un throw indicando el error
         * 
         * id
               * labor_valoracion_id
                *labor_id
                *fecha
                *campo
                *created_at
                *updated_at
                *kg_8
                *valor_kg_adicional

                *6
                *3
                *67
                *2024-11-14
                *1
                *2025-02-14 04:22:58
                *2025-02-14 04:22:58
                *35.00
                *2.50
         */
        /*

 */
        $registroBonos = 0;
        $lista = [];
        foreach ($detalles as $detalle) {

            $reporte = $reporteDiario[$detalle->reporte_diario_id];
            $minutosDescontados = $reporteDiarioCampos[$reporte->fecha] ?? 0;
            $horasTotales = Carbon::parse($reporte->total_horas);
            $factor = self::calcularFactor($horasTotales, $minutosDescontados);

            $horaInicio = Carbon::createFromFormat('H:i:s', $detalle->hora_inicio);
            $horaSalida = Carbon::createFromFormat('H:i:s', $detalle->hora_salida);

            $diferenciaEnMinutos = $horaInicio->diffInMinutes($horaSalida);

            $diferenciaEnHoras = sprintf('%02d:%02d', intdiv($diferenciaEnMinutos, 60), $diferenciaEnMinutos % 60);
            $costoHora = $planillas[$reporte->documento][$reporte->fecha] ?? 0;

            $indiceBono = $reporte->fecha . '_' . $reporte->documento . '_' . $detalle->labor . '_' . $detalle->campo;
            $gastoBono = 0;
            if (array_key_exists($indiceBono, $registroBonos)) {
                $gastoBono = (float) $registroBonos[$indiceBono]['bono'];
                unset($registroBonos[$indiceBono]);
            }

            $lista[] = [
                'campos_campanias_id' => $campoCampania->id,
                'fecha' => $reporte->fecha,
                'documento' => $reporte->documento,
                'empleado_nombre' => $reporte->empleado_nombre,
                'campo' => $detalle->campo,
                'labor' => $detalle->labores->nombre_labor . ' (' . $detalle->labores->id . ')',
                'horas_totales' => $horasTotales->format('H:i'),
                'hora_inicio' => $horaInicio->format('H:i'),
                'hora_salida' => $horaSalida->format('H:i'),
                'factor' => $factor,
                'hora_diferencia' => $diferenciaEnHoras,
                'hora_diferencia_entero' => $diferenciaEnMinutos / 60,
                'costo_hora' => $costoHora,
                'gasto' => (($diferenciaEnMinutos / 60) * $factor) * $costoHora,
                'gasto_bono' => $gastoBono,
            ];


        }

        if (count($registroBonos) > 0) {
            throw new Exception('Hay bonos que no estan registrados en el reporte diario pero si en registro de productividad');
        }

        ReporteCostoPlanilla::insert($lista);

        $filePath = self::procesarExcelGastoPlanilla($campoCampania->id);

        $campoCampania->update([
            'gasto_planilla_file' => $filePath
        ]);

        $campoCampania2 = CampoCampania::find($campoCampaniaId);
        return $campoCampania2->reporteCostoPlanilla->sum(function ($reporte) {
            return $reporte->gasto + $reporte->gasto_bono;
        });
    }

    public static function procesarExcelGastoPlanilla($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            throw new Exception("La Campaña no Existe");
        }

        $spreadsheet = IOFactory::load(public_path('templates/reporte_gasto_planilla.xlsx'));
        $sheet = $spreadsheet->getSheetByName('GASTO PLANILLA');

        if (!$sheet) {
            throw new Exception("No se ha configurado un formato para generar el gasto de planilla");
        }

        $informacion = ReporteCostoPlanilla::where('campos_campanias_id', $campania->id)
            ->with('campania')
            ->orderBy('fecha')
            ->get();

        // Determinar la última fila con datos en la tabla existente
        $highestRow = $sheet->getHighestDataRow(); // Última fila con datos
        $fila = $highestRow; // Insertar después de la última fila con datos
        $index = $fila - 1; // Ajustar el índice de la orden

        foreach ($informacion as $reporte) {
            $sheet->setCellValue("A{$fila}", $index);
            $sheet->setCellValue("B{$fila}", $reporte->campania->nombre_campania);
            $sheet->setCellValue("C{$fila}", $reporte->fecha);
            $sheet->setCellValue("D{$fila}", $reporte->documento);
            $sheet->setCellValue("E{$fila}", $reporte->empleado_nombre);
            $sheet->setCellValue("F{$fila}", $reporte->labor);
            $sheet->setCellValue("G{$fila}", $reporte->campo);
            $sheet->setCellValue("H{$fila}", $reporte->horas_totales);
            $sheet->setCellValue("I{$fila}", $reporte->hora_inicio);
            $sheet->setCellValue("J{$fila}", $reporte->hora_salida);
            $sheet->setCellValue("K{$fila}", $reporte->hora_diferencia_entero);
            $sheet->setCellValue("L{$fila}", $reporte->costo_hora);
            $sheet->setCellValue("M{$fila}", $reporte->gasto);
            $sheet->setCellValue("N{$fila}", $reporte->gasto_bono);
            $sheet->setCellValue("O{$fila}", "=M{$fila}+N{$fila}");

            $fila++;
            $index++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTALES');
        $sheet->setCellValue("M{$fila}", "=SUM(GASTO_PLANILLA[Gasto])");
        $sheet->setCellValue("N{$fila}", "=SUM(GASTO_PLANILLA[Gasto bono])");
        $sheet->setCellValue("O{$fila}", "=SUM(GASTO_PLANILLA[Gasto total])");

        // Ajustar la tabla para incluir las nuevas filas (si la tabla ya está creada en Excel)
        $tableRange = "A1:O" . ($fila - 1); // Nueva área de la tabla
        $sheet->getTableByName('GASTO_PLANILLA')->setRange($tableRange);

        $folderPath = 'gastos_planilla/' . date('Y-m');
        $fileName = 'REPORTE_GASTO_PLANILLA_' . mb_strtoupper($campania->id) . '_' . $campania->campo . '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        Storage::disk('public')->makeDirectory($folderPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));



        return $filePath;
    }

    public static function obtenerPlanillas($fechaDesde, $fechaHasta = null)
    {
        $fechaDesde = Carbon::parse($fechaDesde);
        $fechaHasta = $fechaHasta != null ? Carbon::parse($fechaHasta) : null;

        $planillas = PlanillaBlanco::where(function ($query) use ($fechaDesde, $fechaHasta) {
            if ($fechaHasta) {
                // Si hay fechaHasta, aplicar el rango completo
                $query->whereBetween('anio', [$fechaDesde->year, $fechaHasta->year])
                    ->where(function ($query) use ($fechaDesde, $fechaHasta) {
                        $query->where(function ($q) use ($fechaDesde) {
                            // Condición para el año de inicio
                            $q->where('anio', $fechaDesde->year)
                                ->where('mes', '>=', $fechaDesde->month);
                        })->orWhere(function ($q) use ($fechaHasta) {
                            // Condición para el año de fin
                            $q->where('anio', $fechaHasta->year)
                                ->where('mes', '<=', $fechaHasta->month);
                        })->orWhere(function ($q) use ($fechaDesde, $fechaHasta) {
                            // Años completos intermedios
                            $q->whereBetween('anio', [$fechaDesde->year + 1, $fechaHasta->year - 1]);
                        });
                    });
            } else {
                // Si no hay fechaHasta, considerar todo desde fechaDesde en adelante
                $query->where(function ($q) use ($fechaDesde) {
                    $q->where('anio', '>', $fechaDesde->year) // Años posteriores al de inicio
                        ->orWhere(function ($q) use ($fechaDesde) {
                            // Mismo año de inicio pero meses mayores o iguales
                            $q->where('anio', $fechaDesde->year)
                                ->where('mes', '>=', $fechaDesde->month);
                        });
                });
            }
        })
            ->whereHas('detalle', function ($q) {
                $q->whereNotNull('negro_sueldo_por_hora_total');
            })
            ->get();

        if (!$planillas) {
            return [];
        }

        $resultado = [];
        foreach ($planillas as $planilla) {
            $detalles = $planilla->detalle;
            foreach ($detalles as $detalle) {
                $fechaInicio = Carbon::createFromDate($planilla->anio, $planilla->mes, 1);
                $fechaFin = Carbon::createFromDate($fechaInicio)->endOfMonth();
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

                foreach ($periodo as $fecha) {

                    if ($detalle->negro_sueldo_por_hora_total) {
                        $resultado[$detalle->documento][$fecha->format('Y-m-d')] = $detalle->negro_sueldo_por_hora_total;
                    }

                }
            }

            //$fechaInicio = Carbon::createFromDate($this->anio,$this->mes,1);
        }

        return $resultado;
    }
    public static function calcularFactor($horasTotales, $minutosDescontados)
    {

        $totales = Carbon::createFromDate($horasTotales)->addMinutes($minutosDescontados);

        $horasTotalesSinDescuento = explode(':', $totales->format('H:i'));
        $horasTotalesConDescuento = explode(':', $horasTotales->format('H:i'));
        $minutosSinDescuento = (int) $horasTotalesSinDescuento[0] * 60 + (int) $horasTotalesSinDescuento[1];
        $minutosConDescuento = (int) $horasTotalesConDescuento[0] * 60 + (int) $horasTotalesConDescuento[1];
        return $minutosConDescuento / $minutosSinDescuento;
    }

}
