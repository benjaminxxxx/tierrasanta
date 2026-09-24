<?php
namespace App\Services\GestionCuadrilla;

use App\Models\GastoAdicionalPorGrupoCuadrilla;

class PagoGastoAdicionalServicio
{
    public static function calcularGastosAdicionales(
        string $tipoPeriodo,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $modalidadPago = strtolower($tipoPeriodo);

        $gastos = GastoAdicionalPorGrupoCuadrilla::query()
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->where('esta_pagado', false)
            ->whereHas('grupo', fn ($q) => $q->where('modalidad_pago', $modalidadPago))
            ->with('grupo')
            ->get();

        $detallesPorClave = [];
        $granTotal = 0;

        foreach ($gastos as $gasto) {
            $clave = $gasto->descripcion . '|' . $gasto->codigo_grupo;

            if (!isset($detallesPorClave[$clave])) {
                $detallesPorClave[$clave] = [
                    'tipo_gasto' => $gasto->descripcion,
                    'nombre_grupo' => $gasto->grupo->nombre ?? $gasto->codigo_grupo,
                    'monto' => 0,
                    'gasto_ids' => [],
                ];
            }

            $detallesPorClave[$clave]['monto'] += (float) $gasto->monto;
            $detallesPorClave[$clave]['gasto_ids'][] = $gasto->id;
            $granTotal += (float) $gasto->monto;
        }

        $detalles = collect($detallesPorClave)
            ->map(fn ($d) => [
                'nro_documento' => '',
                'descripcion' => DescripcionDetallePagoServicio::generarRango(
                    $d['tipo_gasto'],
                    $d['nombre_grupo'],
                    $fechaInicio,
                    $fechaFin
                ),
                'monto' => round($d['monto'], 2),
                'gasto_ids' => array_values(array_unique($d['gasto_ids'])),
            ])
            ->values()
            ->all();

        return [
            'detalles' => $detalles,
            'gran_total' => round($granTotal, 2),
        ];
    }
}