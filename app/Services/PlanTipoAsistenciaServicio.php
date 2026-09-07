<?php

namespace App\Services;

use App\Models\PlanRegistroDiario;
use App\Models\PlanTipoAsistencia;
use App\Models\PlanTipoSuspension;
use DB;
use Illuminate\Support\Facades\Artisan;

class PlanTipoAsistenciaServicio
{
    // Códigos protegidos que no se pueden eliminar ni renombrar su código base
    public const CODIGOS_PROTEGIDOS = ['A', 'F', 'V'];

    public function obtenerCodigosNoRegistrados(): array
    {
        $codigosValidos = PlanTipoAsistencia::pluck('codigo')->toArray();

        $codigosUsados = PlanRegistroDiario::distinct()
            ->pluck('asistencia')
            ->filter()
            ->toArray();

        return array_values(array_diff($codigosUsados, $codigosValidos));
    }

    public static function obtenerHorasConsideradas(string $codigoAsistencia): float
    {
        $tipoAsistencia = PlanTipoAsistencia::where('codigo', $codigoAsistencia)->first();

        if (!$tipoAsistencia) {
            throw new \Exception("El código de asistencia '{$codigoAsistencia}' no existe en PlanTipoAsistencia.");
        }

        return is_numeric($tipoAsistencia->horas_jornal) ? (float) $tipoAsistencia->horas_jornal : 0;
    }

    public function listarTodos()
    {
        return PlanTipoAsistencia::with('tipoSuspension')->orderBy('codigo')->get();
    }

    public function obtenerCodigosParaSelector(): array
    {
        return array_merge([''], PlanTipoAsistencia::pluck('codigo')->toArray());
    }

    public function obtenerMapaHoras(): array
    {
        return PlanTipoAsistencia::pluck('horas_jornal', 'codigo')->toArray();
    }

    public function obtenerDiccionarioConfiguracion(): array
    {
        return PlanTipoAsistencia::all()->mapWithKeys(fn($item) => [
            $item->codigo => [
                'color' => $item->color,
                'descripcion' => $item->descripcion,
            ],
        ])->toArray();
    }

    /**
     * Opciones para el <x-select> del formulario, agrupadas por grupo SUNAT (SP/SI).
     * Formato: ['SP' => [id => "01 - Sanción"], 'SI' => [id => "20 - Descanso médico"]]
     */
    public function obtenerOpcionesTipoSuspension(): array
    {
        return PlanTipoSuspension::orderBy('grupo')->orderBy('codigo')->get()
            ->groupBy('grupo')
            ->map(fn($grupo) => $grupo->mapWithKeys(fn($item) => [
                $item->id => "{$item->codigo} - {$item->descripcion_corta}",
            ]))
            ->toArray();
    }

    public function obtenerPorId($id)
    {
        return PlanTipoAsistencia::findOrFail($id);
    }

    public function obtenerPorCodigo($codigo)
    {
        return PlanTipoAsistencia::where('codigo', $codigo)->first();
    }

    public function esCodigoProtegido(string $codigo): bool
    {
        return in_array($codigo, self::CODIGOS_PROTEGIDOS);
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

        if ($this->esCodigoProtegido($registro->codigo)) {
            throw new \Exception("El código '{$registro->codigo}' es protegido y no puede eliminarse.");
        }

        return $registro->delete();
    }

    public function restaurarPorDefecto()
    {
        PlanTipoAsistencia::truncate();
        return Artisan::call('db:seed', ['--class' => 'PlanTipoAsistenciaSeeder']);
    }

    public function registrarOActualizar(array $datos): PlanTipoAsistencia
    {
        return PlanTipoAsistencia::updateOrCreate(['codigo' => $datos['codigo']], $datos);
    }

    public function registrarOActualizarLote(array $tipos): void
    {
        DB::transaction(function () use ($tipos) {
            foreach ($tipos as $tipo) {
                $this->registrarOActualizar($tipo);
            }
        });
    }
}