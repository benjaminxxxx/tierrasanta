<?php

namespace App\Services\Campania\Cosecha;

use App\Models\Actividad;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\Labores;
use App\Models\VentaCochinilla;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Estado de cosecha de campañas (solo lectura).
 *
 * Días de cosecha = fechas de los ingresos de cochinilla de la campaña (sin los de recuperación de infestadores,
 * ver CampaniaCosechaReglas::observacionesNoCosecha) + fechas con labores de cosecha
 * (CampaniaCosechaReglas::laboresCosecha) dentro del rango de la campaña. Como la cosecha dura varios días,
 * se agrupan en tandas (huecos de hasta GAP_DIAS) y se toma la tanda que contiene ingresos (o la primera).
 */
class CampaniaCosechaConsulta
{
    /** Hueco máximo entre días de una misma cosecha. */
    private const GAP_DIAS = 15;

    /**
     * @param iterable<CampoCampania> $campanias
     * @return array<int, array> estado por id de campaña (ver estadoVacio())
     */
    public function estados(iterable $campanias): array
    {
        $campanias = collect($campanias)->filter()->keyBy('id');
        if ($campanias->isEmpty()) {
            return [];
        }

        $hoy = Carbon::today();
        $ingresos = $this->ingresosPorCampania($campanias);
        $actividadesCosecha = $this->actividadesCosechaPorCampania($campanias, $hoy);
        $nombresLabor = null;

        $estados = [];
        foreach ($campanias as $id => $campania) {
            $estado = $this->estadoVacio();
            $delaCampania = $ingresos[$id] ?? collect();

            $diasIngreso = $delaCampania->map(fn($i) => Carbon::parse($i->fecha)->toDateString());
            $diasLabor = collect($actividadesCosecha[$id] ?? []);
            $tanda = $this->tandaDeCosecha($diasIngreso->all(), $diasLabor->all());

            if (!$tanda) {
                $estados[$id] = $estado;
                continue;
            }

            $primera = Carbon::parse($tanda[0]);
            $ultima = Carbon::parse(end($tanda));
            $abierta = $campania->fecha_fin === null;

            $estado['primera_cosecha'] = $primera;
            $estado['ultima_cosecha'] = $ultima;
            $estado['dias_cosecha'] = count($tanda);
            $estado['kg'] = round((float) $delaCampania->sum('total_kilos'), 2);
            $estado['es_mama'] = $delaCampania->contains(fn($i) => self::esMama($i));
            $estado['dias_desde_cosecha'] = (int) $ultima->diffInDays($hoy);
            $estado['fecha_cierre_sugerida'] = $ultima->copy();
            $estado['estado'] = $abierta && $estado['dias_desde_cosecha'] <= CampaniaCosechaReglas::DIAS_COSECHANDO
                ? 'cosechando'
                : 'cosechada';

            if ($abierta) {
                $posterior = $this->primeraActividadNueva($campania->campo, $ultima);
                if ($posterior) {
                    $nombresLabor ??= Labores::pluck('nombre_labor', 'codigo');
                    $estado['actividad_posterior'] = [
                        'fecha' => Carbon::parse($posterior->fecha),
                        'codigo' => $posterior->codigo_labor,
                        'labor' => $posterior->nombre_labor ?: ($nombresLabor[$posterior->codigo_labor] ?? $posterior->codigo_labor),
                    ];
                    $estado['recomendar_cierre'] = 'actividades';
                } elseif ($estado['dias_desde_cosecha'] >= CampaniaCosechaReglas::DIAS_SUGERIR_CIERRE) {
                    $estado['recomendar_cierre'] = 'tiempo';
                }
            }

            $estados[$id] = $estado;
        }

        return $estados;
    }

    public function estado(CampoCampania $campania): array
    {
        return $this->estados([$campania])[$campania->id] ?? $this->estadoVacio();
    }

    /**
     * Ventas hechas después del plazo de su cosecha (15 días, o 100 si la cochinilla fue para mamá).
     * La venta se relaciona con su cosecha por el ingreso de cochinilla (cochinilla_ingreso_id).
     *
     * @return Collection<int, array{venta: VentaCochinilla, ingreso: CochinillaIngreso, dias: int, limite: int, es_mama: bool}>
     */
    public function ventasFueraDePlazo(): Collection
    {
        return VentaCochinilla::query()
            ->whereNotNull('fecha_venta')
            ->whereNotNull('cochinilla_ingreso_id')
            ->with(['ingreso' => fn($q) => $q->with(['observacionRelacionada', 'detalles.observacionRelacionada', 'campoCampania'])->withCount('infestaciones')])
            ->get()
            ->filter(fn($v) => $v->ingreso)
            ->map(function ($venta) {
                $esMama = self::esMama($venta->ingreso);
                $dias = (int) Carbon::parse($venta->ingreso->fecha)->diffInDays(Carbon::parse($venta->fecha_venta), false);
                return [
                    'venta' => $venta,
                    'ingreso' => $venta->ingreso,
                    'dias' => $dias,
                    'limite' => CampaniaCosechaReglas::diasMaximoVenta($esMama),
                    'es_mama' => $esMama,
                ];
            })
            ->filter(fn($r) => $r['dias'] > $r['limite'])
            ->values();
    }

