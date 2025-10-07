<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\CampoCampania;
use App\Models\CuadDetalleHora;
use App\Models\CuadrillaHora;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CuadrillaServicio
{
    public static function calcularGastoCuadrilla($campoCampaniaId)
    {
        $campoCampania = CampoCampania::find($campoCampaniaId);
        if (!$campoCampania) {
            throw new Exception("La campaña no existe.");
        }

        $fechaInicio = $campoCampania->fecha_inicio;
        $fechaFin = $campoCampania->fecha_fin??Carbon::now()->format('Y-m-d');

        $actividades = Actividad::whereBetween('fecha', [$fechaInicio,$fechaFin])
            ->where('campo', $campoCampania->campo)
            ->get();

        if(!$actividades){
            return 0;
        }

        $registros = [];

        foreach ($actividades as $actividad) {
            $fecha = $actividad->fecha;
            $campo = $actividad->campo;
            $data = CuadDetalleHora::with(['registroDiario'])
            ->where('campo_nombre',$campo)
            ->whereHas('registroDiario',function ($registroDiario) use ($fecha){
                return $registroDiario->whereDate('fecha',$fecha);
            })
            ->get();
            if($data){
                foreach ($data as $registro) {
                    $registros[] = $registro;
                }
            }
            
            
        }

        $lista = [];
        $total = 0;
        foreach ($registros as $registroData) {

            dd($registroData);
            $factor = 1;
          
            
            $nombreCampania = $campoCampania->nombre_campania;

            $lista[] = [
                'campos_campanias_id' => $campoCampania->id,
                'nombre_campania' => $nombreCampania,
                'labor' => $registroData->codigo_labor,
                'fecha' => $registroData->registroDiario->fecha,
                'documento' => $documento,
                'empleado_nombre' => $empleadoNombre,
                'campo' => $campo,
                'horas_totales' => $horasTotales,
                'hora_inicio' => '-',
                'hora_salida' => '-',
                'factor' => $factor,
                'hora_diferencia' => '-',
                'hora_diferencia_entero' => $horasTrabajadas,
                'costo_hora' => $costoHora,
                'gasto' => $totalCosto,
                'gasto_bono' => $totalBono,
            ];

            $total += $totalCosto + $totalBono;
        }

        $filePath = self::procesarExcelGastoCuadrilla($lista, $campoCampania);

        $campoCampania->update([
            'gasto_cuadrilla_file' => $filePath
        ]);

        return $total;
    }
    
    public static function procesarExcelGastoCuadrilla($lista, $campoCampania)
    {

        $spreadsheet = ExcelHelper::cargarPlantilla('reporte_gasto_cuadrilla.xlsx');
        $sheet = $spreadsheet->getSheetByName('GASTO CUADRILLA');

        if (!$sheet) {
            throw new Exception("No se ha configurado un formato para generar el gasto de cuadrilla");
        }

        $informacion = $lista;

        // Determinar la última fila con datos en la tabla existente
        $highestRow = $sheet->getHighestDataRow(); // Última fila con datos
        $fila = $highestRow; // Insertar después de la última fila con datos
        $index = $fila - 1; // Ajustar el índice de la orden

        foreach ($informacion as $reporte) {
            $sheet->setCellValue("A{$fila}", $index);
            $sheet->setCellValue("B{$fila}", $reporte['nombre_campania']);
            $sheet->setCellValue("C{$fila}", $reporte['fecha']);
            $sheet->setCellValue("D{$fila}", $reporte['documento']);
            $sheet->setCellValue("E{$fila}", $reporte['empleado_nombre']);
            $sheet->setCellValue("F{$fila}", $reporte['labor']);
            $sheet->setCellValue("G{$fila}", $reporte['campo']);
            $sheet->setCellValue("H{$fila}", $reporte['horas_totales']);
            $sheet->setCellValue("I{$fila}", $reporte['hora_inicio']);
            $sheet->setCellValue("J{$fila}", $reporte['hora_salida']);
            $sheet->setCellValue("K{$fila}", $reporte['hora_diferencia_entero']);
            $sheet->setCellValue("L{$fila}", $reporte['costo_hora']);
            $sheet->setCellValue("M{$fila}", $reporte['gasto']);
            $sheet->setCellValue("N{$fila}", $reporte['gasto_bono']);
            $sheet->setCellValue("O{$fila}", "=M{$fila}+N{$fila}");

            $fila++;
            $index++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTALES');
        $sheet->setCellValue("M{$fila}", "=SUM(GASTO_CUADRILLA[Gasto])");
        $sheet->setCellValue("N{$fila}", "=SUM(GASTO_CUADRILLA[Gasto bono])");
        $sheet->setCellValue("O{$fila}", "=SUM(GASTO_CUADRILLA[Gasto total])");

        // Ajustar la tabla para incluir las nuevas filas (si la tabla ya está creada en Excel)
        $tableRange = "A1:O" . ($fila - 1); // Nueva área de la tabla
        $sheet->getTableByName('GASTO_CUADRILLA')->setRange($tableRange);

        $folderPath = 'gastos_cuadrilla/' . date('Y-m');
        $fileName = 'REPORTE_GASTO_CUADRILLA_' . mb_strtoupper($campoCampania->id) . '_' . $campoCampania->campo . '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        Storage::disk('public')->makeDirectory($folderPath);

        // Verificar si el archivo existe y eliminarlo antes de sobrescribir
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }


        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
    public static function cantidadCuadrilleros($fecha)
    {
        return CuadrillaHora::whereDate('fecha', $fecha)->count();
    }
}
