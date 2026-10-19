<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaRecuperacion;
use App\Services\Cochinilla\Origen\CochinillaOrigenProceso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cochinilla por recuperar: cada "tanda" de infestación de un campo (días seguidos, mamá de 1 a 3 campos) con lo que
 * ya volvió como ingresos de infestadores, los días transcurridos y si ya se dio por recuperada.
 */
class CochinillaRecuperacionConsulta
{
    /**
     * @param string $estado por_recuperar | alerta | recuperadas | todas
     * @return Collection<int, array>
     */
    public function tandas(string $estado = 'por_recuperar', ?string $campo = null): Collection
    {
        $desde = now()->subDays(CochinillaRecuperacionReglas::DIAS_VENTANA)->toDateString();
        $infestaciones = CochinillaInfestacion::where('fecha', '>=', $desde)
            ->when($campo, fn($q) => $q->where('campo_nombre', $campo))
            ->orderBy('campo_nombre')->orderBy('fecha')->orderBy('id')
            ->get(['id', 'fecha', 'campo_nombre', 'campo_origen_nombre', 'kg_madres', 'infestadores', 'metodo', 'tipo_infestacion', 'campo_campania_id']);
        $cerradas = CochinillaRecuperacion::with('cerradaPor:id,name')->where('tanda_inicio', '>=', $desde)->get()
            ->keyBy(fn($r) => $r->campo . '|' . $r->tanda_inicio->toDateString());
        $alerta = CochinillaRecuperacionReglas::diasAlerta();

        $tandas = collect();
        foreach ($infestaciones->groupBy('campo_nombre') as $campoNombre => $porCampo) {
            $grupos = $this->agrupar($porCampo);
            foreach ($grupos as $i => $grupo) {
                $siguiente = $grupos[$i + 1][0]->fecha ?? null;
                $tandas->push($this->armar($campoNombre, $grupo, $siguiente ? Carbon::parse($siguiente)->toDateString() : null, $cerradas, $alerta));
            }
        }

        return $tandas->filter(fn($t) => match ($estado) {
            'por_recuperar' => !$t['cerrada'],
            'alerta' => $t['alerta'],
            'recuperadas' => (bool) $t['cerrada'],
            default => true,
        })->sortBy([['alerta', 'desc'], ['dias', 'desc']])->values();
    }

    /** Una tanda concreta (por su campo y fecha de inicio). */
    public function tanda(string $campo, string $inicio): ?array
    {
        return $this->tandas('todas', $campo)->first(fn($t) => $t['inicio'] === Carbon::parse($inicio)->toDateString());
    }

    /** Infestaciones de un campo separadas en tandas (más de N días sin infestar = otra tanda). */
    private function agrupar(Collection $porCampo): array
    {
        $grupos = [];
        $actual = [];
        $anterior = null;
        foreach ($porCampo as $inf) {
            if ($anterior && Carbon::parse($anterior)->diffInDays(Carbon::parse($inf->fecha)) > CochinillaOrigenProceso::DIAS_MISMA_INFESTACION) {
                $grupos[] = $actual;
                $actual = [];
            }
            $actual[] = $inf;
            $anterior = $inf->fecha;
        }
        if ($actual) {
            $grupos[] = $actual;
        }
        return $grupos;
    }

