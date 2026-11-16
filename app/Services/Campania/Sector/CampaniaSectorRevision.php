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
 * Al guardar horas (registro diario de planilla, cuadrilla): cada labor de etapa debe caer en la etapa de alguna
 * siembra del campo ese día (mapa de etapas de la campaña).
 *
 *  - Si ninguna siembra está en esa etapa pero una está en la etapa anterior del ciclo (siembra → infestación,
 *    infestación → cosecha…), se pregunta si esa siembra pasa a la nueva etapa desde ese día.
 *  - Labores de siembra cuando el campo ya pasó la siembra: con pocos jornales es replante (se guarda sin cambiar el
 *    mapa); con RELLENO_MIN_JORNALES o más ese día se pregunta si es una nueva siembra (relleno) y se agrega al mapa.
 *  - Si no (ej. infestar en preparación de terreno, podar en siembra), no se guarda. Tampoco si una misma siembra
 *    quedaría en dos etapas el mismo día (si parte del campo se sembró en otra fecha, debe estar en el mapa).
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
        $trabajadoresNuevos = []; // campo => [codigo => [trabajador => true]] (para contar jornales de siembra)
        foreach ($tramos as $t) {
            $campo = $mapa[mb_strtolower(trim((string) ($t['campo'] ?? '')))] ?? null;
            $codigo = trim((string) ($t['labor'] ?? ''));
            if (!$campo || !is_numeric($codigo) || !CampoActividadReglas::seRevisa($campo)) {
                continue;
            }
            $nuevas[$campo][(int) $codigo] = true;
            $trabajadoresNuevos[$campo][(int) $codigo][($t['origen'] ?? 'planilla') . '|' . ($t['trabajador_id'] ?? uniqid())] = true;
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

            // Las que no caen en ninguna etapa: ¿alguna siembra puede pasar a esa etapa? (cada una avanza una vez)
            $propuestos = [];
            $nuevaSiembra = [];
            $simulados = $sectores;
            foreach ($revisar as $l) {
                if ($this->cubre($sectores, $fecha, $l['familia']) || collect($propuestos)->contains('etapa', $l['familia'])) {
                    continue;
                }
                $i = collect($simulados)->search(fn($s, $k) => !isset($propuestos[$k])
                    && CampaniaSectorReglas::puedeAvanzar(CampaniaSectorConsulta::etapaEn($s['etapas'], $fecha), $l['etapa']));
                if ($i === false && $l['familia'] === 'siembra') {
                    // Sembrar cuando el campo ya pasó la siembra: con pocos jornales es replante de la misma siembra (se guarda
                    // sin cambiar el mapa); con RELLENO_MIN_JORNALES o más ese día es un relleno: otra siembra con su fecha
                    if (!$nuevaSiembra && $this->jornalesSiembra($campo, $quedan, $trabajadoresNuevos[$campo] ?? [], $etapaDe) >= CampaniaSectorReglas::RELLENO_MIN_JORNALES) {
                        $nuevaSiembra = [$l];
                    }
                    continue;
                }
                if ($i === false) {
                    if ($l['familia'] !== 'siembra') {
                        $errores[] = $this->motivo($campo, $campania, $sectores, $fecha, $l);
                    }
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

            // Al pasar una siembra de etapa no puede quedar fuera otra labor de ese mismo día (una siembra no está en dos
            // etapas el mismo día). Si parte del campo sigue en la etapa anterior, es otra siembra: debe estar en el mapa.
            if ($propuestos) {
                $quedarianFuera = $delDia->filter(fn($l) => $this->cubre($sectores, $fecha, $l['familia']) && !$this->cubre($simulados, $fecha, $l['familia']));
                if ($quedarianFuera->isNotEmpty()) {
                    $nuevasEtapas = collect($propuestos)->map(fn($p) => CampaniaSectorReglas::FAMILIAS[$p['etapa']])->unique()->implode(' y ');
                    $errores[] = "Campo {$campo}, " . Carbon::parse($fecha)->format('d/m/Y') . ": se registra {$nuevasEtapas}, pero ese día también hay "
                        . $quedarianFuera->map(fn($l) => "{$l['codigo']} · {$l['nombre']} (" . CampaniaSectorReglas::FAMILIAS[$l['familia']] . ')')->implode(', ')
                        . '. Una misma siembra no puede estar en dos etapas el mismo día: corrige el código, o si parte del campo se sembró en otra fecha '
                        . '(relleno), agrega esa siembra en Campañas → Mapa de etapas.';
                }
            }

            // Relleno: la nueva siembra entra al mapa desde ese día (las hileras se reparten parejo)
            if ($nuevaSiembra) {
                $avances[] = [
                    'tipo' => 'nueva_siembra',
                    'campania_id' => $campania->id,
                    'sector_id' => null,
                    'campo' => $campo,
                    'campania' => $campania->nombre_campania,
                    'grupo' => CampaniaSectorReglas::nombreSiembra(count($sectores) + 1),
                    'hileras' => '',
                    'unico' => false,
                    'actual' => null,
                    'etapa' => 'siembra',
                    'fecha' => $fecha,
                    'labores' => array_map(fn($l) => "{$l['codigo']} · {$l['nombre']}", $revisar->where('familia', 'siembra')->values()->all()),
                    'siembras' => array_map(fn($s) => "{$s['nombre']}: " . mb_strtolower(CampaniaSectorReglas::nombre(CampaniaSectorConsulta::etapaEn($s['etapas'], $fecha))), $sectores),
                ];
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
                    'tipo' => 'avance',
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
     * Aplica lo aceptado (con las mismas protecciones que el mapa de etapas):
     *  - avance: la siembra pasa a su nueva etapa desde ese día;
     *  - nueva_siembra: relleno, se agrega la siembra con esa fecha de siembra (las hileras se reparten parejo).
     *
     * @param array $avances lo que devolvió revisar()
     */
    public function avanzar(array $avances): void
    {
        DB::transaction(function () use ($avances) {
            foreach (collect($avances)->groupBy('campania_id') as $campaniaId => $lista) {
                $c = CampoCampania::findOrFail($campaniaId);
                $sectores = $this->consulta->sectores($c);
                $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
                $textos = [];
                foreach ($lista as $a) {
                    $fecha = Carbon::parse($a['fecha'])->toDateString();
                    if (($a['tipo'] ?? 'avance') === 'nueva_siembra') {
                        $sectores = CampaniaSectorProceso::conSiembraNueva($sectores, $fecha, $hileras, $c);
                        $textos[] = 'nueva siembra (relleno) sembrada el ' . Carbon::parse($fecha)->format('d/m/Y');
                        continue;
                    }
                    $i = collect($sectores)->search(fn($s) => (int) ($s['id'] ?? 0) === (int) $a['sector_id']);
                    if ($i === false || !isset(CampaniaSectorReglas::ETAPAS[$a['etapa']])) {
                        throw new ErrorControlado("El mapa de etapas de {$c->nombre_campania} (campo {$c->campo}) cambió mientras tanto: vuelve a guardar.");
                    }
                    $sectores = self::conEtapa($sectores, $i, $a['etapa'], $fecha);
                    $textos[] = "{$sectores[$i]['nombre']} → " . CampaniaSectorReglas::nombre($a['etapa']) . ' desde ' . Carbon::parse($fecha)->format('d/m/Y');
                }
                $this->proceso->guardar($c, $hileras, $sectores, 'Al registrar horas: ' . implode(', ', $textos));
            }
        });
    }

    /** Trabajadores (jornales) con labores de siembra en el campo ese día: lo que queda registrado + lo que se guarda. */
    private function jornalesSiembra(string $campo, $quedan, array $nuevos, callable $etapaDe): int
    {
        $esSiembra = fn($codigo) => ($etapaDe((int) $codigo)['familia'] ?? null) === 'siembra';
        $quienes = $quedan->where('campo', $campo)->filter(fn($t) => $esSiembra($t->labor))
            ->mapWithKeys(fn($t) => ["{$t->origen}|{$t->trabajador_id}" => true])->all();
        foreach ($nuevos as $codigo => $trabajadores) {
            if ($esSiembra($codigo)) {
                $quienes += $trabajadores;
            }
        }
        return count($quienes);
    }

    /** ¿Alguna siembra está en esa familia de etapas en la fecha? */
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
        return collect((array) $avances)->filter(fn($a) => is_array($a) && isset($a['campania_id'], $a['etapa'], $a['fecha']))
            ->map(fn($a) => ['tipo' => ($a['tipo'] ?? 'avance') === 'nueva_siembra' ? 'nueva_siembra' : 'avance',
                'campania_id' => (int) $a['campania_id'], 'sector_id' => (int) ($a['sector_id'] ?? 0), 'etapa' => (string) $a['etapa'],
                'fecha' => (string) $a['fecha'], 'grupo' => (string) ($a['grupo'] ?? 'Siembra')])
            ->filter(fn($a) => $a['tipo'] === 'nueva_siembra'
                ? CampoCampania::whereKey($a['campania_id'])->exists()
                : CampaniaSector::whereKey($a['sector_id'])->where('campo_campania_id', $a['campania_id'])->exists())
            ->values()->all();
    }
}
