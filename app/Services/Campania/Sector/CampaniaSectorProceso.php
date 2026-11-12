<?php

namespace App\Services\Campania\Sector;

use App\Exceptions\ErrorControlado;
use App\Models\CampaniaSector;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Grupos de hileras de una campaña y sus etapas: detección con lo registrado y corrección a mano.
 *
 * Detección: si en la campaña se colocó infestadores (o se podó) en tandas separadas por más de DIAS_GRUPO días, cada
 * tanda es un grupo de hileras distinto; las hileras se reparten parejo entre los grupos. Cada grupo entra a una etapa
 * el primer día de sus labores de esa etapa. Es una aproximación: se corrige a mano cuando se sabe cómo fue.
 *
 * Corrección: no se puede dejar fuera de su etapa una labor ya registrada que antes sí caía en ella (ej. mover el
 * inicio de la cosecha después de una poda ya registrada).
 */
class CampaniaSectorProceso
{
    public function __construct(private CampaniaSectorConsulta $consulta)
    {
    }

    /** @return int grupos creados (0 si se respetó una corrección manual) */
    public function detectar(CampoCampania $c, bool $forzar = false): int
    {
        if (!$c->fecha_inicio) {
            return 0;
        }
        if (!$forzar && $c->sectores()->where('origen', 'manual')->exists()) {
            return 0;
        }
        $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $registros = $this->consulta->registros($c);

        // Días de cada etapa y tandas de lo que inicia un grupo (colocar infestadores, podar), con las labores del
        // registro diario y los hitos del módulo de cochinilla (infestaciones, cosechas: tienen historia más antigua)
        $dias = [];
        $inicios = [];
        foreach ($registros as $r) {
            $dias[$r['etapa']][$r['fecha']] = true;
            if (CampaniaSectorReglas::iniciaGrupo($r['etapa'], $r['nombre'])) {
                $inicios[$r['etapa']][$r['fecha']] = true;
            }
        }
        foreach ($this->consulta->eventosCochinilla($c) as $e) {
            $dias[$e['etapa']][$e['fecha']] = true;
            if ($e['inicia']) {
                $inicios[$e['etapa']][$e['fecha']] = true;
            }
        }
        $tandas = array_map(fn($fechas) => $this->tandas(array_keys($fechas)), $inicios);
        $grupos = max(1, count($tandas['infestacion'] ?? []), count($tandas['cosecha'] ?? []));
        $grupos = min($grupos, CampaniaSectorReglas::MAX_GRUPOS, $hileras);
        $rangos = CampaniaSectorReglas::repartirHileras($hileras, $grupos);

        DB::transaction(function () use ($c, $rangos, $dias, $tandas, $grupos) {
            $c->sectores()->delete();
            foreach ($rangos as $k => [$desde, $hasta]) {
                $sector = CampaniaSector::create([
                    'campo_campania_id' => $c->id,
                    'nombre' => 'Grupo ' . ($k + 1),
                    'hilera_desde' => $desde,
                    'hilera_hasta' => $hasta,
                    'origen' => 'detectado',
                ]);
                foreach (array_keys(CampaniaSectorReglas::ETAPAS) as $etapa) {
                    if (empty($dias[$etapa])) {
                        continue;
                    }
                    // Infestación y cosecha en tantas tandas como grupos: cada grupo con la suya; si no, todos desde el primer día
                    $fecha = count($tandas[$etapa] ?? []) === $grupos && $grupos > 1
                        ? $tandas[$etapa][$k][0]
                        : min(array_keys($dias[$etapa]));
                    $sector->etapas()->create(['etapa' => $etapa, 'fecha_desde' => $fecha]);
                }
            }
        });
        return count($rangos);
    }

    /** @return array{campanias:int, con_varios_grupos:int, respetadas:int} */
    public function detectarTodas(bool $forzar = false): array
    {
        $n = $varios = $respetadas = 0;
        CampoCampania::query()->whereNotNull('fecha_inicio')->orderBy('id')->chunkById(100, function ($campanias) use ($forzar, &$n, &$varios, &$respetadas) {
            foreach ($campanias as $c) {
                $grupos = $this->detectar($c, $forzar);
                $grupos === 0 ? $respetadas++ : $n++;
                if ($grupos > 1) {
                    $varios++;
                }
            }
        });
        return ['campanias' => $n, 'con_varios_grupos' => $varios, 'respetadas' => $respetadas];
    }