    private function armar(string $campo, array $grupo, ?string $siguiente, Collection $cerradas, int $alerta): array
    {
        $inicio = Carbon::parse($grupo[0]->fecha)->toDateString();
        $fin = Carbon::parse(end($grupo)->fecha)->toDateString();
        $origenes = [];
        foreach ($grupo as $inf) {
            $o = $inf->campo_origen_nombre ?: '¿?';
            $origenes[$o] ??= ['kg_madres' => 0.0, 'infestadores' => 0, 'desde' => $inf->fecha, 'hasta' => $inf->fecha];
            $origenes[$o]['kg_madres'] += (float) $inf->kg_madres;
            $origenes[$o]['infestadores'] += (int) $inf->infestadores;
            $origenes[$o]['hasta'] = $inf->fecha;
        }
        $kgMadres = array_sum(array_column($origenes, 'kg_madres'));
        $factor = CochinillaRecuperacionReglas::factorSeco();
        foreach ($origenes as &$o) {
            $o['kg_esperado_seco'] = round($o['kg_madres'] / $factor, 2);
            $o['porcentaje'] = $kgMadres > 0 ? round($o['kg_madres'] / $kgMadres * 100, 2) : null;
            $o['kg_recuperados'] = 0.0;
        }
        unset($o);

        // Retornos: ingresos de infestadores en el campo desde la tanda hasta la siguiente (o el plazo máximo)
        $limite = Carbon::parse($fin)->addDays(CochinillaOrigenProceso::DIAS_INFESTADORES)->toDateString();
        $ingresos = CochinillaIngreso::with('origenes')->where('campo', $campo)->where('observacion', 'like', 'infestador%')
            ->where('fecha', '>=', $inicio)->where('fecha', '<=', $limite)
            ->when($siguiente, fn($q) => $q->where('fecha', '<', $siguiente))
            ->orderBy('fecha')->orderBy('lote')
            ->get(['id', 'lote', 'fecha', 'campo', 'observacion', 'total_kilos', 'origen_estado', 'campo_origen']);
        foreach ($ingresos as $ing) {
            $reparto = $ing->origenes->isNotEmpty() ? $ing->origenes->pluck('kg', 'campo_origen')->all() : [$ing->campo_origen => (float) $ing->total_kilos];
            foreach ($reparto as $c => $kg) {
                if (isset($origenes[$c])) {
                    $origenes[$c]['kg_recuperados'] += (float) $kg;
                }
            }
        }
        $kgRecuperados = (float) $ingresos->sum('total_kilos');
        $cerrada = $cerradas->get("{$campo}|{$inicio}");
        $dias = (int) Carbon::parse($fin)->diffInDays(now()->startOfDay());
        $campania = CampoCampania::find($grupo[0]->campo_campania_id);

        return [
            'clave' => "{$campo}|{$inicio}",
            'campo' => $campo,
            'campania_id' => $campania?->id,
            'campania' => $campania?->nombre_campania,
            'inicio' => $inicio,
            'fin' => $fin,
            'dias' => $dias,
            'origenes' => $origenes,
            'kg_madres' => round($kgMadres, 2),
            'infestadores' => array_sum(array_column($origenes, 'infestadores')),
            'kg_recuperados' => round($kgRecuperados, 2),
            // Comparado con lo esperado en seco (mamá fresca ÷ factor), no con los kg frescos de mamá
            'factor_seco' => $factor,
            'kg_esperado_seco' => round($kgMadres / $factor, 2),
            'porcentaje_recuperado' => $kgMadres > 0 ? round($kgRecuperados / ($kgMadres / $factor) * 100, 1) : null,
            'ingresos' => $ingresos->map(fn($i) => [
                'id' => $i->id, 'lote' => $i->lote, 'fecha' => Carbon::parse($i->fecha)->toDateString(), 'kg' => (float) $i->total_kilos,
                'estado' => $i->origen_estado,
                'reparto' => $i->origenes->map(fn($o) => ['campo' => $o->campo_origen, 'kg' => $o->kg])->values()->all(),
                'reparto_cuadra' => $i->origenes->isEmpty() || abs($i->origenes->sum('kg') - (float) $i->total_kilos) <= 0.01,
            ])->all(),
            'cerrada' => $cerrada ? ['en' => $cerrada->cerrada_en, 'por' => $cerrada->cerradaPor?->name, 'nota' => $cerrada->nota] : null,
            'alerta' => !$cerrada && $dias > $alerta,
            'dias_alerta' => $alerta,
        ];
    }
}
