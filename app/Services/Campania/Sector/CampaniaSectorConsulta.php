<?php

namespace App\Services\Campania\Sector;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\Campo\Actividad\CampoActividadConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mapa de etapas de una campaña: hileras del campo, grupos de hileras con sus etapas, la etapa de cada hilera en una
 * fecha y la línea de tiempo de cada grupo. También qué labores registradas quedan dentro o fuera de su etapa.
 */
class CampaniaSectorConsulta
{
    public function __construct(private CampoActividadConsulta $actividades)
    {
    }

    /** Fin efectivo de la campaña para el mapa: su cierre o hoy. */
    public static function fin(CampoCampania $c): string
    {
        return $c->fecha_fin ? Carbon::parse($c->fecha_fin)->toDateString() : now()->toDateString();
    }

    /**
     * Labores de etapa registradas en la campaña (planilla y cuadrilla), una fila por día y labor.
     *
     * @return Collection<int, array{fecha:string, labor:int, nombre:string, etapa:string, familia:string, trabajadores:int}>
     */
    public function registros(CampoCampania $c): Collection
    {
        if (!$c->fecha_inicio) {
            return collect();
        }
        $labores = DB::table('labores')->whereNotNull('codigo')->get(['codigo', 'nombre_labor', 'codigo_mano_obra'])->keyBy('codigo');
        return $this->actividades->tramos(Carbon::parse($c->fecha_inicio)->toDateString(), self::fin($c), [$c->campo])
            ->groupBy(fn($t) => "{$t->fecha}|{$t->labor}")
            ->map(function ($g) use ($labores) {
                $t = $g->first();
                $l = $labores[$t->labor] ?? null;
                $etapa = CampaniaSectorReglas::etapaDeManoObra($l?->codigo_mano_obra);
                return $etapa ? [
                    'fecha' => $t->fecha,
                    'labor' => (int) $t->labor,
                    'nombre' => (string) ($l->nombre_labor ?? ''),
                    'etapa' => $etapa,
                    'familia' => CampaniaSectorReglas::familia($etapa),
                    'trabajadores' => $g->count(),
                ] : null;
            })->filter()->sortBy('fecha')->values();
    }

    /**
     * Hitos de etapa del módulo de cochinilla en la campaña (tienen historia desde antes del registro diario):
     * infestaciones del campo y cosechas que ingresaron (poda, mamá; pre-cosecha = cosecha de madres). Los lotes de
     * infestadores no cuentan: se registran con el campo que dio la mamá.
     *
     * @return Collection<int, array{fecha:string, etapa:string, inicia:bool}>
     */
    public function eventosCochinilla(CampoCampania $c): Collection
    {
        if (!$c->fecha_inicio) {
            return collect();
        }
        $rango = [Carbon::parse($c->fecha_inicio)->toDateString(), self::fin($c)];
        $infestaciones = DB::table('cochinilla_infestaciones')->where('campo_nombre', $c->campo)->whereBetween('fecha', $rango)
            ->get(['fecha', 'tipo_infestacion'])
            ->map(fn($i) => ['fecha' => Carbon::parse($i->fecha)->toDateString(),
                'etapa' => $i->tipo_infestacion === 'reinfestacion' ? 'reinfestacion' : 'infestacion',
                'inicia' => $i->tipo_infestacion !== 'reinfestacion']);
        $cosechas = DB::table('cochinilla_ingresos')->where('campo', $c->campo)->whereBetween('fecha', $rango)
            ->where('observacion', 'not like', 'infestador%')->get(['fecha', 'observacion'])
            ->map(function ($i) {
                $o = (string) $i->observacion;
                $etapa = str_starts_with($o, 'pre_cosecha') ? 'cosecha_madres'
                    : (str_starts_with($o, 'poda') || str_starts_with($o, 'mama') ? 'cosecha' : null);
                return $etapa ? ['fecha' => Carbon::parse($i->fecha)->toDateString(), 'etapa' => $etapa, 'inicia' => $etapa === 'cosecha'] : null;
            })->filter();
        return $infestaciones->concat($cosechas)->values();
    }

    /** Etapa de una lista de etapas [['etapa','fecha_desde'], …] en una fecha (la última que empezó). */
    public static function etapaEn(array $etapas, string $fecha): ?string
    {
        $actual = null;
        foreach (collect($etapas)->sortBy('fecha_desde') as $e) {
            if ($e['fecha_desde'] && $e['fecha_desde'] <= $fecha) {
                $actual = $e['etapa'];
            }
        }
        return $actual;
    }

