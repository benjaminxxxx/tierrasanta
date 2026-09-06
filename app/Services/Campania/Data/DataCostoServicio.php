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

            $campania = $costoMensual->campania->nombre_campania ?? null;
            $campo = $costoMensual->campania->campo ?? null;

            $conceptosFijos = [
                'COSTO ADMINISTRATIVO' => $costoMensual->fijo_administrativo,
                'COSTO FINANCIERO' => $costoMensual->fijo_financiero,
                'GASTOS OFICINA' => $costoMensual->fijo_gastos_oficina,
                'COSTO TERRENO' => $costoMensual->fijo_costo_terreno,
                'DEPRECIACIONES' => $costoMensual->fijo_depreciaciones,
            ];

            foreach ($conceptosFijos as $concepto => $monto) {
                if (is_null($monto)) {
                    continue;
                }

                $data[] = $this->armarFila($fecha, $campania, $campo, $costoMensual->id, 'Costo Fijo', $concepto, $monto);
            }

            $conceptosOperativos = [
                'SERVICIOS FUNDO' => $costoMensual->operativo_servicios_fundo,
                'MANO DE OBRA INDIRECTA' => $costoMensual->operativo_mano_obra_indirecta,
            ];

            foreach ($conceptosOperativos as $concepto => $monto) {
                if (is_null($monto)) {
                    continue;
                }

                $data[] = $this->armarFila($fecha, $campania, $campo, $costoMensual->id, 'Costo Operativo', $concepto, $monto);
            }
        }

        return $data;
    }

    private function armarFila(?string $fecha, ?string $campania, ?string $campo, int $origenId, string $tipoGasto, string $detalle, float $costo): array
    {
        return [
            'fecha' => $fecha,
            'campania' => $campania,
            'campo' => $campo,
            'origen_id' => $origenId,
            'tipo_gasto' => $tipoGasto,
            'detalle_labor' => $detalle,
            'trabajador' => null,
            'horas' => null,
            'cantidad_jornales' => null,
            'cantidad' => null,
            'proveedor' => null,
            'n_documento' => null,
            'costo' => (float) $costo,
            'observacion' => null,
        ];
    }
}