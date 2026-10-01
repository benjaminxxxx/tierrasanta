<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Models\ServicioCampo;
use App\Models\ServicioCampoDetalle;
use DB;

/**
 * Pasa los servicios externos en campo (servicios_campo_detalles) a resumen_costo_diarios
 * con origen_tipo = 'servicio_campo'. El costo ya es el total pagado prorrateado (IGV incluido).
 *
 * Los detalles sin campaña (campaña eliminada) no se pueden consolidar: quedan como
 * diferencia entre lo pagado y lo calculado en el reporte mensual.
 */
class ConsolidarCostoServiciosCampoServicio
{
    public const ORIGEN = 'servicio_campo';

    /**
     * Botón "Consolidar campaña" de /campo/costos.
     */
    public function consolidarServicios(CampoCampania $campania): int
    {
        $detalles = ServicioCampoDetalle::with('servicio')
            ->where('campania_id', $campania->id)
            ->get();

        return DB::transaction(function () use ($campania, $detalles) {
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->where('campo', $campania->campo) // el nombre (T.2025) se repite en todos los campos
                ->where('origen_tipo', self::ORIGEN)
                ->delete();

            return $this->insertar($detalles, [$campania->id => $campania->nombre_campania]);
        });
    }

    /**
     * Botón "generarMes" de la consolidación anual: reemplaza todo lo del rango.
     */
    /**
     * @param string|null $campo solo los servicios de ese campo (regenerar los costos de una campaña sin tocar el resto)
     */
    public function consolidarEnRango(string $fechaInicio, string $fechaFin, ?string $campo = null): int
    {
        $detalles = ServicioCampoDetalle::with('servicio')
            ->whereNotNull('campania_id')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($campo, fn($q) => $q->where('campo', $campo))
            ->get();

        $nombresCampania = CampoCampania::whereIn('id', $detalles->pluck('campania_id')->unique())
            ->pluck('nombre_campania', 'id')
            ->all();

        return DB::transaction(function () use ($fechaInicio, $fechaFin, $detalles, $nombresCampania, $campo) {
            ResumenCostoDiario::where('origen_tipo', self::ORIGEN)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->when($campo, fn($q) => $q->where('campo', $campo))
                ->delete();

            return $this->insertar($detalles, $nombresCampania);
        });
    }

    /**
     * Costo pagado del rango según la tabla fuente (incluye detalles sin campaña),
     * para compararlo con lo consolidado por campo.
     */
    public function costoPagadoEnRango(string $fechaInicio, string $fechaFin): float
    {
        return (float) ServicioCampoDetalle::whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('costo');
    }

    private function insertar($detalles, array $nombresCampania): int
    {
        $ahora = now();

        $filas = $detalles
            ->filter(fn($d) => isset($nombresCampania[$d->campania_id]))
            ->map(function (ServicioCampoDetalle $d) use ($nombresCampania, $ahora) {
                $servicio = $d->servicio;
                $esHora = in_array(mb_strtolower(trim($servicio->unidad)), ['hora', 'horas', 'hr', 'h'], true);

                return [
                    'campania' => $nombresCampania[$d->campania_id],
                    'fecha' => $d->fecha->format('Y-m-d'),
                    'origen_tipo' => self::ORIGEN,
                    'origen_id' => $d->id,
                    'campo' => $d->campo,
                    'labor' => null,
                    'labor_nombre' => mb_substr($d->labor, 0, 150),
                    'trabajador' => null,
                    'cuadrilla_grupo_id' => null,
                    'tipo_cambio' => 1.0000,
                    'minutos' => $esHora ? (int) round((float) $d->cantidad * 60) : 0,
                    'cantidad_jornales' => null,
                    'insumo_nombre' => mb_substr($servicio->servicio, 0, 150),
                    'orden_compra' => null,
                    'factura' => $servicio->numero_comprobante,
                    'tienda_comercial' => null,
                    'cantidad_insumo' => $d->cantidad,
                    'costo_total' => $d->costo,
                    'observacion' => mb_substr(sprintf(
                        '%s %s × %s · %s (%s)',
                        rtrim(rtrim(number_format((float) $d->cantidad, 3, '.', ''), '0'), '.'),
                        $servicio->unidad,
                        number_format((float) $servicio->costo_unitario, 2, '.', ''),
                        ServicioCampo::COMPROBANTES[$servicio->tipo_comprobante] ?? $servicio->tipo_comprobante,
                        $servicio->tipo_costo
                    ), 0, 255),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            })
            ->values()
            ->all();

        foreach (array_chunk($filas, 500) as $chunk) {
            ResumenCostoDiario::insert($chunk);
        }

        return count($filas);
    }
}
