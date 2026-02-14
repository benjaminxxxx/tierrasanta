<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanDescuentoSp;
use App\Models\PlanDescuentoSpHistorico;
use App\Models\PlanEmpleado;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanillaDescuentoServicio
{
     public static function calcularDescuentoEmpleado(int $edad, $plan_sp_codigo, $esta_jubilado, $descuentosAgrupados): array {

        $codigo = $plan_sp_codigo;
        $esJubilado = $esta_jubilado;

        if ($codigo === '') {
            throw new Exception("El trabajador no tiene un código AFP/ONP válido.");
        }

        if (!isset($descuentosAgrupados[$codigo])) {
            throw new Exception("No existe configuración de descuento para el código: {$codigo}");
        }

        $descuento = $descuentosAgrupados[$codigo];

        // 1) Pensionista → no aporta nada
        if ($esJubilado) {
            return [
                'porcentaje' => 0,
                'motivo'     => 'POR SER PENSIONISTA NO TIENE RETENCIÓN'
            ];
        }

        // 2) Mayor de 65 → usa porcentaje_65
        if ($edad > 65) {

            if ($codigo === 'SNP') {
                return [
                    'porcentaje' => (float) $descuento['porcentaje_65'],
                    'motivo'     => 'MAYOR DE 65 EXONERADO DE PRIMA (ONP no tiene prima)'
                ];
            }

            return [
                'porcentaje' => (float) $descuento['porcentaje_65'],
                'motivo'     => 'MAYOR DE 65 EXONERADO DE PRIMA'
            ];
        }

        // 3) Caso común
        return [
            'porcentaje' => (float) $descuento['porcentaje'],
            'motivo'     => ''
        ];
    }
    /**
     * Obtiene TODOS los descuentos AFP/ONP vigentes en una sola consulta.
     */
    public static function obtenerDescuentos(int $mes, int $anio): array
    {
        $codigos = [
            'HAB F',
            'INT F',
            'PRI F',
            'PRO F',
            'SNP',
            'HAB M',
            'INT M',
            'PRI M',
            'PRO M'
        ];

        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();

        // 1 sola consulta SQL para traer los históricos relevantes
        $historiales = PlanDescuentoSpHistorico::whereIn('descuento_codigo', $codigos)
            ->where('fecha_inicio', '<=', $fechaReferencia)
            ->with('descuentoSp')
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->groupBy('descuento_codigo');

        $descuentosAgrupados = [];

        foreach ($codigos as $codigo) {

            // Verificación del código
            if (!isset($historiales[$codigo])) {
                throw new Exception("No se encontró un descuento para el código: $codigo");
            }

            // Elegir SOLO el registro más reciente (primer elemento del groupBy)
            $descuento = $historiales[$codigo]->first();

            $descuentosAgrupados[$codigo] = $descuento->toArray();
        }

        return $descuentosAgrupados;
    }
}