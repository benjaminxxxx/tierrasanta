<?php

namespace App\Services\Cochinilla\Origen;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaObservacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Campaña dueña de la cochinilla de un ingreso (a la que se le carga la venta).
 *
 * - Cosecha, pre-cosecha, poda, mamá…: la cochinilla es del campo cosechado → su propia campaña.
 * - Infestadores (cajitas, tubos, malla): la cochinilla vino de otro campo, que se cosechó para infestar. Se busca
 *   la infestación que la puso ahí y su campo de origen; la campaña dueña es la que tenía ese campo de origen
 *   cuando se cosechó para esa infestación (la del ingreso de mamá de esos días, o la que corría antes).
 *   El costo de recoger las cajitas sigue siendo del campo donde estaban (campo_campania_id no cambia).
 *
 * Los ingresos de infestador se registran con el campo donde estaban las cajitas; algunos antiguos, con el campo
 * de origen. Se consideran las dos formas.
 */
class CochinillaOrigenProceso
{
    /** Días máximos entre la infestación y el recojo de sus infestadores. */
    public const DIAS_INFESTADORES = 180;
    /** Infestaciones de un mismo campo con días de diferencia se consideran la misma (para detectar varios orígenes). */
    public const DIAS_MISMA_INFESTACION = 15;
    /** Días antes de la infestación en que se busca el ingreso de mamá del campo de origen. */
    public const DIAS_COSECHA_MAMA = 20;

    private ?array $observacionesMama = null;

    public static function esDeInfestador(?string $observacion): bool
    {
        return $observacion !== null && str_starts_with($observacion, 'infestador');
    }

    /** Calcula y guarda el origen de un ingreso (salvo que se haya puesto a mano). */
    public function recalcular(CochinillaIngreso $ingreso): void
    {
        if ($ingreso->origen_estado === 'manual') {
            return;
        }
        $ingreso->forceFill($this->resolver($ingreso))->saveQuietly();
    }

    /** @return int ingresos recalculados */
    public function recalcularTodos(): int
    {
        $n = 0;
        CochinillaIngreso::query()->orderBy('id')->chunkById(200, function ($ingresos) use (&$n) {
            foreach ($ingresos as $ingreso) {
                $this->recalcular($ingreso);
                $n++;
            }
        });
        return $n;
    }

    /** Recalcula los ingresos de infestador que podrían venir de infestaciones de ese campo (al guardar infestaciones). */
    public function recalcularPorCampo(string $campo): void
    {
        CochinillaIngreso::where('observacion', 'like', 'infestador%')->where('campo', $campo)->get()->each(fn($i) => $this->recalcular($i));
    }

    /** @return array{campo_origen:?string, campania_origen_id:?int, origen_estado:string, origen_detalle:?string} */
    public function resolver(CochinillaIngreso $ingreso): array
    {
        $propio = [
            'campo_origen' => $ingreso->campo,
            'campania_origen_id' => $ingreso->campo_campania_id,
            'origen_estado' => 'propio',
            'origen_detalle' => null,
        ];
        if (!self::esDeInfestador($ingreso->observacion)) {
            return $propio;
        }

        $fecha = Carbon::parse($ingreso->fecha)->startOfDay();
        $desde = $fecha->copy()->subDays(self::DIAS_INFESTADORES)->toDateString();

        // 1. El campo del ingreso es donde estaban los infestadores: la última infestación que recibió
        $ultima = CochinillaInfestacion::where('campo_nombre', $ingreso->campo)
            ->whereBetween('fecha', [$desde, $fecha->toDateString()])
            ->orderByDesc('fecha')->first();
        if ($ultima) {
            $tanda = $this->tanda($ingreso->campo, $ultima);
            $origenes = $tanda['origenes'];
            $varios = count($origenes) > 1;
            // Varios orígenes: por orden de recojo (las cajitas que se pusieron primero se recogen primero)
            $origen = $varios ? $this->origenPorOrden($ingreso, $tanda) : array_key_first($origenes);
            $campania = $this->campaniaOrigen($origen, $origenes[$origen]['hasta']);
            $fmt = fn($f) => Carbon::parse($f)->format('d/m');

            return [
                'campo_origen' => $origen,
                'campania_origen_id' => $campania?->id,
                'origen_estado' => $varios ? 'sugerido' : 'infestacion',
                'origen_detalle' => mb_substr(($varios
                    ? 'Infestado desde varios campos: ' . collect($origenes)->map(fn($o, $c) => "{$c} (" . round($o['kg'], 1) . " kg, {$fmt($o['desde'])}"
                        . ($o['desde'] !== $o['hasta'] ? "–{$fmt($o['hasta'])}" : '') . ')')->implode(', ')
                        . ". Por el orden de recojo se sugiere {$origen}: confírmalo al registrar el ingreso."
                    : "Infestación {$ultima->tipo_infestacion} del " . Carbon::parse($ultima->fecha)->format('d/m/Y') . " con cochinilla de {$origen}.")
                    . " Infestadores recogidos en {$ingreso->campo}." . ($campania ? '' : ' No se encontró la campaña de origen.'), 0, 300),
            ];
        }

        // 2. Registrado con el campo de origen: la infestación que hizo ese campo antes del ingreso
        $comoOrigen = CochinillaInfestacion::where('campo_origen_nombre', $ingreso->campo)
            ->whereBetween('fecha', [$desde, $fecha->toDateString()])
            ->orderByDesc('fecha')->first();
        if ($comoOrigen) {
            $campania = $this->campaniaOrigen($ingreso->campo, $comoOrigen->fecha);
            return [
                'campo_origen' => $ingreso->campo,
                'campania_origen_id' => $campania?->id,
                'origen_estado' => 'infestacion',
                'origen_detalle' => mb_substr("Registrado con el campo de origen: infestó {$comoOrigen->campo_nombre} el "
                    . Carbon::parse($comoOrigen->fecha)->format('d/m/Y') . '.' . ($campania ? '' : ' No se encontró la campaña de origen.'), 0, 300),
            ];
        }

        return ['origen_estado' => 'sin_infestacion', 'origen_detalle' => 'Ingreso de infestadores sin infestación en los '
            . self::DIAS_INFESTADORES . ' días anteriores: se deja la campaña del campo donde se recogió. Revisar.'] + $propio;
    }