    /**
     * Labores registradas que no caen en su etapa en ningún grupo de hileras (con esos sectores).
     *
     * @param array $sectores [['nombre', 'etapas' => [['etapa','fecha_desde'], …]], …]
     * @return Collection registros fuera de etapa
     */
    public function fueraDeEtapa(array $sectores, Collection $registros): Collection
    {
        return $registros->reject(function ($r) use ($sectores) {
            foreach ($sectores as $s) {
                $e = self::etapaEn($s['etapas'] ?? [], $r['fecha']);
                if ($e && CampaniaSectorReglas::familia($e) === $r['familia']) {
                    return true;
                }
            }
            return false;
        })->values();
    }

    /** Sectores guardados de la campaña en el formato del editor. */
    public function sectores(CampoCampania $c): array
    {
        return $c->sectores()->with('etapas')->get()->map(fn($s) => [
            'id' => $s->id,
            'nombre' => $s->nombre,
            'fecha_siembra' => $s->fecha_siembra?->toDateString(),
            'hilera_desde' => $s->hilera_desde,
            'hilera_hasta' => $s->hilera_hasta,
            'origen' => $s->origen,
            'etapas' => $s->etapas->map(fn($e) => ['etapa' => $e->etapa, 'fecha_desde' => $e->fecha_desde->toDateString()])->values()->all(),
        ])->values()->all();
    }

    /**
     * Lo que dibuja el mapa en una fecha: cada hilera con su grupo y etapa, y la línea de tiempo de cada grupo.
     *
     * @param array $sectores formato de sectores()
     */
    public function dibujo(CampoCampania $c, int $hileras, array $sectores, string $fecha): array
    {
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = max(self::fin($c), collect($sectores)->flatMap(fn($s) => array_column($s['etapas'] ?? [], 'fecha_desde'))->filter()->max() ?? $inicio);
        $totalDias = max(1, Carbon::parse($inicio)->diffInDays(Carbon::parse($fin)) + 1);
        $pos = fn(string $f) => round(max(0, min($totalDias, Carbon::parse($inicio)->diffInDays(Carbon::parse($f), false))) / $totalDias * 100, 3);

        $filas = [];
        for ($h = 1; $h <= $hileras; $h++) {
            $i = collect($sectores)->search(fn($s) => $h >= (int) $s['hilera_desde'] && $h <= (int) $s['hilera_hasta']);
            $etapa = $i !== false ? self::etapaEn($sectores[$i]['etapas'] ?? [], $fecha) : null;
            $filas[] = ['hilera' => $h, 'sector' => $i === false ? null : $i, 'etapa' => $etapa,
                'color' => $i === false ? '#F1F5F9' : CampaniaSectorReglas::color($etapa)];
        }

        $lineas = [];
        foreach ($sectores as $i => $s) {
            $etapas = collect($s['etapas'] ?? [])->filter(fn($e) => $e['fecha_desde'])->sortBy('fecha_desde')->values();
            $tramos = [];
            $primero = $etapas->first()['fecha_desde'] ?? null;
            if (!$primero || $primero > $inicio) {
                $tramos[] = ['etapa' => null, 'desde' => $inicio, 'izq' => 0, 'ancho' => $primero ? $pos($primero) : 100];
            }
            foreach ($etapas as $k => $e) {
                $hasta = $etapas[$k + 1]['fecha_desde'] ?? null;
                $izq = $pos($e['fecha_desde']);
                $tramos[] = ['etapa' => $e['etapa'], 'desde' => $e['fecha_desde'], 'izq' => $izq, 'ancho' => max(0.5, ($hasta ? $pos($hasta) : 100) - $izq)];
            }
            $lineas[] = ['sector' => $i, 'tramos' => $tramos, 'actual' => self::etapaEn($s['etapas'] ?? [], $fecha)];
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'fecha' => $fecha,
            'marca' => $pos($fecha),
            'filas' => $filas,
            'lineas' => $lineas,
        ];
    }

    public static function hilerasDelCampo(string $campo): int
    {
        return max(1, (int) (Campo::whereKey($campo)->value('hileras') ?: 10));
    }
}
