<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\CampoCampania;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuadrillaHora;
use App\Models\CuadrilleroActividad;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CuadrillaServicio
{
    

    /*
    public static function calcularCostoFdmMensual($mes,$anio){
        $registrosDiarios = CuadrillaHora::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        //varios registros, cada campo con costo_dia y bono, sumar cambos campos y sumar cada registro y retornar el tota
        return $registrosDiarios->sum(function ($registro) {
            $costoDia = $registro->costo_dia ?? 0;
            $bono = $registro->bono ?? 0;
            return $costoDia + $bono;
        });
    }*/
    public static function calcularGastoCuadrilla($campoCampaniaId)
    {
        $campoCampania = CampoCampania::find($campoCampaniaId);
        if (!$campoCampania) {
            throw new Exception("La campaña no existe.");
        }

        $query = Actividad::whereDate('fecha', '>=', $campoCampania->fecha_inicio)
            ->where('campo', $campoCampania->campo);

        if ($campoCampania->fecha_fin) {
            $query->whereDate('fecha', '<=', $campoCampania->fecha_fin);
        }

        // Verificamos si existen actividades antes de continuar
        if (!$query->exists()) {
            return 0;
        }

        // Obtener actividades y buscar cuadrillero en la misma consulta
        $cuadrilleroActividades = CuadrilleroActividad::whereIn('actividad_id', $query->pluck('id'))
            ->with(['actividad', 'cuadrillero', 'labor'])
            ->get()
            ->toArray();



        $lista = [];
        $total = 0;
        foreach ($cuadrilleroActividades as $detalle) {

            $horasTotales = (float) $detalle['actividad']['horas_trabajadas'];
            $factor = 1;

            $fecha = $detalle['actividad']['fecha'];
            $nombreCampania = $campoCampania->nombre_campania;
            $documento = $detalle['cuadrillero']['dni'];
            $empleadoNombre = $detalle['cuadrillero']['nombres'];
            $campo = $detalle['actividad']['campo'];
            $horasTrabajadas = $detalle['actividad']['horas_trabajadas'];
            $totalCosto = $detalle['total_costo'];
            $totalBono = $detalle['total_bono'];
            $labor = $detalle['labor']['nombre_labor'] . ' (' . $detalle['labor']['id'] . ')';
            // Evitar división por cero
            $costoHora = ($horasTotales > 0) ? ($totalCosto + $totalBono) / $horasTotales : 0;

            $lista[] = [
                'campos_campanias_id' => $campoCampania->id,
                'nombre_campania' => $nombreCampania,
                'labor' => $labor,
                'fecha' => $fecha,
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
