<?php

namespace App\Services\FDM;

use App\Models\Actividad;
use App\Models\CuadrilleroActividad;
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
                    'grupo' => $gasto->cuaAsistenciaSemanalGrupo?->grupo?->nombre,
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

        $actividades = CuadrilleroActividad::whereIn('actividad_id', $actividadIds)
            ->with(['actividad', 'cuadrillero', 'labor'])
            ->get()
            ->map(function ($detalle) use ($campo) {
                $horasTotales = (float) $detalle->actividad->horas_trabajadas;
                $totalCosto = (float) $detalle->total_costo;
                $totalBono = (float) $detalle->total_bono;
                $costoHora = ($horasTotales > 0) ? ($totalCosto + $totalBono) / $horasTotales : 0;

                return [
                    'labor' => $detalle->labor->nombre_labor . ' (' . $detalle->labor->id . ')',
                    'fecha' => $detalle->actividad->fecha,
                    'documento' => $detalle->cuadrillero->dni,
                    'empleado_nombre' => $detalle->cuadrillero->nombres,
                    'campo' => $campo,
                    'horas_totales' => $horasTotales,
                    'hora_inicio' => '-',
                    'hora_salida' => '-',
                    'factor' => 1,
                    'hora_diferencia' => '-',
                    'hora_diferencia_entero' => $detalle->actividad->horas_trabajadas,
                    'costo_hora' => $costoHora,
                    'gasto' => $totalCosto,
                    'gasto_bono' => $totalBono,
                ];
            })
            ->toArray();

        $totalActividades = array_sum(array_column($actividades, 'gasto')) + array_sum(array_column($actividades, 'gasto_bono'));

        // Generar Excel con ambas listas
        $filePath = self::procesarExcelGastoCuadrillaFdm($actividades, $gastosAdicionales, $anio, $mes);

        return [
            'total' => $totalActividades + $totalGastosAdicionales,
            'file' => $filePath,
            'detalle' => $actividades,
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
            $sheet->setCellValue("L{$fila}", $reporte['hora_diferencia_entero']);
            $sheet->setCellValue("M{$fila}", $reporte['costo_hora']);
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
