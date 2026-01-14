<?php

namespace Database\Seeders;

use App\Models\CochinillaFiltrado;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CochinillaFiltradoSeeder extends Seeder
{
    public function run(): void
    {
        try {
            
            $filename = config('system.files.informacion_general');
            $sheet = ExcelHelper::cargarHoja('public', $filename, 'FILTRADO');
            $table = $sheet->getTableByName('table_filtrados');

            if (!$table) {
                throw new Exception("No se encontró la tabla table_filtrados.");
            }

            $tableRange = $table->getRange();
            $data = $sheet->rangeToArray($tableRange, null, true, false, true);

            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

            $upserts = [];
            $acumulado = [
                'kilos_ingresados' => 0,
                'primera' => 0,
                'segunda' => 0,
                'tercera' => 0,
                'piedra' => 0,
            ];

            foreach ($data as $index => $fila) {
                $fila_numero = $index + 2;
                $lote = (int) $fila['lote'];

                $es_ultima_fila_lote = !isset($data[$index + 1]) || (int) $data[$index + 1]['lote'] !== $lote;
                $es_unica_fila_lote = $es_ultima_fila_lote &&
                    (!isset($data[$index - 1]) || (int) $data[$index - 1]['lote'] !== $lote);

                /** ───── VALIDACIÓN FILA TOTAL ───── */
                if ($es_ultima_fila_lote && !$es_unica_fila_lote) {

                    $errores = [];

                    foreach (array_keys($acumulado) as $campo) {
                        $acum = round($acumulado[$campo], 2);
                        $actual = round((float) $fila[$campo], 2);

                        if ($acum !== $actual) {
                            $errores[] = "$campo: acumulado=$acum, total=$actual";
                        }
                    }

                    if ($errores) {
                        throw new Exception(
                            "Error en fila $fila_numero (total del lote $lote): " .
                            implode(' | ', $errores)
                        );
                    }

                    // reset y saltar fila total
                    $acumulado = array_map(fn() => 0, $acumulado);
                    continue;
                }

                /** ───── ACUMULADO NORMAL ───── */
                if (!$es_unica_fila_lote) {
                    foreach (array_keys($acumulado) as $campo) {
                        $acumulado[$campo] += (float) $fila[$campo];
                    }
                }

                /** ───── VALIDACIÓN INTERNA DE FILA ───── */
                $kilos = round((float) $fila['kilos_ingresados'], 2);
                $componentes = round(
                    (float) $fila['primera'] +
                    (float) $fila['segunda'] +
                    (float) $fila['tercera'] +
                    (float) $fila['piedra'],
                    2
                );

                $basura_calculada = round($kilos - $componentes, 2);

                if ($basura_calculada < 0) {
                    throw new Exception(
                        "Error en fila $fila_numero: los componentes exceden kilos_ingresados"
                    );
                }

                /** ───── INSERT ───── */
                $upserts[] = [
                    'lote' => $lote,
                    'fecha_proceso' => ExcelHelper::parseFecha($fila['fecha_de_proceso'], $fila_numero),
                    'kilos_ingresados' => $kilos,
                    'primera' => $fila['primera'] ?? 0,
                    'segunda' => $fila['segunda'] ?? 0,
                    'tercera' => $fila['tercera'] ?? 0,
                    'piedra' => $fila['piedra'] ?? 0,
                ];
            }

            CochinillaFiltrado::truncate();
            CochinillaFiltrado::insert($upserts);

            $this->command->info('Datos de filtrado importados y validados correctamente.');

        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
        }
    }


}
