<?php
namespace App\Services\Contabilidad;

use App\Models\CostoMensualDistribucion;
use Illuminate\Support\Facades\DB;

class DistribucionCostoMensualServicio
{
    public function guardar(
        int $costoMensualId,
        array $distribucionCalculada
    ): void {
        DB::transaction(function () use ($costoMensualId, $distribucionCalculada) {

            // 1. Limpiar distribuciones previas
            CostoMensualDistribucion::where('costo_mensual_id', $costoMensualId)
                ->delete();

            // 2. Insertar nuevas
            foreach ($distribucionCalculada as $fila) {

                // Si todo es cero, NO persistimos
                if ($this->filaEsCero($fila)) {
                    continue;
                }

                CostoMensualDistribucion::create([
                    'costo_mensual_id' => $costoMensualId,
                    'campo_campania_id' => $fila['campania_id'],

                    'anio' => $fila['anio'],
                    'mes' => $fila['mes'],
                    'dias_mes' => $fila['dias_mes'],
                    'dias_activos' => $fila['dias_activos'],
                    'porcentaje' => $fila['porcentaje'],

                    'fijo_administrativo' => $fila['monto_fijo_administrativo'],
                    'fijo_financiero' => $fila['monto_fijo_financiero'],
                    'fijo_gastos_oficina' => $fila['monto_fijo_gastos_oficina'],
                    'fijo_depreciaciones' => $fila['monto_fijo_depreciaciones'],
                    'fijo_costo_terreno' => $fila['monto_fijo_costo_terreno'],

                    'operativo_servicios_fundo' => $fila['monto_operativo_servicios_fundo'],
                    'operativo_mano_obra_indirecta' => $fila['monto_operativo_mano_obra_indirecta'],
                ]);
            }
        });
    }

    private function filaEsCero(array $fila): bool
    {
        return
            $fila['monto_fijo_administrativo'] == 0 &&
            $fila['monto_fijo_financiero'] == 0 &&
            $fila['monto_fijo_gastos_oficina'] == 0 &&
            $fila['monto_fijo_depreciaciones'] == 0 &&
            $fila['monto_fijo_costo_terreno'] == 0 &&
            $fila['monto_operativo_servicios_fundo'] == 0 &&
            $fila['monto_operativo_mano_obra_indirecta'] == 0;
    }
}
