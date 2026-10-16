<?php
namespace App\Services\Cuadrilla;

use App\Models\CuadResumenPorTramo;
use App\Models\CuadTramoLaboral;
use Illuminate\Support\Facades\DB;

class ResumenTramoServicio
{
    public function __construct(protected ResumenTramoInformativoServicio $calculador)
    {
    }

    // Reemplaza el upsert manual: borra todo lo del tramo y reconstruye desde cero,
    // consultando directamente el estado real (esta_pagado/desglose_detalle_id).
    public function regenerar($tramoLaboralId): void
    {
        $filas = $this->calculador->generar($tramoLaboralId);

        DB::transaction(function () use ($tramoLaboralId, $filas) {
            CuadResumenPorTramo::where('tramo_id', $tramoLaboralId)->delete();

            $generadoEn = now();
            $orden = 0;
            //dd($filas);

            foreach ($filas as $fila) {
                /*
                if($fila['condicion']!='Pendiente'){
dd($fila);
                }*/
                CuadResumenPorTramo::create([
                    ...$fila,
                    'tramo_id' => $tramoLaboralId,
                    'generado_en' => $generadoEn,
                    'orden' => ++$orden,
                ]);
            }
        });
    }

    public function obtenerResumen(int $tramoLaboralId)
    {
        return CuadResumenPorTramo::where('tramo_id', $tramoLaboralId)->orderBy('orden')->get();
    }
}