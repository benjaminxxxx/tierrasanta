<?php

namespace App\Services\FDM;

use App\Models\Actividad;
use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadRegistroDiario;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CuadrillaFdmServicio
{
    public static function generarReportePorMes($mes, $anio)
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
        $campo = 'fdm';

        // GASTOS ADICIONALES
        $gastosAdicionales = GastoAdicionalPorGrupoCuadrilla::where('mes_contable', $mes)
            ->where('anio_contable', $anio)
            ->get()
            ->map(function ($gasto, $index) {
                return [
                    'orden' => $index + 1,
                    'monto' => (float) $gasto->monto,
                    'descripcion' => $gasto->descripcion,
                    'grupo' => $gasto->grupo?->nombre,
                    'fecha_gasto' => formatear_fecha($gasto->fecha_gasto),
                    'fecha_contable' => $gasto->fecha_contable,
                ];
            })
            ->toArray();

        $totalGastosAdicionales = array_sum(array_column($gastosAdicionales, 'monto'));

        // ACTIVIDADES
        $actividadIds = Actividad::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('campo', $campo)
            ->pluck('id');


        $registrosDiarios = CuadRegistroDiario::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->with(['detalleHoras', 'cuadrillero'])
            ->get();

        // Obtener combinaciones únicas de (fecha, cuadrillero_id)
        $fechas = $registrosDiarios->pluck('fecha')->unique();
        $cuadrilleroIds = $registrosDiarios->pluck('cuadrillero_id')->unique();

        // 1. Cargar grupos por lote
        $grupos = CuadGrupoCuadrilleroFecha::whereIn('fecha', $fechas)
            ->whereIn('cuadrillero_id', $cuadrilleroIds)
            ->get()
            ->keyBy(fn($g) => $g->fecha . '-' . $g->cuadrillero_id);

        // 2. Obtener todos los códigos de grupo
        $codigosGrupo = $grupos->pluck('codigo_grupo')->unique();

        // 3. Cargar costos por grupo por lote
        $costoGrupos = CuadCostoDiarioGrupo::whereIn('fecha', $fechas)
            ->whereIn('codigo_grupo', $codigosGrupo)
            ->get()
            ->keyBy(fn($c) => $c->fecha . '-' . $c->codigo_grupo);

        // 4. Mapear los registros diarios con la lógica optimizada
        $registrosDiarios = $registrosDiarios->flatMap(function ($registroDiario) use ($grupos, $costoGrupos) {
            $totalHorasValidado = $registroDiario->total_horas_validado;
            $fecha = $registroDiario->fecha;
            $cuadrilleroId = $registroDiario->cuadrillero_id;
            $detalleHoras = $registroDiario->detalleHoras;

            // Determinar el costo del día
            $costoDia = $registroDiario->costo_personalizado_dia ?? 0;
            if (!$costoDia) {
                $grupo = $grupos->get($fecha->format('Y-m-d') . '-' . $cuadrilleroId);
                if ($grupo) {
                    $codigoGrupo = $grupo->codigo_grupo;
                    $costo = $costoGrupos->get($fecha->format('Y-m-d') . '-' . $codigoGrupo);
                    if ($costo) {
                        $costoDia = $costo->jornal;
                    }
                }
            }

            // Si tiene detalle y está validado, se hace el desglose proporcional
            if ($totalHorasValidado && $detalleHoras->count() > 0 && $registroDiario->total_horas > 0) {
                return $detalleHoras->sortBy('hora_inicio')->map(function ($detalleHora) use ($registroDiario, $costoDia) {
                    $horaInicio = \Carbon\Carbon::parse($detalleHora->hora_inicio);
                    $horaFin = \Carbon\Carbon::parse($detalleHora->hora_fin);

                    $minutos = $horaInicio->diffInMinutes($horaFin);
                    $horasDecimal = round($minutos / 60, 2);

                    // Calcular proporción de gasto
                    $gasto = round(($horasDecimal / $registroDiario->total_horas) * $costoDia, 2);

                    return [
                        'labor' => $detalleHora->codigo_labor,
                        'fecha' => $registroDiario->fecha->format('Y-m-d'),
                        'documento' => $registroDiario->cuadrillero->dni,
                        'empleado_nombre' => $registroDiario->cuadrillero->nombres,
                        'campo' => $detalleHora->campo_nombre,
                        'horas_totales' => $registroDiario->total_horas,
                        'hora_inicio' => $horaInicio->format('H:i'),
                        'hora_salida' => $horaFin->format('H:i'),
                        'factor' => 1,
                        'hora_diferencia' => $minutos . ' min',
                        'hora_diferencia_entero' => $horasDecimal,
                        'costo_dia' => $costoDia,
                        'total_horas' => $registroDiario->total_horas,
                        'gasto' => $gasto,
                        'gasto_bono' => $detalleHora->costo_bono ?? 0,
                    ];
                });
            }

            // Caso sin detalle válido
            return [
                [
                    'labor' => 'SIN REGISTRO',
                    'fecha' => $registroDiario->fecha->format('Y-m-d'),
                    'documento' => $registroDiario->cuadrillero->dni,
                    'empleado_nombre' => $registroDiario->cuadrillero->nombres,
                    'campo' => 'SIN REGISTRO',
                    'horas_totales' => $registroDiario->total_horas,
                    'hora_inicio' => '-',
                    'hora_salida' => '-',
                    'factor' => 1,
                    'hora_diferencia' => '-',
                    'hora_diferencia_entero' => '-',
                    'costo_dia' => $costoDia,
                    'total_horas' => $registroDiario->total_horas,
                    'gasto' => $registroDiario->costo_dia,
                    'gasto_bono' => $registroDiario->total_bono ?? 0,
                ]
            ];
        });
        
        // Generar Excel con ambas listas
        $filePath = self::procesarExcelGastoCuadrillaFdm($registrosDiarios->ToArray(), $gastosAdicionales, $anio, $mes);
        $totalActividades = $registrosDiarios->sum(function ($registroDiario){
            return $registroDiario['gasto'] + $registroDiario['gasto_bono'];
        });
        $totalBono = $registrosDiarios->sum('gasto_bono');
        
        return [
            'total' => $totalActividades + $totalGastosAdicionales,
            'bono'=>$totalBono,
            'file' => $filePath,
            'detalle' => $registrosDiarios,
        ];
    }


    public static function procesarExcelGastoCuadrillaFdm(array $actividades, array $gastosAdicionales, int $anio, int $mes)
    {
        $spreadsheet = ExcelHelper::cargarPlantilla('reporte_gasto_cuadrilla_fdm.xlsx');

        // ========= Hoja principal: GASTO_CUADRILLA_FDM =========
        $sheet = $spreadsheet->getSheetByName('GASTO_CUADRILLA_FDM');
        if (!$sheet)
            throw new Exception("No se ha configurado la hoja GASTO_CUADRILLA_FDM");

        $fila = $sheet->getHighestDataRow();
        $index = $fila - 1;

        foreach ($actividades as $reporte) {
            $sheet->setCellValue("A{$fila}", $index);
            $sheet->setCellValue("B{$fila}", $anio);
            $sheet->setCellValue("C{$fila}", $mes);
            $sheet->setCellValue("D{$fila}", $reporte['fecha']);
            $sheet->setCellValue("E{$fila}", $reporte['documento']);
            $sheet->setCellValue("F{$fila}", $reporte['empleado_nombre']);
            $sheet->setCellValue("G{$fila}", $reporte['labor']);
            $sheet->setCellValue("H{$fila}", $reporte['campo']);
            $sheet->setCellValue("I{$fila}", $reporte['horas_totales']);
            $sheet->setCellValue("J{$fila}", $reporte['hora_inicio']);
            $sheet->setCellValue("K{$fila}", $reporte['hora_salida']);
            $sheet->setCellValue("L{$fila}", $reporte['total_horas']);
            $sheet->setCellValue("M{$fila}", $reporte['costo_dia']);
            $sheet->setCellValue("N{$fila}", $reporte['gasto']);
            $sheet->setCellValue("O{$fila}", $reporte['gasto_bono']);
            $sheet->setCellValue("P{$fila}", "=N{$fila}+O{$fila}");
            $fila++;
            $index++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTALES');
        $sheet->setCellValue("N{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto])");
        $sheet->setCellValue("O{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto bono])");
        $sheet->setCellValue("P{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto total])");

        $sheet->getTableByName('GASTO_CUADRILLA_FDM')->setRange("A1:P" . ($fila - 1));


        // ========= Hoja adicional: GASTO_CUADRILLA_FDM_ADICIONAL =========
        $sheetAdicional = $spreadsheet->getSheetByName('GASTO_CUADRILLA_FDM_ADICIONAL');
        if (!$sheetAdicional)
            throw new Exception("No se ha configurado la hoja GASTO_CUADRILLA_FDM_ADICIONAL");

        $fila = max(2, $sheetAdicional->getHighestDataRow() + 1);
        foreach ($gastosAdicionales as $gasto) {

            $sheetAdicional->setCellValue("A{$fila}", $gasto['orden']); // N°
            $sheetAdicional->setCellValue("B{$fila}", $gasto['grupo']);
            $sheetAdicional->setCellValue("C{$fila}", $gasto['descripcion']);
            $sheetAdicional->setCellValue("D{$fila}", $gasto['monto']);
            $sheetAdicional->setCellValue("E{$fila}", $gasto['fecha_gasto']);
            $sheetAdicional->setCellValue("F{$fila}", $gasto['fecha_contable']);
            $fila++;
        }

        $sheetAdicional->setCellValue("A{$fila}", 'TOTALES');
        $sheetAdicional->setCellValue("D{$fila}", "=SUM(GASTO_CUADRILLA_FDM_ADICIONAL[Monto])");

        $sheetAdicional->getTableByName('GASTO_CUADRILLA_FDM_ADICIONAL')->setRange("A1:F" . ($fila - 1));

        // ========= Guardar archivo =========
        $folderPath = 'gastos_cuadrilla_fdm/' . $anio;
        $fileName = 'REPORTE_GASTO_CUADRILLA_FDM_' . $anio . '_' . $mes . '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        Storage::disk('public')->makeDirectory($folderPath);
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }

}
