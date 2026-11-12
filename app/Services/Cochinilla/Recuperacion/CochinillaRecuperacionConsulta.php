<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaRecuperacion;
use App\Services\Cochinilla\Origen\CochinillaOrigenProceso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cochinilla por recuperar: cada ENVÍO de mamá de un campo (se cosechó y se usó para infestar uno o varios campos,
 * días seguidos) con lo que ya volvió como infestadores, los días que van y si ya se dio por recuperado.
 *
 * Los infestadores que vuelven se registran con el campo que dio la mamá (CochinillaOrigenProceso): los retornos de
 * un envío son los ingresos de infestador de ese mismo campo, desde el envío hasta el siguiente envío del campo.
 * Ej.: A5 dio 7,206.60 kg a Limonero4, 18, B3 y A8 (06–14/03/2026) → volvieron los lotes 1502, 1504, 1505 y 1506.
 */
class CochinillaRecuperacionConsulta
{
    public function __construct(private CochinillaOrigenProceso $origen)
    {
    }

    /**
     * @param string $estado por_recuperar | alerta | recuperadas | todas
     * @param string|null $campo campo que dio la mamá o campo infestado
     * @return Collection<int, array>
     */
    public function tandas(string $estado = 'por_recuperar', ?string $campo = null): Collection
    {
        $desde = now()->subDays(CochinillaRecuperacionReglas::DIAS_VENTANA)->toDateString();
        // Desde un poco antes: el envío que empezó antes de la ventana se arma completo
        $infestaciones = CochinillaInfestacion::where('fecha', '>=', Carbon::parse($desde)->subDays(CochinillaOrigenProceso::DIAS_MISMA_INFESTACION)->toDateString())
            ->whereNotNull('campo_origen_nombre')->where('campo_origen_nombre', '<>', '')
            ->orderBy('campo_origen_nombre')->orderBy('fecha')->orderBy('id')
            // infestadores no es columna: lo calcula el modelo con numero_envases × capacidad_envase
            ->get(['id', 'fecha', 'campo_nombre', 'campo_origen_nombre', 'kg_madres', 'numero_envases', 'capacidad_envase', 'tipo_infestacion']);
        $cerradas = CochinillaRecuperacion::with('cerradaPor:id,name')->get()
            ->keyBy(fn($r) => $r->campo . '|' . $r->tanda_inicio->toDateString());
        $alerta = CochinillaRecuperacionReglas::diasAlerta();

        $envios = collect();
        foreach ($infestaciones->groupBy('campo_origen_nombre') as $origen => $porOrigen) {
            $grupos = $this->agrupar($porOrigen);
            foreach ($grupos as $i => $grupo) {
                $siguiente = isset($grupos[$i + 1]) ? Carbon::parse($grupos[$i + 1][0]->fecha)->toDateString() : null;
                $envios->push($this->armar((string) $origen, $grupo, $siguiente, $cerradas, $alerta));
            }
        }

        $campo = $campo !== null ? mb_strtolower(trim($campo)) : null;
        return $envios
            ->filter(fn($t) => $t['fin'] >= $desde)
            ->filter(fn($t) => !$campo || mb_strtolower($t['campo']) === $campo
                || collect(array_keys($t['destinos']))->contains(fn($d) => mb_strtolower($d) === $campo))
            ->filter(fn($t) => match ($estado) {
                'por_recuperar' => !$t['cerrada'],
                'alerta' => $t['alerta'],
                'recuperadas' => (bool) $t['cerrada'],
                default => true,
            })->sortBy([['alerta', 'desc'], ['dias', 'desc']])->values();
    }

    /** Un envío concreto (por el campo que dio la mamá y la fecha de inicio). */
    public function tanda(string $campo, string $inicio): ?array
    {
        $inicio = Carbon::parse($inicio)->toDateString();
        return $this->tandas('todas', $campo)->first(fn($t) => $t['campo'] === $campo && $t['inicio'] === $inicio);
    }

