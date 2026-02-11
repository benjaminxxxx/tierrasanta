<?php

namespace App\Services\FDM;

use App\Models\CuadDetalleHora;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Servicio encargado de la gestión y cálculo de gastos para la cuadrilla FDM.
 */
class CuadrillaFdmServicio
{
    /**
     * Calcula los costos mensuales de la cuadrilla (Jornales + Bonos + Gastos Adicionales)
     * y genera un reporte en Excel.
     *
     * @param int $mes Número del mes (1-12)
     * @param int $anio Año del reporte
     * @return array Resumen de totales, ruta del archivo y detalle procesado.
     */
    public static function calcularGastosCuadrillaMensual(int $mes, int $anio): array
    {
        // 1. OBTENCIÓN DE GASTOS ADICIONALES
        $gastosAdicionales = GastoAdicionalPorGrupoCuadrilla::where('mes_contable', $mes)
            ->where('anio_contable', $anio)
            ->get()
            ->map(fn($gasto, $index) => [
                'orden' => $index + 1,
                'monto' => (float) $gasto->monto,
                'descripcion' => $gasto->descripcion,
                'grupo' => $gasto->grupo?->nombre,
                'fecha_gasto' => formatear_fecha($gasto->fecha_gasto),
                'fecha_contable' => $gasto->fecha_contable,
            ])
            ->toArray();

        $totalGastosAdicionales = array_sum(array_column($gastosAdicionales, 'monto'));

        // 2. PROCESAMIENTO DE REGISTROS DIARIOS Y PRORRATEO
        $registros = CuadDetalleHora::whereHas('registroDiario', function ($q) use ($mes, $anio) {
            $q->whereMonth('fecha', $mes)->whereYear('fecha', $anio);
        })
            ->where('campo_nombre', 'fdm')
            ->with(['registroDiario.actividadesBonos.actividad', 'registroDiario.cuadrillero', 'labores'])
            ->get()
            ->map(function ($detalle) {
                $rd = $detalle->registroDiario;

                // Cálculo de duración del tramo en horas
                $inicio = Carbon::parse($detalle->hora_inicio);
                $fin = Carbon::parse($detalle->hora_fin);
                $horasDetalle = $inicio->diffInMinutes($fin) / 60;

                // Prorrateo del Jornal: (Costo Día / Total Horas Trabajadas) * Horas en FDM
                $costoHoraJornal = $rd->total_horas > 0 ? ($rd->jornal_aplicado / $rd->total_horas) : 0;
                $gastoProrrateado = $costoHoraJornal * $horasDetalle;

                // Cálculo de Bonos específicos del campo FDM
                $gastoBonoFdm = $rd->actividadesBonos
                    ->where('actividad.campo', 'FDM')
                    ->sum('total_bono');

                return [
                    'fecha' => formatear_fecha($rd->fecha),
                    'documento' => $rd->cuadrillero?->dni ?? 'S/D',
                    'empleado_nombre' => $rd->cuadrillero?->nombre_completo,
                    'labor' => $detalle->labores?->nombre_labor ?? $detalle->codigo_labor,
                    'campo' => $detalle->campo_nombre,
                    'horas_totales' => $rd->total_horas,
                    'hora_inicio' => $detalle->hora_inicio,
                    'hora_salida' => $detalle->hora_fin,
                    'total_horas' => $horasDetalle,
                    //'costo_dia' => $rd->jornal_aplicado,
                    'gasto' => round($gastoProrrateado, 2),
                    'gasto_bono' => round($gastoBonoFdm, 2),
                ];
            });

        // 3. GENERACIÓN DEL ARCHIVO EXCEL
        $rutaArchivo = self::generarExcelReporteFdm(
            $registros->toArray(),
            $gastosAdicionales,
            $anio,
            $mes
        );

        // 4. CÁLCULO DE TOTALES FINALES
        $totalActividades = $registros->sum(fn($r) => $r['gasto'] + $r['gasto_bono']);
        $totalBono = $registros->sum('gasto_bono');

        return [
            'total' => $totalActividades + $totalGastosAdicionales,
            'bono' => $totalBono,
            'file' => $rutaArchivo,
            'detalle' => $registros,
        ];
    }

