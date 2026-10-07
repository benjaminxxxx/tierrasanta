<?php

namespace App\Services\Campo\ManoObra;

use App\Models\Labores;
use App\Models\LaborVigencia;
use App\Models\ManoObra;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Mano de obra: el grupo de cada labor con que se arman los costos de producción de las campañas.
 *
 * Su código es lo que guardan las labores (y la historia de códigos de labor), así que:
 * - Con labores o historia no se puede eliminar (la base de datos dejaría esas labores sin grupo) ni cambiar su
 *   código. La descripción sí se puede corregir.
 * - Los códigos que usan las reglas de costos de producción (orden de grupos, riego) tampoco cambian de código.
 */
class CampoManoObraCrud
{
    /** @return array<string, int> dónde se usa el código (solo los que tienen) */
    public function usos(string $codigo): array
    {
        return array_filter([
            'labores' => Labores::withTrashed()->where('codigo_mano_obra', $codigo)->count(),
            'labores anteriores de códigos reasignados' => LaborVigencia::where('codigo_mano_obra', $codigo)->count(),
        ]);
    }

    /** Por qué el código ya no se puede cambiar ni eliminar (null = libre). */
    public function motivoFijo(string $codigo): ?string
    {
        $partes = collect($this->usos($codigo))->map(fn($n, $donde) => "{$n} {$donde}");
        if ($this->esDelSistema($codigo)) {
            $partes->push('lo usan las reglas de costos de producción');
        }
        return $partes->isEmpty() ? null : 'tiene ' . $partes->implode(', ');
    }

    public function esDelSistema(string $codigo): bool
    {
        return in_array($codigo, CampaniaCostoProduccionReglas::ORDEN_MANO_OBRA, true) || $codigo === CampaniaCostoProduccionReglas::MANO_OBRA_RIEGO;
    }

    /** Crea (sin $codigoActual) o edita. Con usos, el código queda como estaba: solo cambia la descripción. */
    public function guardar(?string $codigoActual, array $datos): ManoObra
    {
        $datos = ['codigo' => trim((string) ($datos['codigo'] ?? '')), 'descripcion' => trim((string) ($datos['descripcion'] ?? ''))];
        Validator::make($datos, [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('mano_obras', 'codigo')->ignore($codigoActual, 'codigo')],
            'descripcion' => ['required', 'string', 'max:255', Rule::unique('mano_obras', 'descripcion')->ignore($codigoActual, 'codigo')],
        ], [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.unique' => 'La descripción ya está registrada.',
        ])->validate();

        if ($codigoActual && $datos['codigo'] !== $codigoActual && ($motivo = $this->motivoFijo($codigoActual))) {
            throw ValidationException::withMessages(['codigo' => "No se puede cambiar el código \"{$codigoActual}\": {$motivo}. Solo se puede corregir la descripción."]);
        }

        return DB::transaction(function () use ($codigoActual, $datos) {
            if (!$codigoActual) {
                $mano = ManoObra::create($datos);
                AuditoriaServicio::registrar(ManoObra::class, $mano->codigo, 'crear', null, $datos);
                return $mano;
            }
            $mano = ManoObra::findOrFail($codigoActual);
            $antes = $mano->only(['codigo', 'descripcion']);
            $mano->update($datos);
            AuditoriaServicio::registrar(ManoObra::class, $mano->codigo, 'editar', $antes, $datos);
            return $mano;
        });
    }

    public function eliminar(string $codigo): void
    {
        $mano = ManoObra::findOrFail($codigo);
        if ($motivo = $this->motivoFijo($codigo)) {
            throw ValidationException::withMessages(['codigo' => "No se puede eliminar \"{$mano->descripcion}\": {$motivo}. "
                . 'Si ya no se usa, pasa antes sus labores a otra mano de obra.']);
        }
        DB::transaction(function () use ($mano) {
            AuditoriaServicio::registrar(ManoObra::class, $mano->codigo, 'eliminar', $mano->only(['codigo', 'descripcion']));
            $mano->delete();
        });
    }

    /** Labores por código (activas y desactivadas), para la lista. @return array<string, int> */
    public function laboresPorCodigo(): array
    {
        return Labores::withTrashed()->whereNotNull('codigo_mano_obra')->groupBy('codigo_mano_obra')
            ->selectRaw('codigo_mano_obra, COUNT(*) n')->pluck('n', 'codigo_mano_obra')->all();
    }
}