    /** Infestaciones con mamá de un campo separadas en envíos (más de N días sin dar mamá = otro envío). */
    private function agrupar(Collection $porOrigen): array
    {
        $grupos = [];
        $actual = [];
        $anterior = null;
        foreach ($porOrigen as $inf) {
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

    private function armar(string $origen, array $grupo, ?string $siguiente, Collection $cerradas, int $alerta): array
    {
        $inicio = Carbon::parse($grupo[0]->fecha)->toDateString();
        $fin = Carbon::parse(end($grupo)->fecha)->toDateString();
        $destinos = [];
        foreach ($grupo as $inf) {
            $d = $inf->campo_nombre ?: '¿?';
            $f = Carbon::parse($inf->fecha)->toDateString();
            $destinos[$d] ??= ['kg_madres' => 0.0, 'infestadores' => 0, 'desde' => $f, 'hasta' => $f, 'reinfestacion' => false];
            $destinos[$d]['kg_madres'] += (float) $inf->kg_madres;
            $destinos[$d]['infestadores'] += (int) $inf->infestadores;
            $destinos[$d]['hasta'] = $f;
            $destinos[$d]['reinfestacion'] = $destinos[$d]['reinfestacion'] || $inf->tipo_infestacion === 'reinfestacion';
        }
        $kgMadres = array_sum(array_column($destinos, 'kg_madres'));
        foreach ($destinos as &$d) {
            $d['porcentaje'] = $kgMadres > 0 ? round($d['kg_madres'] / $kgMadres * 100, 2) : null;
        }
        unset($d);

        // Retornos: lotes de infestador del mismo campo, desde el envío hasta el siguiente envío (o el plazo máximo)
        $limite = Carbon::parse($fin)->addDays(CochinillaOrigenProceso::DIAS_RETORNO)->toDateString();
        $ingresos = CochinillaIngreso::where('campo', $origen)->where('observacion', 'like', 'infestador%')
            ->where('fecha', '>=', $inicio)->where('fecha', '<=', $limite)
            ->when($siguiente, fn($q) => $q->where('fecha', '<', $siguiente))
            ->orderBy('fecha')->orderBy('lote')
            ->get(['id', 'lote', 'fecha', 'campo', 'observacion', 'total_kilos', 'campania_origen_id']);
        $factor = CochinillaRecuperacionReglas::factorSeco();
        $kgRecuperados = (float) $ingresos->sum('total_kilos');
        $cerrada = $cerradas->get("{$origen}|{$inicio}");
        $dias = (int) Carbon::parse($fin)->diffInDays(now()->startOfDay());
        $campania = $this->origen->campaniaParaOrigen($origen, $fin);

        return [
            'clave' => "{$origen}|{$inicio}",
            'campo' => $origen,
            'campania_id' => $campania?->id,
            'campania' => $campania?->nombre_campania,
            'inicio' => $inicio,
            'fin' => $fin,
            'dias' => $dias,
            'destinos' => $destinos,
            'kg_madres' => round($kgMadres, 2),
            'infestadores' => array_sum(array_column($destinos, 'infestadores')),
            'kg_recuperados' => round($kgRecuperados, 2),
            // Comparado con lo esperado en seco (mamá fresca ÷ factor), no con los kg frescos de mamá
            'factor_seco' => $factor,
            'kg_esperado_seco' => round($kgMadres / $factor, 2),
            'porcentaje_recuperado' => $kgMadres > 0 ? round($kgRecuperados / ($kgMadres / $factor) * 100, 1) : null,
            'ingresos' => $ingresos->map(fn($i) => [
                'id' => $i->id, 'lote' => $i->lote, 'fecha' => Carbon::parse($i->fecha)->toDateString(), 'kg' => (float) $i->total_kilos,
                'tipo' => $i->observacion,
            ])->all(),
            'cerrada' => $cerrada ? ['en' => $cerrada->cerrada_en, 'por' => $cerrada->cerradaPor?->name, 'nota' => $cerrada->nota] : null,
            'alerta' => !$cerrada && $dias > $alerta,
            'dias_alerta' => $alerta,
        ];
    }
}
