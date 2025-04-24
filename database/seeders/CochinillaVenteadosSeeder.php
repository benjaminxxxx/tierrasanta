<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaVenteado;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CochinillaVenteadosSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $sheet = ExcelHelper::cargarHoja('public', 'informacion_general.xlsx', 'VENTEADO');
            $table = $sheet->getTableByName('table_venteados');

            if (!$table) {
                throw new Exception("No se encontró la tabla table_venteados.");
            }

            $tableRange = $table->getRange();
            $data = $sheet->rangeToArray($tableRange, null, true, false, true);
            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();
            $collection = collect($data);

            $upserts = [];
            $acumulado = [
                'kilos_ingresados' => 0,
                'limpia' => 0,
                'basura' => 0,
                'polvillo' => 0,
            ];

            foreach ($collection as $index => $fila) {
                $fila_numero = $index + 2; // fila real en Excel
                $lote = (int) $fila['lote'];
                $es_ultima_fila_lote = false;
                $es_unica_fila_lote = false;

                if (!isset($collection[$index + 1]) || (int) $collection[$index + 1]['lote'] !== $lote) {
                    $es_ultima_fila_lote = true;
                    if (!isset($collection[$index - 1]) || (int) $collection[$index - 1]['lote'] !== $lote) {
                        $es_unica_fila_lote = true;
                    }
                }

                if ($es_ultima_fila_lote && !$es_unica_fila_lote) {
                    $hay_acumulado = array_sum($acumulado) > 0;

                    if ($hay_acumulado) {
                        $errores = [];

                        foreach (['kilos_ingresados', 'limpia', 'basura', 'polvillo'] as $campo) {
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

                    $acumulado = ['kilos_ingresados' => 0, 'limpia' => 0, 'basura' => 0, 'polvillo' => 0];
                    continue;
                }

                if (!$es_unica_fila_lote) {
                    foreach (['kilos_ingresados', 'limpia', 'basura', 'polvillo'] as $campo) {
                        $acumulado[$campo] += (float) $fila[$campo];
                    }
                }

                $esperado = round((float) $fila['kilos_ingresados'], 2);
                $suma = round(
                    (float) $fila['limpia'] +
                    (float) $fila['basura'] +
                    (float) $fila['polvillo'],
                    2
                );

                if ($esperado !== $suma) {
                    throw new Exception("Error en fila $fila_numero: limpia + basura + polvillo = $suma, pero kilos_ingresados = $esperado");
                }

                $fecha_de_proceso = $this->parseFecha($fila['fecha_de_proceso'], $fila_numero);
               
                $upserts[] = [
                    'lote' => $lote,
                    'fecha_proceso' => $fecha_de_proceso,
                    'kilos_ingresado' => $fila['kilos_ingresados'],
                    'limpia' => $fila['limpia'] ?? 0,
                    'basura' => $fila['basura'] ?? 0,
                    'polvillo' => $fila['polvillo'] ?? 0,
                ];
            }

            CochinillaVenteado::insert($upserts);

            $this->command->info('Datos actualizados correctamentexlsx');

        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
            return;
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
        } catch (Exception $e) {
            throw new Exception("Error al interpretar la fecha '{$valor}' en la fila #{$fila}: " . $e->getMessage());
        }
    }
}
