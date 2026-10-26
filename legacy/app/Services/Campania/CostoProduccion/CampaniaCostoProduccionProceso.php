<?php

namespace App\Services\Campania\CostoProduccion;

use App\Models\CampaniaCostoProduccion;
use App\Models\CampoCampania;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Guarda una versión de los costos de producción de una campaña: lee resumen_costo_diarios en ese momento y lo
 * congela junto con el área y el tipo de cambio de la campaña. Las versiones anteriores se conservan (historia).
 *
 * La BDD de costos debe estar al día: si se regenera después, se vuelve a consultar y queda una versión nueva.
 */
class CampaniaCostoProduccionProceso
{
    public function __construct(private CampaniaCostoProduccionConsulta $consulta)
    {
    }

    public function generar(int $campaniaId): CampaniaCostoProduccion
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $datos = $this->consulta->agrupar($campania);

        return DB::transaction(function () use ($campania, $datos) {
            $version = CampaniaCostoProduccion::create([
                'campo_campania_id' => $campania->id,
                'area' => $campania->area,
                'tipo_cambio' => $campania->tipo_cambio,
                'fecha_desde' => $datos['fecha_desde'],
                'fecha_hasta' => $datos['fecha_hasta'],
                'total_soles' => round(array_sum(array_column($datos['filas'], 'costo_soles')), 4),
                'filas_origen' => $datos['filas_origen'],
                'generado_por' => Auth::id(),
            ]);

            foreach ($datos['filas'] as $orden => $fila) {
                $version->detalles()->create($fila + ['orden' => $orden + 1]);
            }

            return $version;
        });
    }
}
