<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use DB;

class ConsolidarCostoManoObraServicio
{
    /**
     * Se mantiene para no romper el botón actual "Consolidar" por campaña.
     * Internamente ya delega al método por rango.
     */
    public function consolidarPlanilla(CampoCampania $campania): int
    {
        // Todo lo de esta campaña se regenera: si antes se consolidó con un rango más largo
        // (campaña superpuesta con la siguiente), no quedan filas sobrantes.
        ResumenCostoDiario::where('campania', $campania->nombre_campania)
            ->where('campo', $campania->campo)
            ->whereIn('origen_tipo', $this->resolverOrigenesTipo(null))
            ->delete();

        return $this->consolidarPorRango(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            self::finEfectivoCampania($campania)
        );
    }

    /**
     * Fin real de una campaña: su fecha_fin, pero nunca después del día anterior al inicio de la
     * siguiente campaña del mismo campo (manda la más reciente, como en campaniaVigenteEnFecha).
     * Sin esto, una campaña que quedó abierta y la nueva cubren las mismas fechas y cada tramo
     * se consolida dos veces. Null = abierta y sin campaña posterior (hasta hoy).
     */
    public static function finEfectivoCampania(CampoCampania $campania): ?string
    {
        $inicio = \Illuminate\Support\Carbon::parse($campania->fecha_inicio)->toDateString();

        $inicioSiguiente = CampoCampania::where('campo', $campania->campo)
            ->where('id', '<>', $campania->id)
            ->where('fecha_inicio', '>', $inicio)
            ->min('fecha_inicio');

        $fin = $campania->fecha_fin ? \Illuminate\Support\Carbon::parse($campania->fecha_fin)->toDateString() : null;
        if ($inicioSiguiente) {
            $antesDeSiguiente = \Illuminate\Support\Carbon::parse($inicioSiguiente)->subDay()->toDateString();
            $fin = $fin ? min($fin, $antesDeSiguiente) : $antesDeSiguiente;
        }

        return $fin;
    }
    /*
    public function consolidarPlanilla(CampoCampania $campania): int
    {
        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        DB::transaction(function () use ($campania, $filas) {
            // Solo se borra lo que este proceso sabe regenerar (planilla derivado de ella)
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', ['planilla'])
                ->delete();

            $ahora = now();
            $filasConTimestamps = collect($filas)->map(fn($f) => array_merge($f, [
                'tipo_cambio' => 1.0000,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]))->toArray();

            foreach (array_chunk($filasConTimestamps, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }*/
    /*se agregara en la nueva version soporte de tipos

    public function consolidarPorRango(string $nombreCampania, string $campo, string $fechaInicio, ?string $fechaFin = null): int
    {
        $fechaFinEfectiva = $fechaFin ?? now()->format('Y-m-d');

        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $nombreCampania,
            $campo,
            $fechaInicio,
            $fechaFinEfectiva
        );

        DB::transaction(function () use ($nombreCampania, $campo, $fechaInicio, $fechaFinEfectiva, $filas) {
            // Acotado por campo + rango, no solo por campaña — así un
            // reconsolidado parcial no borra lo ya consolidado fuera de ese rango.
            ResumenCostoDiario::where('campania', $nombreCampania)
                ->where('campo', $campo)
                ->whereIn('origen_tipo', ['planilla'])
                ->whereBetween('fecha', [$fechaInicio, $fechaFinEfectiva])
                ->delete();

            $ahora = now();
            $filasConTimestamps = collect($filas)->map(fn($f) => array_merge($f, [
                'tipo_cambio' => 1.0000,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]))->toArray();

            foreach (array_chunk($filasConTimestamps, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }*/
    public function consolidarPorRango(string $nombreCampania, string $campo, string $fechaInicio, ?string $fechaFin = null, ?array $tipos = null): int
    {
        $fechaFinEfectiva = $fechaFin ?? now()->format('Y-m-d');

        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $nombreCampania,
            $campo,
            $fechaInicio,
            $fechaFinEfectiva,
            $tipos
        );

        $origenesADelete = $this->resolverOrigenesTipo($tipos);

