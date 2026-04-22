<?php

namespace App\Services;

use App\Models\PlanRegistroDiario;
use App\Models\PlanTipoAsistencia;
use DB;
use Illuminate\Support\Facades\Artisan;

class PlanTipoAsistenciaServicio
{
    public function obtenerCodigosNoRegistrados()
    {
        // Todos los códigos válidos
        $codigosValidos = PlanTipoAsistencia::pluck('codigo')->toArray();

        // Todos los códigos usados en registros diarios
        $codigosUsados = PlanRegistroDiario::distinct()
            ->pluck('asistencia')
            ->filter() // elimina null/vacíos
            ->toArray();

        // Diferencia → los que NO existen en catálogo
        $codigosInvalidos = array_diff($codigosUsados, $codigosValidos);

        return array_values($codigosInvalidos); // reindexado limpio
    }
    public static function obtenerHorasConsideradas(string $codigoAsistencia)
    {
        try {

            // Buscar el tipo de asistencia
            $tipoAsistencia = PlanTipoAsistencia::where('codigo', $codigoAsistencia)->first();

            // Si no existe → THROW obligatorio
            if (!$tipoAsistencia) {
                throw new \Exception("El código de asistencia '{$codigoAsistencia}' no existe en PlanTipoAsistencia.");
            }

            // horas_jornal debe ser numérico; si no, retorna 0
            return is_numeric($tipoAsistencia->horas_jornal)
                ? (float) $tipoAsistencia->horas_jornal
                : 0;

        } catch (\Exception $e) {
            // Re-lanzar excepciones de dominio (como código no existente)
            throw $e;
        } catch (\Throwable $t) {
            // Cualquier otro error NO esperado retorna 0
            return 0;
        }
    }
    public function listarTodos()
    {
        return PlanTipoAsistencia::all();
    }
    public function obtenerCodigosParaSelector()
    {
        return array_merge(
            [''],
            PlanTipoAsistencia::pluck('codigo')->toArray()
        );
    }

    /**
     * Obtiene un mapa de [codigo => horas_jornal]
     */
    public function obtenerMapaHoras()
    {
        return PlanTipoAsistencia::pluck('horas_jornal', 'codigo')->toArray();
    }
    /**
     * Obtiene un diccionario de metadatos (color y descripción) indexado por código
     */
    public function obtenerDiccionarioConfiguracion()
    {
        return PlanTipoAsistencia::all()->mapWithKeys(function ($item) {
            return [
                $item->codigo => [
                    'color' => $item->color,
                    'descripcion' => $item->descripcion
                ]
            ];
        })->toArray();
    }
    public function obtenerPorId($id)
    {
        return PlanTipoAsistencia::findOrFail($id);
    }
    public function obtenerPorCodigo($codigo)
    {
        return PlanTipoAsistencia::where('codigo', $codigo)->first();
    }
    public function guardar(array $datos, $id = null)
    {
        if ($id) {
            $registro = $this->obtenerPorId($id);
            $registro->update($datos);
            return $registro;
        }
        return PlanTipoAsistencia::create($datos);
    }

    public function eliminar($id)
    {
        $registro = $this->obtenerPorId($id);
        return $registro->delete();
    }

    public function restaurarPorDefecto()
    {
        PlanTipoAsistencia::truncate();
        return Artisan::call('db:seed', [
            '--class' => 'PlanTipoAsistenciaSeeder'
        ]);
    }
    /**
     * Registra o actualiza un solo tipo
     */
    public function registrarOActualizar(array $datos): PlanTipoAsistencia
    {
        return PlanTipoAsistencia::updateOrCreate(
            ['codigo' => $datos['codigo']],
            $datos
        );
    }

    /**
     * Registra o actualiza múltiples tipos (RECOMENDADO)
     */
    public function registrarOActualizarLote(array $tipos): void
    {
        DB::transaction(function () use ($tipos) {
            foreach ($tipos as $tipo) {
                $this->registrarOActualizar($tipo);
            }
        });
    }
}