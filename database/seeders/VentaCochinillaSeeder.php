<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\Cochinilla\VentaServicio;
use App\Support\ExcelHelper;
use DB;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class VentaCochinillaSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $sheet = ExcelHelper::cargarHoja('public', 'informacion_general_ventas.xlsx', 'Ventas');
            $table = $sheet->getTableByName('table_ventas');

            if (!$table) {
                throw new Exception("No se encontró la tabla table_ventas.");
            }

            // Obtener el rango de la tabla (ejemplo: "A1:O20")
            $tableRange = $table->getRange();

            $data = $sheet->rangeToArray($tableRange, null, true, false, true);

            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));


            $upserts = [];
            // Reestructurar los datos con claves semánticas y resetear índices
            $datosNormalizados = collect($data)
                ->map(fn($row) => array_combine($headers, $row))
                ->values();

            $camposExcel = $datosNormalizados->pluck('campo')->all();
            $resultadoValidacion = CampoServicio::validarCamposDesdeExcel($camposExcel);

            if (!empty($resultadoValidacion['invalidos'])) {
                throw new Exception("Los siguientes campos no existen en la base de datos: " . implode(', ', $resultadoValidacion['invalidos']));
            }

            $filtroCampos = $resultadoValidacion['filtro'];

            $upserts = [];
            $grupoActual = null;
            $contadorGrupo = 1;
            $fechaReferencia = null;

            foreach ($datosNormalizados as $i => $fila) {
                $fechaFiltrado = ExcelHelper::parseFecha($fila['fecha_filtrado'] ?? null, $i + 1);
                $fechaVenta = ExcelHelper::parseFecha($fila['fecha_venta'] ?? null, $i + 1);
                $totalVenta = $fila['total_venta'] ?? null;

                // Si cambia el día de la venta, reiniciamos el contador
                $diaActual = $fechaVenta ?? $fechaFiltrado;

                if ($diaActual !== $fechaReferencia) {
                    $fechaReferencia = $diaActual;
                    $contadorGrupo = 1;
                    $grupoActual = $fechaReferencia ? Carbon::parse($fechaReferencia)->format('Ymd') . '_' . $contadorGrupo : null;
                }

                $campoOriginal = $fila['campo'] ?? null;
                $campoKey = mb_strtolower(trim($campoOriginal));
                $campoNormalizado = $campoKey ? ($filtroCampos[$campoKey] ?? null) : null;

                $esCampoValido = in_array($campoKey, array_keys($filtroCampos));

                $upserts[] = [
                    'grupo_venta' => $grupoActual,
                    'fecha_filtrado' => $fechaFiltrado,
                    'cantidad_seca' => $fila['cantidad_seca'] ?? null,
                    'condicion' => $fila['condicion'] ?? 'venta',
                    'cliente' => $fila['cliente'] ?? null,
                    'item' => $fila['item'] ?? 'Cochinilla seca',
                    'fecha_venta' => $fechaVenta,
                    'campo' => $esCampoValido ? $campoNormalizado : null,
                    'origen_especial' => !$esCampoValido ? $campoOriginal : null,
                    'procedencia' => $fila['procedencia'] ?? null,
                    'tipo_venta' => $fila['tipo_venta'] ?? null,
                    'observaciones' => $fila['observaciones'] ?? null,
                    'contabilizado' => false,
                    'aprobado_admin'=>true,
                    'aprobado_facturacion'=>true,
                ];
                // Si hay total_venta, esta fila marca el fin del grupo actual
                if (!empty($totalVenta)) {
                    $contadorGrupo++;
                    $grupoActual = Carbon::parse($fechaReferencia)->format('Ymd') . '_' . $contadorGrupo;
                }

            }

            //evitar duplicar registros
            $registrosExistentes = DB::table('venta_cochinillas')
                ->select('fecha_filtrado', 'campo', 'fecha_venta', 'condicion', 'cantidad_seca')
                ->get()
                ->map(function ($item) {
                    return [
                        'fecha_filtrado' => $item->fecha_filtrado ? Carbon::parse($item->fecha_filtrado)->format('Y-m-d') : null,
                        'campo' => $item->campo,
                        'fecha_venta' => $item->fecha_venta ? Carbon::parse($item->fecha_venta)->format('Y-m-d') : null,
                        'condicion' => mb_strtolower(trim($item->condicion)),
                        'cantidad_seca' => (float) $item->cantidad_seca,
                    ];
                })

                ->toArray();

            $clavesExistentes = collect($registrosExistentes)->map(
                fn($r) =>
                implode('|', [
                    $r['fecha_filtrado'],
                    $r['campo'],
                    $r['fecha_venta'],
                    $r['condicion'],
                    number_format($r['cantidad_seca'], 2), // Normalizamos decimales
                ])
            )->toArray();
                       
            $nuevosUpserts = collect($upserts)->filter(function ($fila) use ($clavesExistentes) {
                $clave = implode('|', [
                    $fila['fecha_filtrado'],
                    $fila['campo'],
                    $fila['fecha_venta'],
                    mb_strtolower(trim($fila['condicion'])),
                    number_format($fila['cantidad_seca'], 2),
                ]);

                return !in_array($clave, $clavesExistentes);
            })->values()->toArray();

            $registrosAfectados = VentaServicio::cargar($nuevosUpserts);
            $this->command->info("Se registraron $registrosAfectados nuevas ventas.");


        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
            return;
        }

    }

}
