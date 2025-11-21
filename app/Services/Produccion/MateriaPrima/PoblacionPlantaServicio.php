<?php

namespace App\Services\Produccion\MateriaPrima;

use App\Exports\Produccion\MateriaPrima\PoblacionPlantaExport;
use App\Models\EvalPoblacionPlanta;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class PoblacionPlantaServicio
{
    protected CampaniaServicio $campaniaServicio;

    public function __construct(CampaniaServicio $campaniaServicio)
    {
        $this->campaniaServicio = $campaniaServicio;
    }
    public function exportar($data)
    {
        $datos = $this->buscar($data, false)->toArray();
        dd($datos);
        return Excel::download(new PoblacionPlantaExport($datos), date('Y-m-d') . '_poblacion_plantas.xlsx');
    }
    public static function buscar(array $filtros, $paginado = true)
    {
        $query = EvalPoblacionPlanta::query()
            ->with(['campania', 'detalles']);

        if (!empty($filtros['campo'])) {
            $query->whereHas('campania', callback: function ($q) use ($filtros) {
                $q->where('campo', $filtros['campo']);
            });
        }

        if (!empty($filtros['campania_id'])) {
            $query->where('campania_id', $filtros['campania_id']);
        }

        if (!empty($filtros['evaluador'])) {
            $query->where('evaluador', 'like', '%' . $filtros['evaluador'] . '%');
        }
        if (!empty($filtros['fecha'])) {
            $query->where(function ($q) use ($filtros) {
                $q->whereDate('fecha_eval_cero', $filtros['fecha'])
                    ->orWhereDate('fecha_eval_resiembra', $filtros['fecha']);
            });
        }
        if (!$paginado) {
            return $query->get();
        }
        return $query->paginate(20);
    }
    public function eliminar(int $id)
    {
        DB::beginTransaction();

        try {
            $poblacion = EvalPoblacionPlanta::findOrFail($id);
            $campaniaId = $poblacion->campania_id;
            $poblacion->delete();

            // 4. Actualizar Campaña (Usando el servicio externo)
            $this->campaniaServicio->actualizarMetricasPoblacion($campaniaId);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function registrar(array $datos)
    {
        DB::beginTransaction();

        try {
            // 1. Validar todo (Cabecera y Detalles)
            $this->validarDatos($datos);

            // 2. Guardar Cabecera
            $poblacion = $this->guardarCabecera($datos);

            // 3. Guardar Detalles
            $this->guardarDetalles($poblacion, $datos['detalles']);

            // 4. Actualizar Campaña (Usando el servicio externo)
            $this->campaniaServicio->actualizarMetricasPoblacion($datos['campania_id']);

            DB::commit();

            return $poblacion->id;

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // MÉTODOS PRIVADOS (Internal Helpers)
    // -------------------------------------------------------------------------

    private function validarDatos(array $datos): void
    {
        // -------------------------------
        // A. Validación general
        // -------------------------------
        $validator = Validator::make($datos, [
            'id' => 'nullable|integer|exists:eval_poblacion_plantas,id',
            'fecha_eval_cero' => 'required|date',
            'fecha_eval_resiembra' => 'nullable|date|after_or_equal:fecha_eval_cero',
            'fecha_siembra' => 'nullable|date',
            'area_lote' => 'required|numeric|min:0.0001',
            'evaluador' => 'required|string|max:255',
            'metros_cama_ha' => 'required|numeric|min:0.1',
            'campania_id' => 'required|integer|exists:campos_campanias,id',
            'detalles' => 'required|array|min:1',
        ], [
            'detalles.required' => 'Debe ingresar filas en la tabla.',
            'metros_cama_ha.required' => 'Los metros de cama por hectárea son obligatorios.',
            'area_lote.required' => 'El área del lote es obligatoria.'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // -------------------------------
        // B. Validación de filas (detalles)
        // -------------------------------
        foreach ($datos['detalles'] as $i => $fila) {

            $filaValidator = Validator::make($fila, [
                'numero_cama' => 'required|integer|min:1',
                'longitud_cama' => 'required|numeric|min:0.01|max:999999.99',
                'eval_cero_plantas_x_hilera' => 'required|integer|min:0',
                'eval_resiembra_plantas_x_hilera' => 'nullable|integer|min:0',
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


    private function guardarCabecera(array $datos): EvalPoblacionPlanta
    {
        // Campos permitidos según tu migración
        $campos = [
            'fecha_siembra' => $datos['fecha_siembra'],
            'area_lote' => $datos['area_lote'],
            'evaluador' => $datos['evaluador'] ?? null,
            'metros_cama_ha' => $datos['metros_cama_ha'],
            'campania_id' => $datos['campania_id'],
            'fecha_eval_cero' => $datos['fecha_eval_cero'],
            'fecha_eval_resiembra' => $datos['fecha_eval_resiembra'] ?? null,
        ];

        // --------------------------------------------------------------------
        // A. Si viene ID, actualizar (esto permite editar sin romper la regla)
        // --------------------------------------------------------------------
        if (!empty($datos['id'])) {
            $eval = EvalPoblacionPlanta::findOrFail($datos['id']);
            $eval->update($campos);
            return $eval;
        }

        // --------------------------------------------------------------------
        // B. Validar unicidad por campaña
        //    Solo puede existir UNA evaluación por campaña.
        // --------------------------------------------------------------------
        $existe = EvalPoblacionPlanta::where('campania_id', $datos['campania_id'])->first();

        if ($existe) {
            // Actualizar si ya existe
            $existe->update($campos);
            return $existe;
        }

        // --------------------------------------------------------------------
        // C. Crear nuevo registro si no existe
        // --------------------------------------------------------------------
        return EvalPoblacionPlanta::create($campos);
    }


    private function guardarDetalles(EvalPoblacionPlanta $evaluacion, array $detalles): void
    {
        // 1. Eliminar detalles anteriores
        $evaluacion->detalles()->delete();

        // 2. Insertar nuevos detalles con cálculos
        $detallesInsert = collect($detalles)->map(function ($fila) use ($evaluacion) {

            $longitud = floatval($fila['longitud_cama']);
            $cero = intval($fila['eval_cero_plantas_x_hilera']);
            $resiem = isset($fila['eval_resiembra_plantas_x_hilera'])
                ? intval($fila['eval_resiembra_plantas_x_hilera'])
                : null;

            return [
                'eval_poblacion_planta_id' => $evaluacion->id,
                'numero_cama' => intval($fila['numero_cama']),
                'longitud_cama' => $longitud,
                'eval_cero_plantas_x_hilera' => $cero,
                'eval_resiembra_plantas_x_hilera' => $resiem,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        // 3. Inserción masiva
        $evaluacion->detalles()->insert($detallesInsert);
    }

}