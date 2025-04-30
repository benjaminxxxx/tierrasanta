<?php

namespace Database\Seeders;

use App\Models\CochinillaFiltrado;
use App\Models\CochinillaVenteado;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CochinillaFiltradoSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $sheet = ExcelHelper::cargarHoja('public', 'informacion_general.xlsx', 'FILTRADO');
            $table = $sheet->getTableByName('table_filtrados');

            if (!$table) {
                throw new Exception("No se encontró la tabla table_filtrados.");
            }

            // Obtener el rango de la tabla (ejemplo: "A1:O20")
            $tableRange = $table->getRange();

            $data = $sheet->rangeToArray($tableRange, null, true, false, true);

            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

            // Reestructurar los datos con claves semánticas y resetear índices
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

            $upserts = [];
            $acumulado = [
                'kilos_ingresados' => 0,
                'primera' => 0,
                'segunda' => 0,
                'tercera' => 0,
                'piedra' => 0,
                'basura' => 0,
            ];

            $lote_actual = null;
            $i = 0;

            foreach ($data as $index => $fila) {
                $fila_numero = $index + 2; // fila real en Excel

                if ($fila_numero<1573) {
                    //los valores en adelante recien seran registrados
                    continue;
                }
                //dd($fila_numero,$fila);
                $lote = (int) $fila['lote'];
                $es_ultima_fila_lote = false;
                $es_unica_fila_lote = false;

                // Verificar si la próxima fila tiene otro lote o no existe
                if (!isset($data[$index + 1]) || (int) $data[$index + 1]['lote'] !== $lote) {
                    $es_ultima_fila_lote = true;
                    if (!isset($data[$index - 1]) || (int) $data[$index - 1]['lote'] !== $lote) {
                        $es_unica_fila_lote = true;
                    }
                }

                if ($es_ultima_fila_lote) {

                    if (!$es_unica_fila_lote) {
                        $hay_acumulado = array_sum($acumulado) > 0;

                        if ($hay_acumulado) {
                            // Validamos que los totales coincidan con lo acumulado
                            $errores = [];

                            foreach (['kilos_ingresados', 'primera', 'segunda', 'tercera', 'piedra', 'basura'] as $campo) {
                                $acum = round($acumulado[$campo], 2);
                                $actual = round((float) $fila[$campo], 2);

                                if ($acum !== $actual) {
                                    $errores[] = "$campo: acumulado = $acum, total esperado = $actual";
                                }
                            }

                            if (!empty($errores)) {
                                $detalle = implode(' | ', $errores);
                                throw new Exception("Error en fila $fila_numero (total del lote $lote): no coincide la suma de los subtotales. $detalle");
                            }
                        }


                        // Reset acumulado y saltar la fila total
                        $acumulado = [
                            'kilos_ingresados' => 0,
                            'primera' => 0,
                            'segunda' => 0,
                            'tercera' => 0,
                            'basura' => 0,
                            'piedra' => 0
                        ];
                        continue;
                    }

                }

                if (!$es_unica_fila_lote) {
                    // Acumular valores de fila normal
                    foreach (['kilos_ingresados', 'primera', 'segunda', 'tercera', 'basura', 'piedra'] as $campo) {
                        $acumulado[$campo] += (float) $fila[$campo];
                    }
                }

                // Validación interna: los componentes suman al total
                $esperado = round((float) $fila['kilos_ingresados'], 2);
                $suma = round(
                    (float) $fila['primera'] +
                    (float) $fila['segunda'] +
                    (float) $fila['tercera'] +
                    (float) $fila['basura'] +
                    (float) $fila['piedra'],
                    2
                );

                if ($esperado !== $suma) {
                    throw new Exception("Error en fila $fila_numero: primera + segunda + tercera + piedra = $suma, pero kilos_ingresados = $esperado");
                }

                $fecha_de_proceso = ExcelHelper::parseFecha($fila['fecha_de_proceso'], $fila_numero);

                $upserts[] = [
                    'lote' => $lote,
                    'fecha_proceso' => $fecha_de_proceso,
                    'kilos_ingresados' => $fila['kilos_ingresados'] ?? 0,
                    'primera' => $fila['primera'] ?? 0,
                    'segunda' => $fila['segunda'] ?? 0,
                    'tercera' => $fila['tercera'] ?? 0,
                    'piedra' => $fila['piedra'] ?? 0,
                    'basura' => $fila['basura'] ?? 0,
                ];
            }


            CochinillaFiltrado::insert($upserts);

            $this->command->info('Datos actualizados correctamentexlsx');

        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
            return;
        }

    }

}
