<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
class ExcelHelper
{
    /**
     * Carga un archivo Excel y extrae datos de tablas específicas.
     * * @param mixed $archivo El archivo desde Livewire (TemporaryUploadedFile)
     * @param array $configHojas Array asociativo ['NombreHoja' => 'NombreTablaExcel']
     * @return array Datos indexados por [hoja][fila][columna]
     * @throws \Exception
     */
    public static function cargarData($archivo, array $configHojas): array
    {
        $path = $archivo->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $dataExtraida = [];

        foreach ($configHojas as $nombreHoja => $nombreTabla) {
            $hoja = $spreadsheet->getSheetByName($nombreHoja);

            if (!$hoja) {
                throw new Exception("No se encontró la pestaña: {$nombreHoja}");
            }

            // Buscamos el objeto Tabla dentro de la hoja
            $tablaExcel = null;
            foreach ($hoja->getTableCollection() as $table) {
                if ($table->getName() === $nombreTabla) {
                    $tablaExcel = $table;
                    break;
                }
            }

            if (!$tablaExcel) {
                throw new Exception("No se encontró la tabla '{$nombreTabla}' en la pestaña '{$nombreHoja}'");
            }

            $dataExtraida[$nombreHoja] = self::convertirTablaAArray($hoja, $tablaExcel);
        }

        return $dataExtraida;
    }

    /**
     * Convierte el rango de una tabla en un array asociativo usando los encabezados.
     */
    private static function convertirTablaAArray(Worksheet $hoja, Table $tabla): array
    {
        $rango = $tabla->getRange(); // Ejemplo: "A1:E10"
        $filas = $hoja->rangeToArray($rango, null, true, false, true);

        if (empty($filas)) return [];

        // El primer elemento son los encabezados
        $encabezados = array_shift($filas);
        $resultado = [];

        foreach ($filas as $fila) {
            $filaAsociativa = [];
            foreach ($encabezados as $columna => $nombreColumna) {
                // Indexamos el valor de la celda con el nombre de la columna
                $filaAsociativa[mb_strtolower($nombreColumna)] = $fila[$columna] ?? null;
            }
            $resultado[] = $filaAsociativa;
        }

        return $resultado;
    }
    public static function parseFechaExcel($valor, $fila = null): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            // Número serial Excel
            if (is_numeric($valor)) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject($valor)
                )->format('Y-m-d');
            }

            // Texto
            $valor = trim($valor);
            $valor = str_replace(['.', '/', '\\'], '-', $valor);

            return Carbon::parse($valor)->format('Y-m-d');

        } catch (\Throwable $e) {
            $msg = "Error al interpretar la fecha '{$valor}'";
            if ($fila !== null) {
                $msg .= " en la fila #{$fila}";
            }
            throw new Exception($msg . ': ' . $e->getMessage());
        }
    }
    public static function descargar($spreadsheet, $filename)
    {
        return new StreamedResponse(function () use ($spreadsheet) {

            $writer = new Xlsx($spreadsheet);

            // salida directa
            $writer->save('php://output');

        }, 200, [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Cache-Control" => "max-age=0, no-cache, no-store, must-revalidate",
        ]);
    }
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
