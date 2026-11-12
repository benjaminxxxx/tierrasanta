<?php

namespace App\Services\Campania\Sector;

use App\Exceptions\ErrorControlado;
use App\Models\CampaniaSector;
use App\Models\CampoCampania;
use App\Services\Campo\Actividad\CampoActividadConsulta;
use App\Services\Campo\Actividad\CampoActividadReglas;
use App\Services\Campo\CampoServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Al guardar horas (registro diario de planilla, cuadrilla): cada labor de etapa debe caer en la etapa de algún grupo
 * de hileras del campo ese día (mapa de etapas de la campaña).
 *
 *  - Si ningún grupo está en esa etapa pero uno está en la etapa anterior del ciclo (siembra → infestación,
 *    infestación → cosecha…), se pregunta si ese grupo pasa a la nueva etapa desde ese día.
 *  - Si no (ej. infestar en preparación de terreno, podar en siembra, volver a sembrar en infestación), no se guarda.
 *
 * Solo se revisan las combinaciones campo + labor que ese día aún no estaban registradas (corregir horas de un día
 * antiguo no se traba por lo que ya había). Labores sin etapa (culturales, riego, sanidad…) y FDM no cuentan; campos
 * sin campaña o campañas sin mapa tampoco.
 */
class CampaniaSectorRevision
{
    public function __construct(
        private CampoActividadConsulta $actividades,
        private CampaniaSectorConsulta $consulta,
        private CampaniaSectorProceso $proceso,
    ) {
    }

    /**
     * @param array $tramos [['campo', 'labor', 'origen' => 'planilla'|'cuadrilla', 'trabajador_id'], …] lo que se guarda
     * @param array $excluirPlan / $excluirCuad trabajadores cuyas horas de ese día se reemplazan
     * @return array avances a preguntar [['campania_id', 'sector_id', 'campo', 'campania', 'grupo', 'hileras', 'actual', 'etapa', 'fecha', 'labores'], …]
     * @throws ErrorControlado si alguna labor no cabe en ninguna etapa
     */
    public function revisar(string $fecha, array $tramos, array $excluirPlan = [], array $excluirCuad = []): array
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $mapa = CampoServicio::obtenerMapaCamposNormalizados();
        $labores = DB::table('labores')->whereNotNull('codigo')->get(['codigo', 'nombre_labor', 'codigo_mano_obra'])->keyBy('codigo');

        $nuevas = [];
        foreach ($tramos as $t) {
            $campo = $mapa[mb_strtolower(trim((string) ($t['campo'] ?? '')))] ?? null;
            $codigo = trim((string) ($t['labor'] ?? ''));
            if (!$campo || !is_numeric($codigo) || !CampoActividadReglas::seRevisa($campo)) {
                continue;
            }
            $nuevas[$campo][(int) $codigo] = true;
        }
        if (!$nuevas) {
            return [];
        }

        // Lo que ese día ya estaba registrado (todo) y lo que queda tras guardar (sin los trabajadores que se reemplazan)
        $campos = array_keys($nuevas);
        $yaEstaba = $this->actividades->tramos($fecha, $fecha, $campos)->map(fn($t) => "{$t->campo}|{$t->labor}")->flip();
        $quedan = $this->actividades->tramos($fecha, $fecha, $campos, $excluirPlan, $excluirCuad);

        $etapaDe = function (int $codigo) use ($labores) {
            $l = $labores[$codigo] ?? null;
            $etapa = CampaniaSectorReglas::etapaDeManoObra($l?->codigo_mano_obra);
            return $etapa ? ['codigo' => $codigo, 'nombre' => (string) $l->nombre_labor, 'etapa' => $etapa, 'familia' => CampaniaSectorReglas::familia($etapa)] : null;
        };

