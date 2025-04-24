<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;


class CochinillaIngresoSeeder extends Seeder
{
    public function run(): void
    {
        $path = Storage::disk('public')->path('informacion_general.xlsx');

        if (!file_exists($path)) {
            $this->command->error("No se encontró el archivo: informacion_general.xlsx");
            return;
        }

        // Obtener todas las hojas
        $hojas = Excel::toArray([], $path);

        $hojaIngresos = $hojas[2];

        if (!$hojaIngresos || count($hojaIngresos) < 2) {
            throw new \Exception("La hoja 'SIEMBRAS' no contiene datos suficientes o no existe.");
        }

        $rows = $hojaIngresos;

        $variedadCultivos = [];
        $loteIndex = 0;
        $subloteIndex = 1;
        $fechaIndex = 2;
        $campoIndex = 3;
        $campaniaIndex = 4;
        $cultivoIndex = 5;
        $fechaSiembra = 6;
        $kilo_totalIndex = 7;
        $obsIndex = 8;

        // Obtener todos los nombres de campo desde el Excel (ignorando encabezados)
        $camposDesdeExcel = collect(array_slice($rows, 5))
            ->pluck($campoIndex) // columna de nombres de campos
            ->filter() // eliminar nulos o vacíos
            ->unique()
            ->values();

        // Obtener campos válidos de la base de datos
        $camposEnBD = Campo::whereIn('nombre', $camposDesdeExcel)->pluck('nombre');

        // Verificar si hay campos del Excel que no existen en la BD
        $camposFaltantes = $camposDesdeExcel->diff($camposEnBD);

        if ($camposFaltantes->isNotEmpty()) {
            throw new \Exception("Los siguientes campos no existen en la base de datos: " . $camposFaltantes->join(', '));
        }
        // Obtener los textos de observación desde Excel
        $observacionesDesdeExcel = collect(array_slice($rows, 5))
            ->pluck($obsIndex)
            ->filter()
            ->map(fn($texto) => $this->generarCodigoSlug($texto)) // convierte a código
            ->unique()
            ->values();

        // Obtener códigos válidos desde la BD
        $observacionesEnBD = CochinillaObservacion::whereIn('codigo', $observacionesDesdeExcel)->pluck('codigo');

        // Verificar si hay códigos que no existen
        $observacionesFaltantes = $observacionesDesdeExcel->diff($observacionesEnBD);

        if ($observacionesFaltantes->isNotEmpty()) {
            throw new \Exception("Las siguientes observaciones no existen en la base de datos: " . $observacionesFaltantes->join(', '));
        }

        foreach (array_slice($rows, 5) as $i => $row) {
            $fila = $i + 6;
            $loteCodigo = $row[$loteIndex]??null;
            $subLoteCodigo = $row[$subloteIndex]??null;
            $campoNombre = $row[$campoIndex];
            $fecha = $this->parseFecha($row[$fechaIndex],$fila);
            $totalKilos = $row[$kilo_totalIndex];
            $observacion = $row[$obsIndex];
            if(!$campoNombre){
                continue;
            }
            //variables para actualizar el cultivo de las campañias
            $campania = $row[$campaniaIndex];
            $cultivo = $row[$cultivoIndex];

            $codigoObservacion = $this->generarCodigoSlug($observacion);

            $loteCodigoPrincipal = $loteCodigo ?? explode('.', $subLoteCodigo)[0];

            // Obtener o crear ingreso (si es lote nuevo o sublote sin ingreso previo)
            $ingreso = CochinillaIngreso::firstOrCreate(
                ['lote' => $loteCodigoPrincipal],
                [
                    'campo' => $campoNombre,
                    'fecha'=>$fecha,
                    'observacion' => $codigoObservacion,
                ]
            );

            // Si es fila de LOTE (no sublote), actualizamos datos
            if ($loteCodigo) {
                $ingreso->update([
                    'fecha' => $fecha,
                    'campo' => $campoNombre,
                    'observacion' => $codigoObservacion,
                    'total_kilos' => $totalKilos,
                ]);
            }

            // Guardamos detalle del sublote
            if ($subLoteCodigo) {
                CochinillaIngresoDetalle::updateOrCreate(
                    [
                        'cochinilla_ingreso_id' => $ingreso->id,
                        'sublote_codigo' => $subLoteCodigo,
                    ],
                    [
                        'fecha' => $fecha,
                        'total_kilos' => $totalKilos,
                        'observacion' => $codigoObservacion,
                    ]
                );
            }

            // Guardamos variedad para luego actualizar en CampoCampania
            $key = $campoNombre . '|' . $campania;
            $variedadCultivos[$key] = $cultivo;
        }

        // Finalmente, actualizar variedad_tuna en campo_campania
        foreach ($variedadCultivos as $key => $variedad) {
            [$campoNombre, $campania] = explode('|', $key);
            $relacion = CampoCampania::where('campo', $campoNombre)
                ->where('nombre_campania', $campania)
                ->first();

            if ($relacion) {
                $relacion->update(['variedad_tuna' => $variedad]);
            }
        }
    }
    private function parseFecha($valor, $fila)
    {
        if (empty($valor))
            return null;

        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d');
            }

            return Carbon::parse(str_replace(['.', '/', '\\'], '-', $valor))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Error al interpretar la fecha '{$valor}' en la fila #{$fila}: " . $e->getMessage());
        }
    }
    private function generarCodigoSlug($texto = null)
    {
        if(!$texto){
            return '';
        }
        $texto = strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n'
        ]);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto); // quita signos raros
        $texto = preg_replace('/\s+/', '_', $texto); // reemplaza espacios por guion bajo
        return $texto;
    }
}