    /**
     * Crea y guarda el archivo Excel basado en la plantilla de gastos FDM.
     *
     * @param array $actividades Datos de los trabajadores y prorrateos
     * @param array $gastosAdicionales Datos de gastos extras por grupo
     * @param int $anio
     * @param int $mes
     * @return string Ruta relativa del archivo guardado
     * @throws Exception
     */
    public static function generarExcelReporteFdm(array $actividades, array $gastosAdicionales, int $anio, int $mes): string
    {
        $hojasRequeridas = ['GASTO_CUADRILLA_FDM', 'GASTO_CUADRILLA_FDM_ADICIONAL'];
        $hojas = ExcelHelper::cargarHojasDesdePlantilla('reporte_gasto_cuadrilla_fdm.xlsx', $hojasRequeridas);

        ['GASTO_CUADRILLA_FDM' => $sheet, 'GASTO_CUADRILLA_FDM_ADICIONAL' => $sheetAdicional] = $hojas;

        // --- Procesamiento Hoja Principal (Actividades) ---
        $fila = $sheet->getHighestDataRow();
        $index = $fila - 1;



        foreach ($actividades as $reporte) {

            $horaInicio = Carbon::parse($reporte['hora_inicio'])->format('H:i');
            $horaSalida = Carbon::parse($reporte['hora_salida'])->format('H:i');

            $sheet->setCellValue("A{$fila}", $index);
            $sheet->setCellValue("B{$fila}", $anio);
            $sheet->setCellValue("C{$fila}", $mes);
            $sheet->setCellValue("D{$fila}", $reporte['fecha']);
            $sheet->setCellValue("E{$fila}", $reporte['documento']);
            $sheet->setCellValue("F{$fila}", $reporte['empleado_nombre']);
            $sheet->setCellValue("G{$fila}", $reporte['labor']);
            $sheet->setCellValue("H{$fila}", $reporte['campo']);
            $sheet->setCellValue("I{$fila}", $reporte['horas_totales']);
            $sheet->setCellValue("J{$fila}", $horaInicio);
            $sheet->setCellValue("K{$fila}", $horaSalida);
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

        // --- Procesamiento Hoja Gastos Adicionales ---
        $filaAdicional = max(2, $sheetAdicional->getHighestDataRow() + 1);
        foreach ($gastosAdicionales as $gasto) {
            $sheetAdicional->setCellValue("A{$filaAdicional}", $gasto['orden']);
            $sheetAdicional->setCellValue("B{$filaAdicional}", $gasto['grupo']);
            $sheetAdicional->setCellValue("C{$filaAdicional}", $gasto['descripcion']);
            $sheetAdicional->setCellValue("D{$filaAdicional}", $gasto['monto']);
            $sheetAdicional->setCellValue("E{$filaAdicional}", $gasto['fecha_gasto']);
            $sheetAdicional->setCellValue("F{$filaAdicional}", $gasto['fecha_contable']);
            $filaAdicional++;
        }

        $sheetAdicional->setCellValue("A{$filaAdicional}", 'TOTALES');
        $sheetAdicional->setCellValue("D{$filaAdicional}", "=SUM(GASTO_CUADRILLA_FDM_ADICIONAL[Monto])");
        $sheetAdicional->getTableByName('GASTO_CUADRILLA_FDM_ADICIONAL')->setRange("A1:F" . ($filaAdicional - 1));

        // --- Guardado del Archivo ---
        $folderPath = "gastos_cuadrilla_fdm/{$anio}";
        $fileName = "REPORTE_GASTO_CUADRILLA_FDM_{$anio}_{$mes}.xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        Storage::disk('public')->makeDirectory($folderPath);

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $spreadsheet = $sheet->getParent();
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
}