    /**
     * Guarda las hileras del campo y los grupos de hileras con sus etapas (corrección a mano).
     *
     * @param array $sectores [['nombre', 'hilera_desde', 'hilera_hasta', 'etapas' => [['etapa', 'fecha_desde'], …]], …]
     */
    public function guardar(CampoCampania $c, int $hileras, array $sectores, ?string $motivo = null): void
    {
        $limpios = $this->validar($c, $hileras, $sectores);

        // No dejar fuera de su etapa labores que hoy sí están en ella
        $registros = $this->consulta->registros($c);
        $antes = $this->consulta->sectores($c);
        $fueraAntes = $this->consulta->fueraDeEtapa($antes, $registros)->map(fn($r) => "{$r['fecha']}|{$r['labor']}")->flip();
        $nuevasFuera = $this->consulta->fueraDeEtapa($limpios, $registros)->reject(fn($r) => isset($fueraAntes["{$r['fecha']}|{$r['labor']}"]));
        if ($nuevasFuera->isNotEmpty()) {
            $detalle = $nuevasFuera->groupBy('familia')->map(function ($g, $familia) {
                $fechas = $g->pluck('fecha')->unique()->sort()->values();
                $lista = $fechas->take(4)->map(fn($f) => Carbon::parse($f)->format('d/m'))->implode(', ') . ($fechas->count() > 4 ? ' y ' . ($fechas->count() - 4) . ' día(s) más' : '');
                return CampaniaSectorReglas::FAMILIAS[$familia] . " el {$lista} (" . $g->pluck('labor')->unique()->take(3)->map(fn($l) => $l . ' · ' . $g->firstWhere('labor', $l)['nombre'])->implode(', ') . ')';
            })->implode('; ');
            throw new ErrorControlado("No se guardó: hay labores registradas que quedarían fuera de su etapa: {$detalle}. "
                . 'Alguna hilera debe estar en esa etapa en esas fechas (o corrige primero la labor en el registro diario).');
        }

        DB::transaction(function () use ($c, $hileras, $limpios, $antes, $motivo) {
            $campoAntes = (int) Campo::whereKey($c->campo)->value('hileras');
            if ($campoAntes !== $hileras) {
                Campo::whereKey($c->campo)->update(['hileras' => $hileras]);
            }
            $c->sectores()->delete();
            foreach ($limpios as $s) {
                $sector = CampaniaSector::create([
                    'campo_campania_id' => $c->id,
                    'nombre' => $s['nombre'],
                    'hilera_desde' => $s['hilera_desde'],
                    'hilera_hasta' => $s['hilera_hasta'],
                    'origen' => 'manual',
                ]);
                foreach ($s['etapas'] as $e) {
                    $sector->etapas()->create($e);
                }
            }
            AuditoriaServicio::registrar(CampoCampania::class, $c->id, 'editar',
                ['hileras' => $campoAntes, 'grupos_hileras' => $this->resumen($antes)],
                ['hileras' => $hileras, 'grupos_hileras' => $this->resumen($limpios)],
                mb_substr("Mapa de etapas de {$c->nombre_campania} (campo {$c->campo})" . ($motivo ? ". {$motivo}" : ''), 0, 255));
        });
    }

