<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use Exception;

class RegistroDiarioServicio
{
    /*
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
    }*/
    /**
     * Asigna un costo personalizado a un registro diario específico.
     *
     * @param int $cuadrilleroId
     * @param string $codigoGrupo
     * @param string $fecha (Y-m-d)
     * @param mixed $costo
     * @param int $tramoLaboralId
     */
    public function asignarCostoPersonalizado(
        int $cuadrilleroId,
        string $codigoGrupo,
        string $fecha,
        $costo,
        int $tramoLaboralId
    ): void {
        $costoLimpio = (is_numeric($costo) && $costo >= 0) ? (float) $costo : null;

        // 1. Buscar si ya existe el registro diario para este cuadrillero, fecha, grupo y tramo
        $registroDiario = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
            ->where('fecha', $fecha)
            ->where('tramo_laboral_id', $tramoLaboralId)
            ->where('codigo_grupo', $codigoGrupo)
            ->first();

        if ($registroDiario) {
            // Si el registro ya existe (por ejemplo, tiene horas), actualizamos el costo
            $registroDiario->update([
                'costo_personalizado_dia' => $costoLimpio
            ]);
            return;
        }

        // 2. Si NO existe el registro diario y tampoco hay costo ingresado, no insertamos nada en blanco
        if (is_null($costoLimpio)) {
            return;
        }

        // 3. Si es un registro NUEVO con costo personalizado, obtenemos la FK tramo_cuadrillero_id obligatoria
        $tramoCuadrillero = CuadTramoLaboralCuadrillero::whereHas('tramoLaboralGrupal', function ($q) use ($tramoLaboralId, $codigoGrupo) {
            $q->where('cuad_tramo_laboral_id', $tramoLaboralId)
                ->where('codigo_grupo', $codigoGrupo);
        })
            ->where('cuadrillero_id', $cuadrilleroId)
            ->first();

        if (!$tramoCuadrillero) {
            throw new Exception("No se encontró la asignación del cuadrillero ID {$cuadrilleroId} en el grupo {$codigoGrupo} para este tramo.");
        }

        // 4. Crear el registro diario garantizando el tramo_cuadrillero_id (NOT NULL)
        CuadRegistroDiario::create([
            'tramo_cuadrillero_id' => $tramoCuadrillero->id, // 👈 Llave obligatoria NOT NULL
            'cuadrillero_id' => $cuadrilleroId,
            'fecha' => $fecha,
            'costo_personalizado_dia' => $costoLimpio,
            'codigo_grupo' => $codigoGrupo,
            'tramo_laboral_id' => $tramoLaboralId,
            'costo_dia' => 0,
        ]);
    }
}