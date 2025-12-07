<?php

namespace App\Services\Produccion\MateriaPrima;

use App\Exports\Produccion\MateriaPrima\BrotesPorPisoExport;
use App\Models\EvalBrotesPorPiso;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class BrotesPorPisoServicio
{
    protected CampaniaServicio $campaniaServicio;

    public function __construct(CampaniaServicio $campaniaServicio)
    {
        $this->campaniaServicio = $campaniaServicio;
    }
    public function exportar($filtros)
    {
        $crudos = EvalBrotesPorPiso::get();
        $ordenado = $this->ordenarDatosExportBrotesPorPiso($filtros, $crudos);
        return Excel::download(new BrotesPorPisoExport($ordenado), date('Y-m-d') . '_brotes_por_piso.xlsx');
    }

    public static function buscar(array $filtros, bool $paginado = true)
    {
        $query = EvalBrotesPorPiso::query()
            ->with(['campania', 'detalles']);

        // Filtrar por campo (campo proviene del modelo CampoCampania)
        if (!empty($filtros['campo'])) {
            $query->whereHas('campania', function ($q) use ($filtros) {
                $q->where('campo', $filtros['campo']);
            });
        }

        // Filtrar campaña
        if (!empty($filtros['campania_id'])) {
            $query->where('campania_id', $filtros['campania_id']);
        }

        // Filtrar evaluador
        if (!empty($filtros['evaluador'])) {
            $query->where('evaluador', 'like', '%' . $filtros['evaluador'] . '%');
        }

        // Filtrar fecha exacta
        if (!empty($filtros['fecha'])) {
            $query->whereDate('fecha', $filtros['fecha']);
        }

        if (!$paginado) {
            return $query->get();
        }
        return $query->paginate(20);
    }

    public function registrar($datos)
    {
        DB::beginTransaction();

        try {
            // 1. Validar todo (Cabecera y Detalles)
            $this->validarDatos($datos);

            // 2. Guardar Cabecera
            $evaluacion = $this->guardarCabecera($datos);

            // 3. Guardar Detalles
            $this->guardarDetalles($evaluacion, $datos['detalles']);

            // 4. Actualizar Campaña (Usando el servicio externo)
            $metricas = $this->calcularMetricas($evaluacion);

            // 5. Actualizar Campaña (solo campos enviados)
            $this->campaniaServicio->actualizarMetricas(
                $datos['campania_id'],
                $metricas
            );
            DB::commit();

            return $evaluacion->id;

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function eliminar(int $id)
    {
        DB::beginTransaction();

        try {
            $brotesPorPiso = EvalBrotesPorPiso::findOrFail($id);
            $campaniaId = $brotesPorPiso->campania_id;
            $brotesPorPiso->delete();

            // 4. Actualizar Campaña (Usando el servicio externo)
            $metricasNull = $this->calcularMetricasNull();
            $this->campaniaServicio->actualizarMetricas($campaniaId, $metricasNull);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    #region Métodos Privados
    private function ordenarDatosExportBrotesPorPiso(array $filtros, $coleccion)
    {
        $resultado = [];

        foreach ($coleccion as $item) {

            // 1. Campo
            $campo = $item->campania->campo ?? 'SIN_CAMPO';

            // 2. Campaña (nombre o id)
            $campania = $item->campania->nombre_campania ?? 'SIN_CAMPANIA';

            // 3. Crear estructura base si no existe
            if (!isset($resultado[$campo][$campania])) {
                $resultado[$campo][$campania] = [
                    'fecha_evaluacion' => $item->fecha,
                    'evaluador' => $item->evaluador,
                    'metros_cama_ha' => $item->metros_cama_ha,
                    'detalles' => [],
                ];
            }

            // 4. Procesar detalles
            foreach ($item->detalles as $detalle) {
                $resultado[$campo][$campania]['detalles'][] = [
                    'numero_cama' => $detalle->numero_cama,
                    'longitud_cama' => $detalle->longitud_cama,

                    'brotes_2p_actual' => $detalle->brotes_aptos_2p_actual,
                    'brotes_2p_despues_n_dias' => $detalle->brotes_aptos_2p_despues_n_dias,

                    'brotes_3p_actual' => $detalle->brotes_aptos_3p_actual,
                    'brotes_3p_despues_n_dias' => $detalle->brotes_aptos_3p_despues_n_dias,
                ];
            }
        }

        // 5. Ordenar por campo y campaña
        ksort($resultado);
        foreach ($resultado as $campo => $list) {
            ksort($resultado[$campo]);
        }

        // 6. Retorno
        return [
            'filtros' => $filtros,
            'datos' => $resultado,
        ];
    }

    private function calcularMetricas(EvalBrotesPorPiso $eval): array
    {
        return [
            'brotexpiso_fecha_evaluacion' => $eval->fecha,
            'brotexpiso_actual_brotes_2piso' => $eval->promedio_actual_brotes_2piso,
            'brotexpiso_brotes_2piso_n_dias' => $eval->promedio_brotes_2piso_n_dias,
            'brotexpiso_actual_brotes_3piso' => $eval->promedio_actual_brotes_3piso,
            'brotexpiso_brotes_3piso_n_dias' => $eval->promedio_brotes_3piso_n_dias,
            'brotexpiso_actual_total_brotes_2y3piso' => $eval->promedio_actual_total_brotes_2y3piso,
            'brotexpiso_total_brotes_2y3piso_n_dias' => $eval->promedio_total_brotes_2y3piso_n_dias,
        ];
    }
    private function calcularMetricasNull(): array
    {
        return [
            'brotexpiso_fecha_evaluacion' => null,
            'brotexpiso_actual_brotes_2piso' => null,
            'brotexpiso_brotes_2piso_n_dias' => null,
            'brotexpiso_actual_brotes_3piso' => null,
            'brotexpiso_brotes_3piso_n_dias' => null,
            'brotexpiso_actual_total_brotes_2y3piso' => null,
            'brotexpiso_total_brotes_2y3piso_n_dias' => null,
        ];
    }

    private function guardarDetalles(EvalBrotesPorPiso $evaluacion, array $detalles): void
    {
        // --------------------------------------------------------------------
        // 1. Eliminar detalles anteriores
        // --------------------------------------------------------------------
        $evaluacion->detalles()->delete();

        // --------------------------------------------------------------------
        // 2. Preparar nuevos detalles para inserción masiva
        // --------------------------------------------------------------------
        $detallesInsert = collect($detalles)->map(function ($fila) use ($evaluacion) {

            return [
                'brotes_x_piso_id' => $evaluacion->id,

                'numero_cama' => intval($fila['numero_cama']),
                'longitud_cama' => isset($fila['longitud_cama'])
                    ? floatval($fila['longitud_cama'])
                    : null,

                'brotes_aptos_2p_actual' => isset($fila['brotes_aptos_2p_actual'])
                    ? intval($fila['brotes_aptos_2p_actual'])
                    : null,

                'brotes_aptos_2p_despues_n_dias' => isset($fila['brotes_aptos_2p_despues_n_dias'])
                    ? intval($fila['brotes_aptos_2p_despues_n_dias'])
                    : null,

                'brotes_aptos_3p_actual' => isset($fila['brotes_aptos_3p_actual'])
                    ? intval($fila['brotes_aptos_3p_actual'])
                    : null,

                'brotes_aptos_3p_despues_n_dias' => isset($fila['brotes_aptos_3p_despues_n_dias'])
                    ? intval($fila['brotes_aptos_3p_despues_n_dias'])
                    : null,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        // --------------------------------------------------------------------
        // 3. Inserción masiva
        // --------------------------------------------------------------------
        $evaluacion->detalles()->insert($detallesInsert);
    }

    private function guardarCabecera(array $datos): EvalBrotesPorPiso
    {
        // -------------------------------------------------------------
        // Campos permitidos según tu migración eval_brotes_por_pisos
        // -------------------------------------------------------------
        $campos = [
            'campania_id' => $datos['campania_id'],
            'fecha' => $datos['fecha'],
            'metros_cama_ha' => $datos['metros_cama_ha'],
            'evaluador' => $datos['evaluador'] ?? null,
        ];

        // --------------------------------------------------------------------
        // A. Si viene ID → actualizar cabecera existente
        //    (permite editar sin romper la regla unique)
        // --------------------------------------------------------------------
        if (!empty($datos['id'])) {
            $eval = EvalBrotesPorPiso::findOrFail($datos['id']);
            $eval->update($campos);
            return $eval;
        }

        // --------------------------------------------------------------------
        // B. Validar unicidad por campaña
        //    Solo puede existir UNA evaluación por campaña.
        // --------------------------------------------------------------------
        $existe = EvalBrotesPorPiso::where('campania_id', $datos['campania_id'])->first();

        if ($existe) {
            // Si ya existe uno, solo lo actualizamos
            $existe->update($campos);
            return $existe;
        }

        // --------------------------------------------------------------------
        // C. Crear nuevo registro si no existe
        // --------------------------------------------------------------------
        return EvalBrotesPorPiso::create($campos);
    }

    private function validarDatos(array $datos): void
    {
        // ===============================
        // A. VALIDACIÓN GENERAL
        // ===============================
        $validator = Validator::make($datos, [
            'id' => 'nullable|integer|exists:eval_brotes_por_pisos,id',
            'campania_id' => 'required|integer|exists:campos_campanias,id',
            'fecha' => 'date',
            'metros_cama_ha' => 'required|numeric|min:0.1',
            'evaluador' => 'required|string|max:255',
            'detalles' => 'required|array|min:1',

        ], [
            'detalles.required' => 'Debe ingresar filas en la tabla.',
            'metros_cama_ha.required' => 'Los metros de cama por hectárea son obligatorios.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // ===============================
        // B. VALIDACIÓN DE FILAS (DETALLES)
        // ===============================
        foreach ($datos['detalles'] as $i => $fila) {

            $filaValidator = Validator::make($fila, [
                'numero_cama' => 'required|integer|min:1',
                'longitud_cama' => 'nullable|numeric|min:0.01|max:999999.99',
                'brotes_aptos_2p_actual' => 'nullable|integer|min:0',
                'brotes_aptos_2p_despues_n_dias' => 'nullable|integer|min:0',
                'brotes_aptos_3p_actual' => 'nullable|integer|min:0',
                'brotes_aptos_3p_despues_n_dias' => 'nullable|integer|min:0',
            ]);

            if ($filaValidator->fails()) {

                $errores = [];

                foreach ($filaValidator->errors()->getMessages() as $campo => $msgs) {
                    $errores["detalles.$i.$campo"] = $msgs;
                }

                throw ValidationException::withMessages($errores);
            }
        }
    }
    #endregion

}