    /**
     * La tanda de infestación de un campo que termina en $ultima (días seguidos, separados por menos de
     * DIAS_MISMA_INFESTACION): sus orígenes en el orden en que se pusieron, con sus kg y fechas.
     *
     * @return array{inicio:string, fin:string, segmentos: array<int, array{campo:string, kg:float}>, origenes: array<string, array{kg:float, desde:string, hasta:string}>}
     */
    public function tanda(string $campo, CochinillaInfestacion $ultima): array
    {
        $previas = CochinillaInfestacion::where('campo_nombre', $campo)->where('fecha', '<=', $ultima->fecha)
            ->orderByDesc('fecha')->orderByDesc('id')->get(['id', 'fecha', 'campo_origen_nombre', 'kg_madres']);
        $tanda = [];
        $anterior = null;
        foreach ($previas as $inf) {
            if ($anterior && Carbon::parse($inf->fecha)->diffInDays(Carbon::parse($anterior)) > self::DIAS_MISMA_INFESTACION) {
                break;
            }
            $tanda[] = $inf;
            $anterior = $inf->fecha;
        }
        $tanda = array_reverse($tanda);

        $segmentos = [];
        $origenes = [];
        foreach ($tanda as $inf) {
            $c = $inf->campo_origen_nombre;
            $f = Carbon::parse($inf->fecha)->toDateString();
            $ultimo = count($segmentos) - 1;
            if ($ultimo >= 0 && $segmentos[$ultimo]['campo'] === $c) {
                $segmentos[$ultimo]['kg'] += (float) $inf->kg_madres;
            } else {
                $segmentos[] = ['campo' => $c, 'kg' => (float) $inf->kg_madres];
            }
            $origenes[$c] ??= ['kg' => 0.0, 'desde' => $f, 'hasta' => $f];
            $origenes[$c]['kg'] += (float) $inf->kg_madres;
            $origenes[$c]['hasta'] = max($origenes[$c]['hasta'], $f);
        }

        return [
            'inicio' => Carbon::parse($tanda[0]->fecha)->toDateString(),
            'fin' => Carbon::parse($ultima->fecha)->toDateString(),
            'segmentos' => $segmentos,
            'origenes' => $origenes,
        ];
    }

    /**
     * Origen sugerido por orden de recojo: los recojos de infestadores de la tanda, en orden, se reparten sobre los
     * días de infestación en el mismo orden según su proporción de kg (un recojo cae en el origen del punto medio
     * de sus kg). Sin cálculos proporcionales en la venta: cada lote queda con un solo origen.
     */
    private function origenPorOrden(CochinillaIngreso $ingreso, array $tanda): string
    {
        $siguiente = CochinillaInfestacion::where('campo_nombre', $ingreso->campo)->where('fecha', '>', $tanda['fin'])->min('fecha');
        $recojos = CochinillaIngreso::where('campo', $ingreso->campo)->where('observacion', 'like', 'infestador%')
            ->where('fecha', '>', $tanda['inicio'])
            ->where('fecha', '<=', Carbon::parse($tanda['fin'])->addDays(self::DIAS_INFESTADORES)->toDateString())
            ->when($siguiente, fn($q) => $q->where('fecha', '<', $siguiente))
            ->orderBy('fecha')->orderBy('lote')->get(['id', 'total_kilos']);

        $totalRecojo = max(0.0001, (float) $recojos->sum('total_kilos'));
        $antes = 0.0;
        $propio = (float) $ingreso->total_kilos;
        foreach ($recojos as $r) {
            if ($r->id === $ingreso->id) {
                break;
            }
            $antes += (float) $r->total_kilos;
        }
        $punto = ($antes + $propio / 2) / $totalRecojo;

        $totalMadres = max(0.0001, array_sum(array_column($tanda['segmentos'], 'kg')));
        $acumulado = 0.0;
        foreach ($tanda['segmentos'] as $s) {
            $acumulado += $s['kg'] / $totalMadres;
            if ($punto <= $acumulado + 1e-9) {
                return $s['campo'];
            }
        }
        return end($tanda['segmentos'])['campo'];
    }

