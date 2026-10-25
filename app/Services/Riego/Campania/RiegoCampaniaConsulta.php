<?php

namespace App\Services\Riego\Campania;

use App\Models\CampoCampania;
use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use Illuminate\Support\Carbon;

/**
 * Registros de riego en campos que ese día no tienen campaña. Su costo de mano de obra queda "SIN CAMPAÑA" en la BDD
 * de costos hasta que se cree la campaña o se corrija el campo. FDM (fundo) no tiene campaña y no cuenta.
 */
class RiegoCampaniaConsulta
{
    /** Campos que no llevan campaña */
    public const SIN_CAMPANIA_OK = ['fdm'];

    /**
     * @return array<string, array<string, array{regadores: string[], horas: float}>> campo => fecha => regadores y horas
     */
    public function sinCampania(string $desde, string $hasta): array
    {
        $consolidados = ConsolidadoRiego::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->get()->keyBy('id');
        if ($consolidados->isEmpty()) {
            return [];
        }
        $registros = ReporteDiarioRiego::whereIn('consolidado_id', $consolidados->keys())
            ->whereNotNull('campo')->where('campo', '<>', '')
            ->get(['consolidado_id', 'campo', 'hora_inicio', 'hora_fin'])
            ->reject(fn($r) => in_array(mb_strtolower(trim($r->campo)), self::SIN_CAMPANIA_OK, true));

        $campanias = CampoCampania::whereIn('campo', $registros->pluck('campo')->map(fn($c) => trim($c))->unique())
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $desde))
            ->get(['campo', 'fecha_inicio', 'fecha_fin'])->groupBy('campo');

        $resultado = [];
        foreach ($registros as $r) {
            $consolidado = $consolidados[$r->consolidado_id];
            $dia = Carbon::parse($consolidado->fecha)->toDateString();
            $campo = trim($r->campo);
            $cubierta = ($campanias[$campo] ?? collect())->contains(fn($c) =>
                $c->fecha_inicio->toDateString() <= $dia && ($c->fecha_fin === null || $c->fecha_fin->toDateString() >= $dia));
            if ($cubierta) {
                continue;
            }
            $resultado[$campo][$dia] ??= ['regadores' => [], 'horas' => 0.0];
            $resultado[$campo][$dia]['regadores'][] = trim((string) $consolidado->trabajador_nombre) ?: 'Regador';
            $resultado[$campo][$dia]['horas'] += $this->horas($r->hora_inicio, $r->hora_fin);
        }
        ksort($resultado, SORT_NATURAL);
        foreach ($resultado as &$dias) {
            ksort($dias);
            foreach ($dias as &$d) {
                $d['regadores'] = array_values(array_unique($d['regadores']));
                $d['horas'] = round($d['horas'], 2);
            }
        }
        return $resultado;
    }

    private function horas(?string $inicio, ?string $fin): float
    {
        if (!$inicio || !$fin) {
            return 0;
        }
        $min = Carbon::parse($inicio)->diffInMinutes(Carbon::parse($fin), false);
        return ($min < 0 ? $min + 1440 : $min) / 60;
    }
}