    /** Valida y ordena lo que viene del editor. */
    private function validar(CampoCampania $c, int $hileras, array $sectores): array
    {
        if ($hileras < 1 || $hileras > 500) {
            throw new ErrorControlado('El número de hileras debe estar entre 1 y 500.');
        }
        // Otras campañas del campo no pueden quedar con hileras que ya no existen
        $otra = CampaniaSector::whereHas('campania', fn($q) => $q->where('campo', $c->campo)->where('id', '<>', $c->id))
            ->where('hilera_hasta', '>', $hileras)->with('campania:id,nombre_campania')->first();
        if ($otra) {
            throw new ErrorControlado("La campaña {$otra->campania->nombre_campania} del campo {$c->campo} usa hasta la hilera {$otra->hilera_hasta}: "
                . 'corrige primero su mapa o deja al menos esas hileras.');
        }
        if (!$sectores) {
            throw new ErrorControlado('Debe haber al menos un grupo de hileras.');
        }
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = $c->fecha_fin ? Carbon::parse($c->fecha_fin)->toDateString() : null;
        $limpios = [];
        $ocupadas = [];
        foreach (array_values($sectores) as $i => $s) {
            $nombre = trim((string) ($s['nombre'] ?? '')) ?: 'Grupo ' . ($i + 1);
            $desde = (int) ($s['hilera_desde'] ?? 0);
            $hasta = (int) ($s['hilera_hasta'] ?? 0);
            if ($desde < 1 || $hasta < $desde || $hasta > $hileras) {
                throw new ErrorControlado("{$nombre}: las hileras deben ir de 1 a {$hileras} (desde ≤ hasta).");
            }
            for ($h = $desde; $h <= $hasta; $h++) {
                if (isset($ocupadas[$h])) {
                    throw new ErrorControlado("La hilera {$h} está en {$ocupadas[$h]} y en {$nombre}: cada hilera va en un solo grupo.");
                }
                $ocupadas[$h] = $nombre;
            }
            $etapas = [];
            foreach ($s['etapas'] ?? [] as $e) {
                $etapa = (string) ($e['etapa'] ?? '');
                $fecha = trim((string) ($e['fecha_desde'] ?? ''));
                if ($etapa === '' && $fecha === '') {
                    continue;
                }
                if (!isset(CampaniaSectorReglas::ETAPAS[$etapa])) {
                    throw new ErrorControlado("{$nombre}: elige la etapa.");
                }
                try {
                    $fecha = Carbon::parse($fecha)->toDateString();
                } catch (\Throwable) {
                    throw new ErrorControlado("{$nombre}: falta la fecha de " . CampaniaSectorReglas::nombre($etapa) . '.');
                }
                if ($fecha < $inicio || ($fin && $fecha > $fin)) {
                    throw new ErrorControlado("{$nombre}: " . CampaniaSectorReglas::nombre($etapa) . ' empieza el ' . Carbon::parse($fecha)->format('d/m/Y')
                        . ', fuera de la campaña (' . Carbon::parse($inicio)->format('d/m/Y') . ' a ' . ($fin ? Carbon::parse($fin)->format('d/m/Y') : 'hoy') . ').');
                }
                $etapas["{$etapa}|{$fecha}"] = ['etapa' => $etapa, 'fecha_desde' => $fecha];
            }
            $etapas = collect($etapas)->sortBy('fecha_desde')->values()->all();
            $limpios[] = ['nombre' => mb_substr($nombre, 0, 40), 'hilera_desde' => $desde, 'hilera_hasta' => $hasta, 'etapas' => $etapas];
        }
        return collect($limpios)->sortBy('hilera_desde')->values()->all();
    }

    /** Fechas separadas en tandas (más de DIAS_GRUPO días entre una y otra = otra tanda). */
    private function tandas(array $fechas): array
    {
        sort($fechas);
        $tandas = [];
        $anterior = null;
        foreach ($fechas as $f) {
            if (!$anterior || Carbon::parse($anterior)->diffInDays(Carbon::parse($f)) > CampaniaSectorReglas::DIAS_GRUPO) {
                $tandas[] = [];
            }
            $tandas[count($tandas) - 1][] = $f;
            $anterior = $f;
        }
        return $tandas;
    }

    private function resumen(array $sectores): array
    {
        return array_map(fn($s) => "{$s['nombre']} (hileras {$s['hilera_desde']}–{$s['hilera_hasta']}): "
            . implode(', ', array_map(fn($e) => CampaniaSectorReglas::nombre($e['etapa']) . ' ' . $e['fecha_desde'], $s['etapas'] ?? [])), $sectores);
    }
}
