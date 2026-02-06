<?php
namespace App\Services\Labor;

use App\Models\Labores;
use App\Models\ManoObra;
use DB;
use App\Support\ExcelHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportarLaborProceso
{
    public static function ejecutar($archivo)
    {
        // 1. Preparar mapas de referencia para evitar consultas dentro del loop
        $manoObraMap = ManoObra::pluck('codigo', 'descripcion')->toArray();
        $laboresExistentes = Labores::withTrashed()->get()->keyBy('codigo');

        return DB::transaction(function () use ($archivo, $manoObraMap, $laboresExistentes) {

            // 2. Cargar datos de ambas hojas
            $hojasCfg = [
                'LABORES' => 'tblLabores',
                'TRAMOS' => 'tblTramos',
            ];
            $data = ExcelHelper::cargarData($archivo, $hojasCfg);

            // 3. Pre-procesar Tramos: Agruparlos por 'codigo labor' para adjuntarlos a la labor
            $tramosAgrupados = [];
            foreach ($data['TRAMOS'] ?? [] as $filaTramo) {
                $codigoL = $filaTramo['codigo labor'];
                $tramosAgrupados[$codigoL][] = [
                    'hasta' => $filaTramo['tramo hasta'],
                    'monto' => $filaTramo['pago'],
                ];
            }

            $idsProcesados = [];

            // 4. Procesar Labores
            foreach ($data['LABORES'] as $index => $fila) {
                $numFila = $index + 2; // +2 porque el Excel tiene cabecera y el índice empieza en 0
                $nombreMO = $fila['mano de obra'] ?? null;
                $codigoLabor = $fila['codigo'] ?? null;

                // 1. Validar que la mano de obra exista en el sistema
                if (!empty($nombreMO) && !isset($manoObraMap[$nombreMO])) {
                    throw ValidationException::withMessages([
                        'archivo' => "Error en la Fila {$numFila}: La mano de obra '" . ($nombreMO) . "' no está registrada en el sistema. Por favor, verifíquela o créela antes de importar."
                    ]);
                }

                // 2. Validar que el código de labor no sea nulo
                if (empty($codigoLabor)) {
                    throw ValidationException::withMessages([
                        'archivo' => "Error en la Fila {$numFila}: El código de la labor es obligatorio."
                    ]);
                }

                if (empty($fila['labor'])) {
                    continue;
                }

                $laborExistente = $laboresExistentes->get($codigoLabor);
                $idParaServicio = null;

                if ($laborExistente) {
                    if ($laborExistente->trashed()) {
                        $laborExistente->restore();
                    }
                    $idParaServicio = $laborExistente->id;
                }

                // 3. Preparar el payload ahora que estamos seguros de que la llave existe
                $payload = [
                    'codigo' => $codigoLabor,
                    'nombre_labor' => $fila['labor'] ?? null,
                    'unidades' => $fila['unidad'] ?? null,
                    'codigo_mano_obra' => $manoObraMap[$nombreMO] ?? null, // Aquí ya es seguro
                    'tramos_bonificacion' => $tramosAgrupados[$codigoLabor] ?? null,
                ];

                $labor = LaborServicio::guardar($payload, $idParaServicio);
                $idsProcesados[] = $labor->id;
            }

            // 6. Limpieza: Eliminar lo que no vino en el Excel
            LaborServicio::eliminarExcepto($idsProcesados);

            return count($idsProcesados);
        });
    }
}