        DB::transaction(function () use ($nombreCampania, $campo, $fechaInicio, $fechaFinEfectiva, $filas, $origenesADelete) {
            ResumenCostoDiario::where('campania', $nombreCampania)
                ->where('campo', $campo)
                ->whereIn('origen_tipo', $origenesADelete) // ← solo borra lo del/los tipo(s) pedido(s)
                ->whereBetween('fecha', [$fechaInicio, $fechaFinEfectiva])
                ->delete();

            $ahora = now();
            $filasConTimestamps = collect($filas)->map(fn($f) => array_merge($f, [
                'tipo_cambio' => 1.0000,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]))->toArray();

            foreach (array_chunk($filasConTimestamps, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }
    private function resolverOrigenesTipo(?array $tipos): array
    {
        $mapa = [
            'planilla' => ['planilla', 'cuadrilla', 'cuadrilla_bono'], // planilla + riego + cuadrilla (detalle y bonos)
            'bono_productividad' => ['planilla_bono_productividad'], // nuevo
        ];

        if ($tipos === null) {
            return array_merge(...array_values($mapa));
        }

        $origenes = [];
        foreach ($tipos as $tipo) {
            $origenes = array_merge($origenes, $mapa[$tipo] ?? []);
        }

        return $origenes;
    }

    /*se añadira en la nueva version $tipos para ser mas selectivo
    public function consolidarPlanillaEnRango(string $fechaInicio, string $fechaFin, ?array $tipos = null): int
    {
        $campanias = CampoCampania::where('fecha_inicio', '<=', $fechaFin)
            ->where(function ($q) use ($fechaInicio) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio);
            })
            ->get();

        $totalFilas = 0;

        foreach ($campanias as $campania) {
            $finCampaniaReal = $campania->fecha_fin ?? now()->format('Y-m-d');

            // Recorte: nunca antes del inicio real de la campaña,
            // nunca después de su fin real (o "hoy" si sigue abierta)
            $inicioEfectivo = max($campania->fecha_inicio, $fechaInicio);
            $finEfectivo = min($finCampaniaReal, $fechaFin);

            if ($inicioEfectivo > $finEfectivo) {
                continue; // no hay traslape real
            }

            $totalFilas += $this->consolidarPorRango(
                $campania->nombre_campania,
                $campania->campo,
                $inicioEfectivo,
                $finEfectivo
            );
        }

        return $totalFilas;
    }*/
    /**
     * @param string|null $campo si se indica, solo se regenera ese campo (p. ej. al cambiar las fechas de una
     *                           campaña: los demás campos no cambian y rehacer toda la empresa tomaba minutos)
     */
    public function consolidarPlanillaEnRango(string $fechaInicio, string $fechaFin, ?array $tipos = null, ?string $campo = null): int
    {
        $campanias = CampoCampania::where('fecha_inicio', '<=', $fechaFin)
            ->where(function ($q) use ($fechaInicio) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio);
            })
            ->when($campo, fn($q) => $q->where('campo', $campo))
            ->get();

        $totalFilas = 0;

        // Se limpia TODO el periodo (de cualquier campaña) antes de regenerar: así desaparecen
        // filas duplicadas por campañas superpuestas de consolidaciones anteriores.
        ResumenCostoDiario::whereIn('origen_tipo', $this->resolverOrigenesTipo($tipos))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($campo, fn($q) => $q->where('campo', $campo))
            ->delete();

        $cubiertos = []; // campo => [[inicio, fin], ...] tramos del periodo que tienen campaña
        $conActividad = self::camposConActividad($fechaInicio, $fechaFin);
        if ($campo) {
            $conActividad = array_intersect_key($conActividad, [$campo => true]);
        }

        foreach ($campanias as $campania) {
            // Sin mano de obra en el rango: no genera filas (y sus tramos solo importan en campos con actividad)
            if (!isset($conActividad[$campania->campo])) {
                continue;
            }

            // Manda la campaña más reciente: una campaña abierta termina donde empieza la siguiente
            // (si no hay siguiente, cubre hasta el fin del periodo)
            $finCampaniaReal = self::finEfectivoCampania($campania) ?? $fechaFin;
            $inicioEfectivo = max(\Illuminate\Support\Carbon::parse($campania->fecha_inicio)->toDateString(), $fechaInicio);
            $finEfectivo = min($finCampaniaReal, $fechaFin);

            if ($inicioEfectivo > $finEfectivo) {
                continue;
            }

            $cubiertos[$campania->campo][] = [$inicioEfectivo, $finEfectivo];

            $totalFilas += $this->consolidarPorRango(
                $campania->nombre_campania,
                $campania->campo,
                $inicioEfectivo,
                $finEfectivo,
                $tipos
            );
        }

        // El reporte mensual es un encuadre: TODO costo debe estar, también el de campos sin
        // campaña (FDM siempre; otros campos en sus días sin campaña, marcados para revisar).
        foreach (array_keys($conActividad) as $campo) {
            foreach (self::tramosSinCampania($cubiertos[$campo] ?? [], $fechaInicio, $fechaFin) as [$ini, $fin]) {
                $totalFilas += $this->consolidarPorRango(self::campaniaSinCobertura($campo), $campo, $ini, $fin, $tipos);
            }
        }

        return $totalFilas;
    }

    /**
     * Campos con mano de obra en el rango (planilla, cuadrilla, riego, bonos). Los demás no generan
     * filas: saltarlos evita recorrer ~100 campos cuando se recalcula un solo día.
     *
     * @return array<string, true>
     */
    public static function camposConActividad(string $fechaInicio, string $fechaFin): array
    {
        $campos = DB::table('plan_detalles_horas as d')
            ->join('plan_registros_diarios as r', 'r.id', '=', 'd.plan_reg_dia_id')
            ->whereBetween('r.fecha', [$fechaInicio, $fechaFin])->distinct()->pluck('d.campo_nombre')
            ->merge(DB::table('cuad_detalles_horas as d')
                ->join('cuad_registros_diarios as r', 'r.id', '=', 'd.registro_diario_id')
                ->whereBetween('r.fecha', [$fechaInicio, $fechaFin])->distinct()->pluck('d.campo_nombre'))
            ->merge(DB::table('reg_registro_diario')->whereBetween('fecha', [$fechaInicio, $fechaFin])->distinct()->pluck('campo'))
            // Bonos (planilla y cuadrilla) se ubican por el campo de la actividad
            ->merge(DB::table('actividades')->whereBetween('fecha', [$fechaInicio, $fechaFin])->distinct()->pluck('campo'))
            ->filter()
            ->unique();

        return array_fill_keys($campos->all(), true);
    }

    /**
     * Nombre de "campaña" para costos de un campo en días sin campaña:
     * FDM (su costo se prorratea luego entre campañas activas) o SIN CAMPAÑA (revisar).
     */
    public static function campaniaSinCobertura(string $campo): string
    {
        return mb_strtoupper(trim($campo)) === 'FDM' ? 'FDM' : 'SIN CAMPAÑA';
    }

    /**
     * Tramos de [fechaInicio, fechaFin] que no cubre ninguno de los rangos dados.
     *
     * @param array<int, array{0:string,1:string}> $cubiertos
     * @return array<int, array{0:string,1:string}>
     */
    public static function tramosSinCampania(array $cubiertos, string $fechaInicio, string $fechaFin): array
    {
        usort($cubiertos, fn($a, $b) => strcmp($a[0], $b[0]));

        $libres = [];
        $cursor = $fechaInicio;
        foreach ($cubiertos as [$ini, $fin]) {
            if ($ini > $cursor) {
                $libres[] = [$cursor, \Illuminate\Support\Carbon::parse($ini)->subDay()->toDateString()];
            }
            if ($fin >= $cursor) {
                $cursor = \Illuminate\Support\Carbon::parse($fin)->addDay()->toDateString();
            }
        }
        if ($cursor <= $fechaFin) {
            $libres[] = [$cursor, $fechaFin];
        }

        return $libres;
    }
}
