<?php

namespace App\Services\Campania\Data;

use App\Models\ResumenCostoDiario;

class DataReporteCampoServicio
{
    public function generarPlanillerosPor(string $campania, string $campo): array
    {
        $registros = ResumenCostoDiario::where('campania', $campania)
            ->where('campo', $campo)
            ->where('origen_tipo', 'planilla')
            ->orderBy('fecha', 'asc')
            ->get();

        return $registros->map(fn($row) => $this->mapearFilaManoObra($row, 'Planilla'))->toArray();
    }

    /**
     * Gastos generales (Costo Fijo + Costo Operativo) consolidados en la tabla maestra.
     * Se leen igual que planilla — la única diferencia es que casi todas las
     * columnas de mano de obra e insumo quedan vacías, ya que son montos
     * configurados a nivel mensual, no ligados a un trabajador o compra puntual.
     */
    public function generarGastosGeneralesPor(string $campania, string $campo): array
    {
        $registros = ResumenCostoDiario::where('campania', $campania)
            ->where('campo', $campo)
            ->whereIn('origen_tipo', ['costo_fijo', 'costo_operativo'])
            ->orderBy('fecha', 'asc')
            ->get();

        return $registros->map(function ($row) {
            $etiqueta = $row->origen_tipo === 'costo_fijo' ? 'Costo Fijo' : 'Costo Operativo';
            return $this->mapearFilaManoObra($row, $etiqueta);
        })->toArray();
    }
    public function generarMaquinariaPor(string $campania, string $campo): array
    {
        $registros = ResumenCostoDiario::where('campania', $campania)
            ->where('campo', $campo)
            ->where('origen_tipo', 'maquinaria')
            ->orderBy('fecha', 'asc')
            ->get();

        return $registros->map(function ($row) {
            return [
                'fecha' => $row->fecha ? $row->fecha->format('Y-m-d') : null,
                'campania' => $row->campania,
                'campo' => $row->campo,
                'tipo_gasto' => 'Maquinaria',
                'detalle_labor' => $row->labor_nombre ?? '-',
                'trabajador' => $row->trabajador ?? '-',
                'horas' => $row->horas !== null ? (float) $row->horas : null,
                'cantidad_jornales' => null,
                'cantidad' => $row->cantidad_insumo !== null ? (float) $row->cantidad_insumo : null,
                'proveedor' => null,
                'n_documento' => null,
                'costo' => (float) $row->costo_total,
                'observacion' => $row->observacion,
            ];
        })->toArray();
    }
    public function generarInsumosPor(string $campania, string $campo): array
    {
        $registros = ResumenCostoDiario::where('campania', $campania)
            ->where('campo', $campo)
            ->whereIn('origen_tipo', ['fertilizante', 'pesticida'])
            ->orderBy('fecha', 'asc')
            ->get();

        return $registros->map(function ($row) {
            return [
                'fecha' => $row->fecha ? $row->fecha->format('Y-m-d') : null,
                'campania' => $row->campania,
                'campo' => $row->campo,
                'tipo_gasto' => ucfirst($row->origen_tipo),
                'detalle_labor' => $row->insumo_nombre ?? '-',
                'trabajador' => null,
                'horas' => null,
                'cantidad_jornales' => null,
                'cantidad' => $row->cantidad_insumo !== null ? (float) $row->cantidad_insumo : null,
                'proveedor' => $row->tienda_comercial,
                'n_documento' => $this->armarDocumento($row->orden_compra, $row->factura),
                'costo' => (float) $row->costo_total,
                'observacion' => $row->observacion,
            ];
        })->toArray();
    }

    private function armarDocumento(?string $ordenCompra, ?string $factura): ?string
    {
        $partes = [];
        if ($ordenCompra)
            $partes[] = "OC-{$ordenCompra}";
        if ($factura)
            $partes[] = "F-{$factura}";
        return !empty($partes) ? implode(' / ', $partes) : null;
    }
    private function mapearFilaManoObra(ResumenCostoDiario $row, string $tipoGasto): array
    {
        return [
            'fecha' => $row->fecha ? $row->fecha->format('Y-m-d') : null,
            'campania' => $row->campania,
            'campo' => $row->campo,
            'tipo_gasto' => $tipoGasto,
            'detalle_labor' => $row->labor_nombre ?? '-',
            'trabajador' => $row->trabajador ?? '-',
            'horas' => $row->horas !== null ? (float) $row->horas : null,
            'cantidad_jornales' => $row->cantidad_jornales !== null ? (float) $row->cantidad_jornales : null,
            'cantidad' => null,
            'proveedor' => null,
            'n_documento' => null,
            'costo' => (float) $row->costo_total,
            'observacion' => $row->observacion,
        ];
    }
}