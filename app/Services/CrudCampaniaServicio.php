<?php

namespace App\Services;

use App\Models\CampoCampania;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrudCampaniaServicio
{
    protected array $numericFields = [
        // Producción fresca
        'cosch_kg_fresca_carton',
        'cosch_kg_fresca_tubo',
        'cosch_kg_fresca_malla',
        'cosch_kg_fresca_losa',

        // Producción seca
        'cosch_kg_seca_carton',
        'cosch_kg_seca_tubo',
        'cosch_kg_seca_malla',
        'cosch_kg_seca_losa',
        'cosch_kg_seca_venta_madre',

        // Totales / factores
        'cosch_factor_fs_carton',
        'cosch_factor_fs_tubo',
        'cosch_factor_fs_malla',
        'cosch_factor_fs_losa',
        'cosch_total_cosecha',
        'cosch_total_campania',

        // Otros numéricos existentes
        'area',
        'tipo_cambio',
        'pencas_x_hectarea',
    ];

    /**
     * Crear o actualizar una campaña.
     *
     * @param array      $data
     * @param int|null   $campaniaId
     * @return CampoCampania
     * @throws \Exception
     */
    public function guardar(array $data, ?int $campaniaId = null): CampoCampania
    {
        return DB::transaction(function () use ($data, $campaniaId) {

            $campo = $data['campo'];
            $fechaInicio = Carbon::parse($data['fecha_inicio']);

            // 1. Campaña anterior
            $campaniaAnterior = CampoCampania::where('campo', $campo)
                ->whereDate('fecha_inicio', '<', $fechaInicio)
                ->orderByDesc('fecha_inicio')
                ->first();

            if ($campaniaAnterior) {
                $campaniaAnterior->update([
                    'fecha_fin' => $fechaInicio->copy()->subDay(),
                ]);
            }

            // 2. Campaña posterior
            $campaniaPosterior = CampoCampania::where('campo', $campo)
                ->whereDate('fecha_inicio', '>', $fechaInicio)
                ->orderBy('fecha_inicio')
                ->first();

            if ($campaniaPosterior) {
                $data['fecha_fin'] = Carbon::parse($campaniaPosterior->fecha_inicio)->subDay();
            }

            // 3. Evitar duplicados (solo en creación)
            if (!$campaniaId) {
                $existe = CampoCampania::where('campo', $campo)
                    ->whereDate('fecha_inicio', $fechaInicio)
                    ->exists();

                if ($existe) {
                    throw new \Exception(
                        "Ya existe una campaña para el campo {$campo} con fecha {$fechaInicio->toDateString()}."
                    );
                }
            }

            // 4. Auditoría
            $data['usuario_modificador'] = Auth::id();

            foreach ($this->numericFields as $field) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = $data[$field] === '' ? null : $data[$field];
                }
            }

            // 5. Crear o actualizar
            if ($campaniaId) {
                $campania = CampoCampania::findOrFail($campaniaId);
                $campania->update($data);
            } else {
                $campania = CampoCampania::create($data);
            }

            return $campania;
        });
    }

    /**
     * Eliminar campaña (sin reglas de negocio complejas).
     */
    public function eliminar(int $campaniaId): void
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $campania->delete();
    }

    /**
     * Obtener campañas por campo.
     */
    public function listarPorCampo(string $campo)
    {
        return CampoCampania::where('campo', $campo)
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    /**
     * Buscar una campaña.
     */
    public function obtener(int $campaniaId): CampoCampania
    {
        return CampoCampania::findOrFail($campaniaId);
    }
}
