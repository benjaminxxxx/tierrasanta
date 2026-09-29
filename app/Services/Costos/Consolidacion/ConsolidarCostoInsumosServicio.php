<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Almacen\Kardex\KardexActualizacionServicio;
use App\Services\Costos\Data\DataInsumoServicio;
use Carbon\Carbon;
use DB;

/**
 * Pasa a resumen_costo_diarios las salidas de productos con grupo operativo
 * fertilizante/pesticida (origen_tipo = grupo operativo).
 *
 * El costo de cada salida lo fija el kardex; antes de leerlo se verifica que los kardex
 * involucrados estén al día (fecha de corte) y se regeneran si hace falta.
 */
class ConsolidarCostoInsumosServicio
{
    public const ORIGENES = ['fertilizante', 'pesticida'];

    public function __construct(
        private DataInsumoServicio $dataInsumo,
        private KardexActualizacionServicio $kardexActualizacion,
    ) {
    }

    /**
     * Botón "Consolidar campaña" de /campo/costos.
     */
    public function consolidarInsumos(CampoCampania $campania): int
    {
        // Manda la campaña más reciente: si esta quedó abierta, termina donde empieza la siguiente
        $fechaFin = ConsolidarCostoManoObraServicio::finEfectivoCampania($campania) ?? now()->format('Y-m-d');

        $this->kardexActualizacion->asegurarActualizados(
            $this->dataInsumo->salidasInsumos($campania->fecha_inicio, $fechaFin, $campania->campo)
        );

        // Se vuelven a leer: la regeneración del kardex actualiza total_costo de las salidas.
        $filas = $this->dataInsumo->salidasInsumos($campania->fecha_inicio, $fechaFin, $campania->campo)
            ->map(fn($salida) => $this->dataInsumo->mapearSalidaInsumo($salida, $campania->nombre_campania))
            ->all();

        DB::transaction(function () use ($campania, $filas) {
            // Borra ambos tipos derivados de esta fuente en una sola pasada
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', self::ORIGENES)
                ->delete();

            $this->insertar($filas);
        });

        return count($filas);
    }

    /**
     * Botón "generarMes" de la consolidación anual: reemplaza todo lo del rango.
     * Las salidas cuyo campo no tiene campaña en esa fecha se consolidan como 'FDM'
     * o 'SIN CAMPAÑA' (ver ConsolidarCostoManoObraServicio::campaniaSinCobertura).
     */
    public function consolidarEnRango(string $fechaInicio, string $fechaFin): int
    {
        $this->kardexActualizacion->asegurarActualizados(
            $this->dataInsumo->salidasInsumos($fechaInicio, $fechaFin)
        );

        $salidas = $this->dataInsumo->salidasInsumos($fechaInicio, $fechaFin);

        $campanias = CampoCampania::whereIn('campo', $salidas->pluck('campo_nombre')->unique())
            ->where('fecha_inicio', '<=', $fechaFin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio))
            ->orderByDesc('fecha_inicio')
            ->get()
            ->groupBy('campo');

        $ymd = fn($f) => $f ? Carbon::parse($f)->format('Y-m-d') : null;

        $filas = $salidas
            ->map(function ($salida) use ($campanias, $ymd) {
                $fecha = $salida->fecha_reporte->format('Y-m-d');
                // Misma regla que Campo::campaniaVigenteEnFecha (la más reciente vigente)
                $campania = ($campanias[$salida->campo_nombre] ?? collect())->first(
                    fn($c) => $ymd($c->fecha_inicio) <= $fecha && (!$c->fecha_fin || $ymd($c->fecha_fin) >= $fecha)
                );

                // Sin campaña también entra (FDM / SIN CAMPAÑA): el reporte mensual es un encuadre
                $nombreCampania = $campania
                    ? $campania->nombre_campania
                    : ConsolidarCostoManoObraServicio::campaniaSinCobertura((string) $salida->campo_nombre);

                return $this->dataInsumo->mapearSalidaInsumo($salida, $nombreCampania);
            })
            ->values()
            ->all();

        DB::transaction(function () use ($fechaInicio, $fechaFin, $filas) {
            ResumenCostoDiario::whereIn('origen_tipo', self::ORIGENES)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->delete();

            $this->insertar($filas);
        });

        return count($filas);
    }

    /**
     * Costo de todas las salidas del rango según el kardex (incluye las de campos sin campaña),
     * agrupado por grupo operativo: ['fertilizante' => x, 'pesticida' => y].
     */
    public function costoSalidasEnRango(string $fechaInicio, string $fechaFin): array
    {
        $salidas = $this->dataInsumo->salidasInsumos($fechaInicio, $fechaFin);

        $totales = array_fill_keys(self::ORIGENES, 0.0);
        foreach ($salidas as $salida) {
            $grupo = $salida->producto->categoria->grupo_operativo;
            $totales[$grupo] += (float) $salida->total_costo;
        }

        return $totales;
    }

    private function insertar(array $filas): void
    {
        $ahora = now();

        $filasParaInsertar = collect($filas)->map(fn($f) => [
            'campania' => $f['campania'],
            'fecha' => $f['fecha'],
            'origen_tipo' => $f['grupo_operativo'], // 'fertilizante' o 'pesticida', dinámico
            'origen_id' => $f['origen_id'],
            'campo' => $f['campo'],
            'labor' => null,
            'labor_nombre' => $f['detalle_labor'],
            'trabajador' => null,
            'cuadrilla_grupo_id' => null,
            'tipo_cambio' => 1.0000,
            'minutos' => null,
            'cantidad_jornales' => null,
            'insumo_nombre' => $f['detalle_labor'],
            'orden_compra' => null, // ya viene combinado en n_documento a nivel de lectura
            'factura' => null,
            'tienda_comercial' => $f['proveedor'],
            'cantidad_insumo' => $f['cantidad'],
            'costo_total' => $f['costo'],
            'observacion' => $f['n_documento'], // guardamos el documento combinado aquí temporalmente
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ])->toArray();

        foreach (array_chunk($filasParaInsertar, 500) as $chunk) {
            ResumenCostoDiario::insert($chunk);
        }
    }
}
