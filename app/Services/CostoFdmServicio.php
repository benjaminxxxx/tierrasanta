<?php

namespace App\Services;

use App\Models\CostoFdmMensual;
use App\Models\CostoManoIndirecta;
use App\Models\CostoMensual;
use Illuminate\Support\Facades\DB;
use Exception;

class CostoFdmServicio
{

    /**
     * Guarda o actualiza los costos de mano indirecta para un mes y año específicos.
     * 
     * @param int $mes
     * @param int $anio
     * @param array $datos
     * @return array
     */
    public static function guardar(int $mes, int $anio, array $datos)
    {
        try {

            if (!is_array($datos) || empty($datos)) {
                throw new Exception("Datos inválidos para guardar los costos.");
            }

            // Validar que los datos tengan el mismo mes y año
            foreach ($datos as $dato) {
                $fecha = \Carbon\Carbon::parse($dato['fecha']);
                if ($fecha->format('m') != $mes || $fecha->format('Y') != $anio) {
                    throw new Exception("Todos los registros deben pertenecer al mes y año seleccionados.");
                }
            }

            DB::beginTransaction();

            // Eliminar registros previos del mismo mes y año
            CostoFdmMensual::whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->delete();

            // Agregar los nuevos registros
            $nuevosCostos = [];
            $montoTotalBlanco = 0;
            $montoTotalNegro = 0;
            foreach ($datos as $dato) {
                $monto_blanco = (float) $dato['monto_blanco'];
                $monto_negro = (float) $dato['monto_negro'];
                $nuevosCostos[] = [
                    'fecha' => $dato['fecha'],
                    'destinatario' => $dato['destinatario'],
                    'descripcion' => $dato['descripcion'],
                    'monto_blanco' => $monto_blanco,
                    'monto_negro' => $monto_negro,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $montoTotalBlanco += $monto_blanco;
                $montoTotalNegro += $monto_negro;
            }

            // Inserta los datos en un solo query para mejor rendimiento
            CostoFdmMensual::insert($nuevosCostos);

            self::guardarCostoManoIndirecta($mes, $anio, 'blanco_costos_adicionales_monto', $montoTotalBlanco);
            self::guardarCostoManoIndirecta($mes, $anio, 'negro_costos_adicionales_monto', $montoTotalNegro);


            DB::commit();
            return [
                'costo_adicional_blanco' => $montoTotalBlanco,
                'costo_adicional_negro' => $montoTotalNegro,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());

        }
    }
    /**
     * Guarda un costo de mano indirecta y actualiza el total en costos mensuales.
     *
     * @param int $mes
     * @param int $anio
     * @param string $campo
     * @param float $montoTotal
     * @return void
     */
    public static function guardarCostoManoIndirecta(int $mes, int $anio, array $data)
    {
        DB::transaction(function () use ($mes, $anio, $data) {
            // Definir los campos válidos que se pueden actualizar
            $camposValidos = [
                'blanco_cuadrillero_monto',
                'negro_cuadrillero_monto',
                

                'blanco_planillero_monto',
                'negro_planillero_monto',

                'blanco_maquinaria_monto',
                'negro_maquinaria_monto',

                'blanco_maquinaria_salida_monto',
                'negro_maquinaria_salida_monto',

                'blanco_costos_adicionales_monto',
                'negro_costos_adicionales_monto',

                'negro_cuadrillero_file',
                'negro_planillero_file',

                // puedes agregar otros campos si los permites
            ];

            // Validar los campos recibidos
            foreach ($data as $campo => $valor) {
                if (!in_array($campo, $camposValidos)) {
                    throw new Exception("El campo '$campo' no es válido para actualizar.");
                }
            }
            
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
