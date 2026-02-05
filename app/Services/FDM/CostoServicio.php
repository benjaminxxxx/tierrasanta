<?php

namespace App\Services\FDM;

use App\Models\CostoManoIndirecta;
use App\Models\CostoMensual;
use DB;

class CostoServicio
{
    public static function calcularCostoCuadrillaFDM($mes, $anio)
    {
        $totalCosto = CuadrillaFdmServicio::calcularGastosCuadrillaMensual($mes, $anio);
        $data = [
            'negro_cuadrillero_monto' => $totalCosto['total'],
            'negro_cuadrillero_bono' => $totalCosto['bono'],
            'negro_cuadrillero_file' => $totalCosto['file'],
        ];
        self::guardarCostoManoIndirecta($mes, $anio, $data);
    }
    public static function calcularCostoPlanillaFDM($mes, $anio)
    {
        $totalCosto = PlanillaFdmServicio::calcularGastosPlanillaMensual($mes, $anio);
        $data = [
            'blanco_planillero_monto' => $totalCosto['total'],
            'negro_planillero_monto' => $totalCosto['total'],
            'file' => $totalCosto['file'],
        ];

        self::guardarCostoManoIndirecta($mes, $anio, $data);
    }

    public static function calcularCostoMaquinariaFDM($mes, $anio)
    {
        $totalCosto = MaquinariaFdmServicio::generarReportePorMes($mes, $anio);
        $data = [
            'negro_maquinaria_monto' => $totalCosto['total'],
        ];

        self::guardarCostoManoIndirecta($mes, $anio, $data);
    }

    public static function calcularCostoMaquinariaSalidaFDM($mes, $anio)
    {
        $totalCosto = MaquinariaSalidaFdmServicio::generarReportePorMes($mes, $anio);
        $data = [
            'negro_maquinaria_salida_monto' => $totalCosto['total'],
        ];

        self::guardarCostoManoIndirecta($mes, $anio, $data);
    }

    public static function calcularCostoAdicionalFDM($mes, $anio)
    {
        $totalCosto = CostoAdicionalFdmServicio::generarReportePorMes($mes, $anio);
        $data = [
            'negro_costos_adicionales_monto' => $totalCosto['total'],
        ];

        self::guardarCostoManoIndirecta($mes, $anio, $data);
    }
    public static function guardarCostoManoIndirecta(int $mes, int $anio, array $data)
    {
        DB::transaction(function () use ($mes, $anio, $data) {


            // 1. Actualizar o insertar en CostoManoIndirecta con los campos válidos
            CostoManoIndirecta::updateOrCreate(
                ['mes' => $mes, 'anio' => $anio],
                $data
            );

            // 2. Calcular totales
            $totalManoIndirectaBlanco = CostoManoIndirecta::where('mes', $mes)
                ->where('anio', $anio)
                ->sum(DB::raw("
                COALESCE(blanco_cuadrillero_monto, 0) + 
                COALESCE(blanco_planillero_monto, 0) + 
                COALESCE(blanco_maquinaria_monto, 0) + 
                COALESCE(blanco_maquinaria_salida_monto, 0) + 
                COALESCE(blanco_costos_adicionales_monto, 0)
            "));

            $totalManoIndirectaNegro = CostoManoIndirecta::where('mes', $mes)
                ->where('anio', $anio)
                ->sum(DB::raw("
                COALESCE(negro_cuadrillero_monto, 0) + 
                COALESCE(negro_planillero_monto, 0) + 
                COALESCE(negro_maquinaria_monto, 0) + 
                COALESCE(negro_maquinaria_salida_monto, 0) + 
                COALESCE(negro_costos_adicionales_monto, 0)
            "));

            // 3. Actualizar CostoMensual
            CostoMensual::updateOrCreate(
                ['mes' => $mes, 'anio' => $anio],
                [
                    'operativo_mano_obra_indirecta_blanco' => $totalManoIndirectaBlanco,
                    'operativo_mano_obra_indirecta_negro' => $totalManoIndirectaNegro
                ]
            );
        });
    }
}
