<?php

namespace App\Services\Campania\Data;

use App\Models\CostoMensualDistribucion;
use App\Support\CalculoHelper;

class DataCostoServicio
{
    public function generarCostoPor($campaniaId)
    {
        $costosMensuales = CostoMensualDistribucion::with(['campania', 'costoMensual'])
            ->where('campo_campania_id', $campaniaId)
            ->get();

        $data = [];
        foreach ($costosMensuales as $costoMensual) {
            $fecha = CalculoHelper::obtenerFechaFinalActiva(
                $costoMensual->anio,
                $costoMensual->mes,
                $costoMensual->campania->fecha_inicio,
                $costoMensual->campania->fecha_fin
            );

            $data[] = [
                'fecha' => $fecha,
                'costo_fijo' => 'COSTO ADMINISTRATIVO',
                'costo_fijo_costo' => $costoMensual->fijo_administrativo,
            ];
            $data[] = [
                'fecha' => $fecha,
                'costo_fijo' => 'COSTO FINANCIERO',
                'costo_fijo_costo' => $costoMensual->fijo_financiero,
            ];
            $data[] = [
                'fecha' => $fecha,
                'costo_fijo' => 'GASTOS OFICINA',
                'costo_fijo_costo' => $costoMensual->fijo_gastos_oficina,
            ];
            $data[] = [
                'fecha' => $fecha,
                'costo_fijo' => 'COSTO TERRENO',
                'costo_fijo_costo' => $costoMensual->fijo_costo_terreno,
            ];
            $data[] = [
                'fecha' => $fecha,
                'costo_fijo' => 'DEPRECIACIONES',
                'costo_fijo_costo' => $costoMensual->fijo_depreciaciones,
            ];

            $data[] = [
                'fecha' => $fecha,
                'costo_operativo' => 'SERVICIOS FUNDO',
                'costo_operativo_costo' => $costoMensual->operativo_servicios_fundo,
            ];
            $data[] = [
                'fecha' => $fecha,
                'costo_operativo' => 'MANO DE OBRA INDIRECTA',
                'costo_operativo_costo' => $costoMensual->operativo_mano_obra_indirecta,
            ];
        }
        return $data;
    }
}
