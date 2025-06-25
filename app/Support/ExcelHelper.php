<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\XAxis;
use PhpOffice\PhpSpreadsheet\Chart\YAxis;
use Exception;

class ExcelHelper
{
    /**
     * Carga un archivo Excel y devuelve la hoja solicitada.
     *
     * @param string $disk Nombre del disco de almacenamiento (ej. 'public')
     * @param string $filePath Ruta del archivo dentro del disco
     * @param string $sheetName Nombre de la hoja a obtener
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     * @throws Exception
     */
    public static function cargarHoja(string $disk, string $filePath, string $sheetName)
    {
        try {
            $fullPath = Storage::disk($disk)->path($filePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (!$sheet) {
                throw new Exception("No se encontró la hoja: {$sheetName}");
            }

            return $sheet;
        } catch (Exception $e) {
            throw new Exception("Error al cargar el archivo Excel: " . $e->getMessage());
        }
    }
    /**
     * Carga una plantilla Excel desde la carpeta 'templates' en public.
     *
     * @param string $fileName Nombre del archivo en la carpeta templates
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     * @throws Exception
     */
    public static function cargarPlantilla(string $fileName)
    {
        try {
            $filePath = public_path("templates/{$fileName}");

            if (!file_exists($filePath)) {
                throw new Exception("La plantilla '{$fileName}' no existe en la carpeta templates.");
            }

            return IOFactory::load($filePath);
        } catch (Exception $e) {
            throw new Exception("Error al cargar la plantilla: " . $e->getMessage());
        }
    }
    /**
     * Obtiene la primera fila de una tabla dada su instancia.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Table $table
     * @param int $defaultValue Valor predeterminado en caso de error (por defecto 1)
     * @return int
     */
    public static function primeraFila($table, int $defaultValue = 1): int
    {
        try {
            $range = $table->getRange(); // Ejemplo: "B4:F22" o "AX34:AZ11"
            if (preg_match('/[A-Z]+(\d+):[A-Z]+\d+/', $range, $matches)) {
                return (int) $matches[1]; // Extrae el número de fila inicial
            }
        } catch (Exception $e) {
            // Loguear el error si es necesario
        }

        return $defaultValue;
    }
    /**
     * Actualiza el rango de una tabla en una hoja de cálculo después de insertar nuevas filas.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Table $table Tabla a actualizar.
     * @param int $filaFinal Última fila utilizada en la tabla.
     * @throws \Exception Si ocurre un error al obtener o actualizar el rango.
     */
    public static function actualizarRangoTabla($table, int $filaFinal)
    {
        try {
            // Obtener el rango actual de la tabla, ejemplo: "B4:F22"
            $rangoActual = $table->getRange();

            // Extraer las coordenadas del rango (columna inicial, fila inicial, columna final, fila final)
            preg_match('/([A-Z]+)(\d+):([A-Z]+)(\d+)/', $rangoActual, $matches);

            // Validar que el rango sea correcto
            if (count($matches) !== 5) {
                throw new Exception("Formato de rango inválido: $rangoActual");
            }

            $colInicio = $matches[1];  // Columna inicial (ejemplo: "B")
            $filaInicio = (int) $matches[2];  // Fila inicial (ejemplo: 4)
            $colFin = $matches[3];  // Columna final (ejemplo: "F")

            // Construir el nuevo rango con la última fila utilizada
            $nuevoRango = "{$colInicio}{$filaInicio}:{$colFin}{$filaFinal}";

            // Asignar el nuevo rango a la tabla
            $table->setRange($nuevoRango);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar el rango de la tabla: " . $e->getMessage());
        }
    }
    /**
     * Parsea una fecha desde Excel en distintos formatos (número serial o string).
     *
     * @param mixed $valor  El valor de la celda de fecha (puede ser numérico o string).
     * @param int|string $fila  Número de fila (solo para el mensaje de error).
     * @return string|null  Fecha en formato 'Y-m-d' o null si está vacía.
     * @throws Exception si no se puede interpretar la fecha.
     */
    public static function parseFecha($valor, $fila)
    {
        if (empty($valor)) {
            return null;
        }

        try {
            // Si es numérico, se asume número serial de Excel
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d');
            }

            // Si es string, limpia separadores no estándar y parsea
            $valor = str_replace(['.', '/', '\\'], '-', trim($valor));
            return Carbon::parse($valor)->format('Y-m-d');
        } catch (Exception $e) {
            throw new Exception("Error al interpretar la fecha '{$valor}' en la fila #{$fila}: " . $e->getMessage());
        }
    }

}