        $avances = [];
        $errores = [];
        foreach ($nuevas as $campo => $codigos) {
            // Labores de etapa del campo ese día: las que quedan registradas + las que se guardan
            $delDia = $quedan->where('campo', $campo)->pluck('labor')->map(fn($c) => (int) $c)->merge(array_keys($codigos))->unique()
                ->map($etapaDe)->filter()->keyBy('codigo');
            $revisar = $delDia->filter(fn($l) => !isset($yaEstaba["{$campo}|{$l['codigo']}"]));
            if ($revisar->isEmpty()) {
                continue;
            }
            $campania = CampoCampania::where('campo', $campo)->whereDate('fecha_inicio', '<=', $fecha)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha))
                ->orderByDesc('fecha_inicio')->first();
            $sectores = $campania ? $this->consulta->sectores($campania) : [];
            if (!$sectores) {
                continue;
            }

            // Las que no caen en ninguna etapa: ¿algún grupo puede pasar a esa etapa? (cada grupo avanza una vez)
            $propuestos = [];
            $simulados = $sectores;
            foreach ($revisar as $l) {
                if ($this->cubre($sectores, $fecha, $l['familia']) || collect($propuestos)->contains('etapa', $l['familia'])) {
                    continue;
                }
                $i = collect($simulados)->search(fn($s, $k) => !isset($propuestos[$k])
                    && CampaniaSectorReglas::puedeAvanzar(CampaniaSectorConsulta::etapaEn($s['etapas'], $fecha), $l['etapa']));
                if ($i === false) {
                    $errores[] = $this->motivo($campo, $campania, $sectores, $fecha, $l);
                    continue;
                }
                $nueva = $l['familia']; // re-infestación o cosecha de madres sin infestación previa → infestación
                $propuestos[$i] = ['actual' => CampaniaSectorConsulta::etapaEn($simulados[$i]['etapas'], $fecha), 'etapa' => $nueva, 'labores' => [$l]];
                $simulados = self::conEtapa($simulados, $i, $nueva, $fecha);
            }
            foreach ($revisar as $l) {
                foreach ($propuestos as $i => $p) {
                    if ($p['etapa'] === $l['familia'] && !in_array($l, $p['labores'], true)) {
                        $propuestos[$i]['labores'][] = $l;
                    }
                }
            }

            // Al pasar un grupo de etapa no puede quedar fuera otra labor de ese mismo día (un solo grupo no está en dos etapas)
            if ($propuestos) {
                $quedarianFuera = $delDia->filter(fn($l) => $this->cubre($sectores, $fecha, $l['familia']) && !$this->cubre($simulados, $fecha, $l['familia']));
                if ($quedarianFuera->isNotEmpty()) {
                    $nuevasEtapas = collect($propuestos)->map(fn($p) => CampaniaSectorReglas::FAMILIAS[$p['etapa']])->unique()->implode(' y ');
                    $errores[] = "Campo {$campo}, " . Carbon::parse($fecha)->format('d/m/Y') . ": se registra {$nuevasEtapas}, pero ese día también hay "
                        . $quedarianFuera->map(fn($l) => "{$l['codigo']} · {$l['nombre']} (" . CampaniaSectorReglas::FAMILIAS[$l['familia']] . ')')->implode(', ')
                        . '. Un grupo de hileras no puede estar en dos etapas el mismo día: corrige el código, o divide las hileras en grupos en Campañas → Mapa de etapas.';
                }
            }

            foreach ($propuestos as $i => $p) {
                $s = $sectores[$i];
                $avances[] = [
                    'campania_id' => $campania->id,
                    'sector_id' => $s['id'],
                    'campo' => $campo,
                    'campania' => $campania->nombre_campania,
                    'grupo' => $s['nombre'],
                    'hileras' => "{$s['hilera_desde']}–{$s['hilera_hasta']}",
                    'unico' => count($sectores) === 1,
                    'actual' => $p['actual'],
                    'etapa' => $p['etapa'],
                    'fecha' => $fecha,
                    'labores' => array_map(fn($l) => "{$l['codigo']} · {$l['nombre']}", $p['labores']),
                ];
            }
        }

        if ($errores) {
            throw new ErrorControlado('No se guardó. ' . implode(' ', array_unique($errores)));
        }
        return collect($avances)->sortBy('campo', SORT_NATURAL)->values()->all();
    }

    /**
     * Pasa los grupos aceptados a su nueva etapa (con las mismas protecciones que el mapa de etapas).
     *
     * @param array $avances lo que devolvió revisar()
     */
    public function avanzar(array $avances): void
    {
        DB::transaction(function () use ($avances) {
            foreach (collect($avances)->groupBy('campania_id') as $campaniaId => $lista) {
                $c = CampoCampania::findOrFail($campaniaId);
                $sectores = $this->consulta->sectores($c);
                foreach ($lista as $a) {
                    $i = collect($sectores)->search(fn($s) => (int) $s['id'] === (int) $a['sector_id']);
                    if ($i === false || !isset(CampaniaSectorReglas::ETAPAS[$a['etapa']])) {
                        throw new ErrorControlado("El mapa de etapas de {$c->nombre_campania} (campo {$c->campo}) cambió mientras tanto: vuelve a guardar.");
                    }
                    $sectores = self::conEtapa($sectores, $i, $a['etapa'], Carbon::parse($a['fecha'])->toDateString());
                }
                $this->proceso->guardar($c, CampaniaSectorConsulta::hilerasDelCampo($c->campo), $sectores,
                    'Avance de etapa al registrar horas: ' . collect($lista)->map(fn($a) => "{$a['grupo']} → " . CampaniaSectorReglas::nombre($a['etapa'])
                        . ' desde ' . Carbon::parse($a['fecha'])->format('d/m/Y'))->implode(', '));
            }
        });
    }

    /** ¿Algún grupo está en esa familia de etapas en la fecha? */
    private function cubre(array $sectores, string $fecha, string $familia): bool
    {
        foreach ($sectores as $s) {
            $e = CampaniaSectorConsulta::etapaEn($s['etapas'] ?? [], $fecha);
            if ($e && CampaniaSectorReglas::familia($e) === $familia) {
                return true;
            }
        }
        return false;
    }

    /** El grupo $i entra a $etapa en $fecha; si después ya empezaba esa misma etapa, ese inicio se adelanta (no se repite). */
    private static function conEtapa(array $sectores, int $i, string $etapa, string $fecha): array
    {
        $etapas = collect($sectores[$i]['etapas'] ?? [])->push(['etapa' => $etapa, 'fecha_desde' => $fecha])
            ->sortBy('fecha_desde')->values();
        $limpias = [];
        foreach ($etapas as $e) {
            $ultima = end($limpias);
            if ($ultima && $ultima['etapa'] === $e['etapa']) {
                continue;
            }
            $limpias[] = ['etapa' => $e['etapa'], 'fecha_desde' => $e['fecha_desde']];
        }
        $sectores[$i]['etapas'] = $limpias;
        return $sectores;
    }

    private function motivo(string $campo, CampoCampania $c, array $sectores, string $fecha, array $l): string
    {
        $estado = collect($sectores)->map(fn($s) => (count($sectores) > 1 ? "{$s['nombre']} (hileras {$s['hilera_desde']}–{$s['hilera_hasta']}): " : '')
            . mb_strtolower(CampaniaSectorReglas::nombre(CampaniaSectorConsulta::etapaEn($s['etapas'], $fecha))))->implode('; ');
        return "Campo {$campo}, " . Carbon::parse($fecha)->format('d/m/Y') . ": la labor {$l['codigo']} · {$l['nombre']} es de "
            . CampaniaSectorReglas::FAMILIAS[$l['familia']] . ', pero ' . (count($sectores) > 1 ? 'sus grupos de hileras están en ' : 'el campo está en ')
            . "{$estado} (campaña {$c->nombre_campania}) y no puede pasar directo a esa etapa. "
            . 'Revisa el código de la labor; si el campo de verdad está en esa etapa, corrígelo en Campañas → Mapa de etapas.';
    }

    /** Los avances que vienen del navegador (confirm) se vuelven a leer solo con lo necesario. */
    public static function limpiarAvances($avances): array
    {
        return collect((array) $avances)->filter(fn($a) => is_array($a) && isset($a['campania_id'], $a['sector_id'], $a['etapa'], $a['fecha']))
            ->map(fn($a) => ['campania_id' => (int) $a['campania_id'], 'sector_id' => (int) $a['sector_id'], 'etapa' => (string) $a['etapa'],
                'fecha' => (string) $a['fecha'], 'grupo' => (string) ($a['grupo'] ?? 'Grupo')])
            ->filter(fn($a) => CampaniaSector::whereKey($a['sector_id'])->where('campo_campania_id', $a['campania_id'])->exists())
            ->values()->all();
    }
}
