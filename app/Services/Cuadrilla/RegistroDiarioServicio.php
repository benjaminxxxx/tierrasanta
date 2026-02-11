<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use Exception;

class RegistroDiarioServicio
{
    /**
     * Asigna un costo personalizado a un registro diario específico.
     * * @param int $cuadrilleroId
     * @param string $fecha (Y-m-d)
     * @param mixed $costo
     */
    public function asignarCostoPersonalizado(int $cuadrilleroId, string $fecha, $costo, $tramoLaboralId): void
    {
        // Convertir string vacío, espacios o valores no numéricos a null
        $grupoEnTramo = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $tramoLaboralId)
            ->whereHas('cuadrilleros', function ($q) use ($cuadrilleroId) {
                $q->where('cuadrillero_id', $cuadrilleroId);
            })->first();

        if (!$grupoEnTramo) {
            throw new Exception("No se encontró la relación o el grupo de este tramo");
        }

        $costoLimpio = (is_numeric($costo) && $costo >= 0) ? (float) $costo : null;
        $registroDiario = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
            ->where('fecha', $fecha)
            ->where('tramo_laboral_id', $tramoLaboralId)
            ->where('codigo_grupo',$grupoEnTramo->codigo_grupo)
            ->first();
        if ($registroDiario) {
            $registroDiario->update([
                'costo_personalizado_dia' => $costoLimpio
            ]);
        } else {

            CuadRegistroDiario::create([
                'cuadrillero_id' => $cuadrilleroId,
                'fecha' => $fecha,
                'costo_personalizado_dia' => $costoLimpio,
                'codigo_grupo' => $grupoEnTramo->codigo_grupo,
                'tramo_laboral_id' => $tramoLaboralId
            ]);
        }
    }
}