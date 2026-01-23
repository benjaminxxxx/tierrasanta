<?php

namespace App\Services\Reportes;

use App\Models\PlanEmpleado;
use App\Support\ExcelHelper;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RptPlanillaGeneral
{
    public function descargarPlanillaActualizada()
    {
        $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_planilla_general.xlsx');
        $hoja = $spreadsheet->getSheetByName('EMPLEADOS');

        if (!$hoja) {
            throw new Exception("La plantilla no contiene la hoja 'EMPLEADOS'.");
        }

        $table = $hoja->getTableByName('tblEmpleados');
        if (!$table) {
            throw new Exception("La plantilla no tiene una tabla llamada tblEmpleados.");
        }

        // 1. Obtener los datos ordenados
        $planilla = PlanEmpleado::orderBy('apellido_paterno', 'asc')
        ->orderBy('apellido_materno', 'asc')
        ->orderBy('nombres', 'asc')
        ->get();

        // 2. Determinar la fila de inicio
        $rangoOriginal = $table->getRange(); // E.g., "A1:N2"
        $partesRango = explode(':', $rangoOriginal);
        $celdaInicio = $partesRango[0]; // A1
        $filaInicio = (int) filter_var($celdaInicio, FILTER_SANITIZE_NUMBER_INT) + 1;

        $filaActual = $filaInicio;
        $contador = 1;

        foreach ($planilla as $emp) {
            // A: N°
            $hoja->setCellValue("A{$filaActual}", $contador);
            
            // B: ID (Formato PL00001)
            $idFormateado = 'PL' . str_pad($emp->id, 5, '0', STR_PAD_LEFT);
            $hoja->setCellValue("B{$filaActual}", $idFormateado);

            // C: APELLIDO_PATERNO
            $hoja->setCellValue("C{$filaActual}", mb_strtoupper($emp->apellido_paterno));
            
            // D: APELLIDO_MATERNO
            $hoja->setCellValue("D{$filaActual}", mb_strtoupper($emp->apellido_materno));
            
            // E: NOMBRES
            $hoja->setCellValue("E{$filaActual}", mb_strtoupper($emp->nombres));
            
            // F: DOCUMENTO
            $hoja->setCellValue("F{$filaActual}", $emp->documento);

            // G: FECHA_INGRESO (Date Serial)
            if ($emp->fecha_ingreso) {
                $hoja->setCellValue("G{$filaActual}", ExcelDate::PHPToExcel($emp->fecha_ingreso));
                $hoja->getStyle("G{$filaActual}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }

            // H: EMAIL
            $hoja->setCellValue("H{$filaActual}", $emp->email);
            
            // I: NUMERO
            $hoja->setCellValue("I{$filaActual}", $emp->numero);

            // J: FECHA_NACIMIENTO (Date Serial)
            if ($emp->fecha_nacimiento) {
                $hoja->setCellValue("J{$filaActual}", ExcelDate::PHPToExcel($emp->fecha_nacimiento));
                $hoja->getStyle("J{$filaActual}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }

            // K: DIRECCION
            $hoja->setCellValue("K{$filaActual}", $emp->direccion);
            
            // L: GENERO
            $hoja->setCellValue("L{$filaActual}", $emp->genero);
            
            // M: ORDEN
            $hoja->setCellValue("M{$filaActual}", $emp->orden);
            
            // N: COMENTARIOS
            $hoja->setCellValue("N{$filaActual}", $emp->comentarios);

            $filaActual++;
            $contador++;
        }

        // 3. Actualizar el rango de la tabla (Ahora hasta la columna N)
        $nuevaFilaFin = ($filaActual > $filaInicio) ? $filaActual - 1 : $filaInicio;
        $table->setRange("{$celdaInicio}:N{$nuevaFilaFin}");

        return ExcelHelper::descargar($spreadsheet, 'PLANILLA GENERAL.xlsx');
    }
}