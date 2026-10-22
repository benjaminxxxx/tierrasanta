<?php

namespace Database\Seeders;

use App\Models\CampoCampania;
use App\Support\ExcelHelper;
use App\Support\ValidacionHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CampoCampaniaSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // 1. Cargar datos del Excel
            $filename = config('system.files.informacion_general');
            $sheet = ExcelHelper::cargarHoja('public', $filename, 'CAMPAÑAS');
            $table = $sheet->getTableByName('table_campania');

            if (!$table) {
                throw new \Exception("No se encontró la tabla 'table_campania' en el Excel.");
            }

            // 2. Transformar rango a Array con Headers slugificados
            $dataRaw = $sheet->rangeToArray($table->getRange(), null, true, false, true);
            $headers = array_map(fn($h) => Str::slug($h, '_'), array_shift($dataRaw));
            
            $coleccionDinamica = collect($dataRaw)->map(function ($row) use ($headers) {
                return array_combine($headers, $row);
            });

            // 3. Validar y obtener IDs/Nombres de los campos involucrados
            $nombresCampos = $coleccionDinamica->pluck('campo')->filter()->unique()->toArray();
            $camposMapeados = ValidacionHelper::obtenerYValidarCampos($nombresCampos);

            // 4. Iniciar Transacción y Procesar Upserts
            DB::transaction(function () use ($coleccionDinamica, $camposMapeados) {
                
                $this->command->getOutput()->progressStart($coleccionDinamica->count());

                foreach ($coleccionDinamica as $index => $fila) {
                    // Limpieza y preparación de datos
                    $nombreCampo = mb_strtolower(trim($fila['campo'] ?? ''));
                    
                    if (empty($nombreCampo) || !isset($camposMapeados[$nombreCampo])) {
                        $this->command->getOutput()->progressAdvance();
                        continue;
                    }

                    // Parseo de fechas (asumiendo que parseFecha maneja nulos para campañas abiertas)
                    $fechaInicio = ExcelHelper::parseFecha($fila['fecha_inicio'], $index + 2);
                    $fechaFin = !empty($fila['fecha_final']) 
                                ? ExcelHelper::parseFecha($fila['fecha_final'], $index + 2) 
                                : null;

                    CampoCampania::updateOrInsert(
                        [
                            'campo'        => $camposMapeados[$nombreCampo], 
                            'fecha_inicio' => $fechaInicio
                        ],
                        [
                            'fecha_fin'       => $fechaFin,
                            'tipo_cambio'     => $fila['tipo_cambio'] ?? null,
                            'nombre_campania' => $fila['nombre_campania'] ?? null,
                            'updated_at'      => now(),
                        ]
                    );

                    $this->command->getOutput()->progressAdvance();
                }

                $this->command->getOutput()->progressFinish();
            });

            $this->command->info('Datos de campañas importados/actualizados correctamente.');

        } catch (Throwable $th) {
            $this->command->error("Error en Seeder: " . $th->getMessage());
            // El rollback es automático al usar DB::transaction() y lanzar una excepción
        }
    }
}