    /** Campaña de origen para un origen elegido a mano (al confirmar o cambiar la sugerencia). */
    public function campaniaParaOrigen(string $campoOrigen, string $fechaInfestacion): ?CampoCampania
    {
        return $this->campaniaOrigen($campoOrigen, $fechaInfestacion);
    }

    /**
     * Orígenes posibles de un ingreso de infestadores (los de su tanda), con el sugerido. Para elegir al registrar.
     *
     * @return array{opciones: array<string, string>, sugerido: ?string}
     */
    public function opciones(CochinillaIngreso $ingreso): array
    {
        if (!self::esDeInfestador($ingreso->observacion)) {
            return ['opciones' => [], 'sugerido' => null];
        }
        $ultima = CochinillaInfestacion::where('campo_nombre', $ingreso->campo)
            ->whereBetween('fecha', [Carbon::parse($ingreso->fecha)->subDays(self::DIAS_INFESTADORES)->toDateString(), Carbon::parse($ingreso->fecha)->toDateString()])
            ->orderByDesc('fecha')->first();
        if (!$ultima) {
            return ['opciones' => [], 'sugerido' => null];
        }
        $tanda = $this->tanda($ingreso->campo, $ultima);
        $fmt = fn($f) => Carbon::parse($f)->format('d/m/Y');
        return [
            'opciones' => collect($tanda['origenes'])->mapWithKeys(fn($o, $c) => [$c => "{$c} — " . round($o['kg'], 1) . " kg de madres, {$fmt($o['desde'])}"
                . ($o['desde'] !== $o['hasta'] ? " a {$fmt($o['hasta'])}" : '')])->all(),
            'sugerido' => count($tanda['origenes']) > 1 ? $this->origenPorOrden($ingreso, $tanda) : array_key_first($tanda['origenes']),
            'fechas' => collect($tanda['origenes'])->map(fn($o) => $o['hasta'])->all(),
        ];
    }

    /**
     * Quien registra el ingreso confirma o cambia el origen: queda como elegido a mano y ya no se recalcula.
     */
    public function confirmar(CochinillaIngreso $ingreso, string $campoOrigen): void
    {
        $opciones = $this->opciones($ingreso);
        $fecha = $opciones['fechas'][$campoOrigen] ?? $ingreso->fecha;
        $campania = $this->campaniaOrigen($campoOrigen, $fecha);
        $ingreso->forceFill([
            'campo_origen' => $campoOrigen,
            'campania_origen_id' => $campania?->id,
            'origen_estado' => 'manual',
            'origen_detalle' => mb_substr('Origen elegido al registrar' . ($opciones['sugerido'] && $opciones['sugerido'] !== $campoOrigen ? " (se sugería {$opciones['sugerido']})" : '')
                . ' por ' . (auth()->user()?->name ?? 'sistema') . '. Infestadores recogidos en ' . $ingreso->campo . '.'
                . ($campania ? '' : ' No se encontró la campaña de origen.'), 0, 300),
        ])->saveQuietly();
    }

    /**
     * La campaña del campo de origen que se cosechó para esa infestación: la del ingreso de mamá de esos días; si
     * no hay, la que corría antes de la infestación.
     */
    private function campaniaOrigen(?string $campo, $fechaInfestacion): ?CampoCampania
    {
        if (!$campo) {
            return null;
        }
        $f = Carbon::parse($fechaInfestacion)->startOfDay();
        $this->observacionesMama ??= CochinillaObservacion::where('es_cosecha_mama', true)->pluck('codigo')->all();

        $ingresoMama = CochinillaIngreso::where('campo', $campo)
            ->whereIn('observacion', $this->observacionesMama)
            ->whereBetween('fecha', [$f->copy()->subDays(self::DIAS_COSECHA_MAMA)->toDateString(), $f->toDateString()])
            ->whereNotNull('campo_campania_id')
            ->orderByDesc('fecha')->first();
        if ($ingresoMama) {
            return $ingresoMama->campoCampania;
        }

        // La campaña que cerró con esa cosecha (la siguiente pudo empezar antes de la infestación)
        $cerrada = CampoCampania::where('campo', $campo)
            ->whereBetween(DB::raw('COALESCE(cosch_fecha, fecha_fin)'), [$f->copy()->subDays(self::DIAS_COSECHA_MAMA)->toDateString(), $f->copy()->addDays(5)->toDateString()])
            ->orderByDesc(DB::raw('COALESCE(cosch_fecha, fecha_fin)'))->first();

        return $cerrada ?? CampoCampania::where('campo', $campo)->whereDate('fecha_inicio', '<', $f)
            ->orderByDesc('fecha_inicio')->first();
    }
}