    /** El ingreso fue cosecha para mamá (observación marcada) o ya se usó para infestar otro campo. */
    public static function esMama(CochinillaIngreso $ingreso): bool
    {
        if ($ingreso->observacionRelacionada?->es_cosecha_mama) {
            return true;
        }
        if ($ingreso->relationLoaded('detalles')
            && $ingreso->detalles->contains(fn($d) => $d->observacionRelacionada?->es_cosecha_mama)) {
            return true;
        }
        return ($ingreso->infestaciones_count ?? 0) > 0;
    }

    private function estadoVacio(): array
    {
        return [
            'estado' => 'sin_cosecha',        // sin_cosecha | cosechando | cosechada
            'primera_cosecha' => null,        // Carbon
            'ultima_cosecha' => null,         // Carbon
            'dias_cosecha' => 0,
            'dias_desde_cosecha' => null,
            'kg' => 0.0,
            'es_mama' => false,
            'actividad_posterior' => null,    // ['fecha' => Carbon, 'codigo', 'labor']
            'fecha_cierre_sugerida' => null,  // Carbon
            'recomendar_cierre' => null,      // null | actividades | tiempo
        ];
    }

    /**
     * Ingresos de cochinilla por campaña: los que tienen la campaña asignada y, para los que no la tienen,
     * los del mismo campo cuya fecha cae en el rango de la campaña.
     */
    private function ingresosPorCampania(Collection $campanias): array
    {
        $ingresos = CochinillaIngreso::query()
            ->select(['id', 'fecha', 'campo', 'campo_campania_id', 'total_kilos', 'observacion'])
            ->with(['observacionRelacionada', 'detalles.observacionRelacionada'])
            ->withCount('infestaciones')
            ->where(fn($q) => $q
                ->whereNull('observacion')
                ->orWhereNotIn('observacion', CampaniaCosechaReglas::observacionesNoCosecha()))
            ->where(fn($q) => $q
                ->whereIn('campo_campania_id', $campanias->keys())
                ->orWhere(fn($q) => $q->whereNull('campo_campania_id')->whereIn('campo', $campanias->pluck('campo')->unique())))
            ->get();

        $porCampania = [];
        foreach ($ingresos as $ingreso) {
            $id = $ingreso->campo_campania_id ?? $this->campaniaQueContiene($campanias, $ingreso->campo, $ingreso->fecha)?->id;
            if ($id && $campanias->has($id)) {
                $porCampania[$id] ??= collect();
                $porCampania[$id]->push($ingreso);
            }
        }
        return $porCampania;
    }

    /** Fechas (Y-m-d) con labores de cosecha dentro del rango de cada campaña. */
    private function actividadesCosechaPorCampania(Collection $campanias, Carbon $hoy): array
    {
        $desde = $campanias->min(fn($c) => $c->fecha_inicio);
        $filas = Actividad::query()
            ->whereIn('campo', $campanias->pluck('campo')->unique())
            ->whereIn('codigo_labor', CampaniaCosechaReglas::laboresCosecha())
            ->whereBetween('fecha', [Carbon::parse($desde)->toDateString(), $hoy->toDateString()])
            ->distinct()
            ->get(['campo', 'fecha']);

        $porCampania = [];
        foreach ($filas as $fila) {
            $campania = $this->campaniaQueContiene($campanias, $fila->campo, $fila->fecha);
            if ($campania) {
                $porCampania[$campania->id][] = Carbon::parse($fila->fecha)->toDateString();
            }
        }
        return $porCampania;
    }

    private function campaniaQueContiene(Collection $campanias, string $campo, $fecha): ?CampoCampania
    {
        $dia = Carbon::parse($fecha)->toDateString();
        return $campanias->first(fn($c) => $c->campo === $campo
            && $c->fecha_inicio->toDateString() <= $dia
            && ($c->fecha_fin === null || $c->fecha_fin->toDateString() >= $dia));
    }

    /**
     * Agrupa los días en tandas separadas por huecos de más de GAP_DIAS y devuelve la tanda de la cosecha:
     * la que contiene ingresos de cochinilla o, si no hay ingresos, la primera.
     *
     * @return string[]|null días ordenados
     */
    private function tandaDeCosecha(array $diasIngreso, array $diasLabor): ?array
    {
        $dias = array_values(array_unique(array_merge($diasIngreso, $diasLabor)));
        if (!$dias) {
            return null;
        }
        sort($dias);

        $tandas = [[$dias[0]]];
        for ($i = 1; $i < count($dias); $i++) {
            $anterior = Carbon::parse($dias[$i - 1]);
            if ($anterior->diffInDays(Carbon::parse($dias[$i])) > self::GAP_DIAS) {
                $tandas[] = [];
            }
            $tandas[count($tandas) - 1][] = $dias[$i];
        }

        foreach ($tandas as $tanda) {
            if (array_intersect($tanda, $diasIngreso)) {
                return $tanda;
            }
        }
        return $tandas[0];
    }

    /** Primera labor en el campo después de la cosecha que no es de cosecha ni neutra. */
    private function primeraActividadNueva(string $campo, Carbon $ultimaCosecha): ?Actividad
    {
        return Actividad::query()
            ->where('campo', $campo)
            ->whereDate('fecha', '>', $ultimaCosecha)
            ->whereNotIn('codigo_labor', array_merge(
                CampaniaCosechaReglas::laboresCosecha(),
                CampaniaCosechaReglas::laboresNeutras(),
            ))
            ->orderBy('fecha')
            ->first(['fecha', 'codigo_labor', 'nombre_labor']);
    }
}
