<?php

namespace App\Services\Campo\Actividad;

use App\Models\CampoActividadAceptada;
use App\Services\Campo\CampoServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cada campo, en un día, debería tener un solo código de labor (un código puede ser una metida de mano).
 *
 *  - revisar(): antes de guardar horas, junta lo ya registrado ese día (sin los trabajadores que se están guardando,
 *    porque sus horas se reemplazan) con lo que se guarda. Los campos con más de un código y alguno que todavía no
 *    fue aceptado se devuelven para preguntar "¿es correcto?" (el código no aceptado va marcado como nuevo).
 *  - aceptar(): guarda los códigos de esos campos en campo_actividades_aceptadas; ya no se vuelve a preguntar.
 *  - limpiar(): después de guardar, quita los códigos aceptados que ya ningún trabajador hizo en ese campo ese día.
 */
class CampoActividadProceso
{
    public function __construct(private CampoActividadConsulta $consulta)
    {
    }

    /**
     * @param array $tramos [['campo', 'labor', 'origen' => 'planilla'|'cuadrilla', 'trabajador_id'], …] lo que se guarda
     * @return array [['campo', 'codigos' => [['codigo', 'labor', 'planilla', 'cuadrilla', 'nuevo']]], …]
     */
    public function revisar(string $fecha, array $tramos, array $excluirPlan = [], array $excluirCuad = []): array
    {
        $mapa = CampoServicio::obtenerMapaCamposNormalizados();
        $nuevas = [];
        foreach ($tramos as $t) {
            $campo = $mapa[mb_strtolower(trim((string) ($t['campo'] ?? '')))] ?? null;
            $codigo = trim((string) ($t['labor'] ?? ''));
            if (!$campo || $codigo === '' || !is_numeric($codigo) || !CampoActividadReglas::seRevisa($campo)) {
                continue;
            }
            $nuevas[] = (object) ['campo' => $campo, 'labor' => (int) $codigo, 'origen' => $t['origen'] ?? 'planilla',
                'trabajador_id' => (string) ($t['trabajador_id'] ?? uniqid())];
        }
        if (!$nuevas) {
            return [];
        }
        $campos = array_values(array_unique(array_column($nuevas, 'campo')));

        $todos = $this->consulta->tramos($fecha, $fecha, $campos, $excluirPlan, $excluirCuad)->concat($nuevas);
        $aceptados = CampoActividadAceptada::whereDate('fecha', $fecha)->whereIn('campo_nombre', $campos)
            ->get(['campo_nombre', 'codigo_labor'])->groupBy('campo_nombre')
            ->map(fn($g) => $g->pluck('codigo_labor')->map(fn($c) => (int) $c)->all());
        $nombres = DB::table('labores')->whereNotNull('codigo')->pluck('nombre_labor', 'codigo');

        $preguntar = [];
        foreach ($todos->groupBy('campo') as $campo => $filas) {
            $porCodigo = $filas->groupBy('labor');
            if ($porCodigo->count() < 2) {
                continue;
            }
            $yaAceptados = $aceptados[$campo] ?? [];
            $codigos = $porCodigo->map(fn($f, $codigo) => [
                'codigo' => (int) $codigo,
                'labor' => $nombres[$codigo] ?? '',
                'planilla' => $f->where('origen', 'planilla')->unique('trabajador_id')->count(),
                'cuadrilla' => $f->where('origen', 'cuadrilla')->unique('trabajador_id')->count(),
                'nuevo' => !in_array((int) $codigo, $yaAceptados, true),
            ])->sortByDesc(fn($c) => $c['planilla'] + $c['cuadrilla'])->values()->all();
            if (collect($codigos)->contains('nuevo', true)) {
                $preguntar[] = ['campo' => (string) $campo, 'codigos' => $codigos];
            }
        }
        return collect($preguntar)->sortBy('campo', SORT_NATURAL)->values()->all();
    }

    /** @param array $campos campo => [códigos] */
    public function aceptar(string $fecha, array $campos): void
    {
        DB::transaction(function () use ($fecha, $campos) {
            foreach ($campos as $campo => $codigos) {
                foreach (array_unique(array_map('intval', (array) $codigos)) as $codigo) {
                    CampoActividadAceptada::updateOrCreate(
                        ['campo_nombre' => $campo, 'fecha' => $fecha, 'codigo_labor' => $codigo],
                        ['aceptado_por' => auth()->id()]
                    );
                }
            }
        });
    }

    /**
     * Quita los códigos aceptados de ese día que ya nadie hizo en su campo (se corrigió una metida de mano).
     *
     * @return string[] mensajes para avisar
     */
    public function limpiar(string $fecha): array
    {
        $aceptados = CampoActividadAceptada::whereDate('fecha', $fecha)->get();
        if ($aceptados->isEmpty()) {
            return [];
        }
        $usados = $this->consulta->tramos($fecha, $fecha, $aceptados->pluck('campo_nombre')->unique()->values()->all())
            ->map(fn($t) => "{$t->campo}|{$t->labor}")->unique()->flip();
        $nombres = DB::table('labores')->whereNotNull('codigo')->pluck('nombre_labor', 'codigo');
        $dia = Carbon::parse($fecha)->format('d/m/Y');

        $mensajes = [];
        foreach ($aceptados as $a) {
            if (!isset($usados["{$a->campo_nombre}|{$a->codigo_labor}"])) {
                $mensajes[] = "El código {$a->codigo_labor} (" . ($nombres[$a->codigo_labor] ?? '') . ") ya no lo realizó ningún trabajador "
                    . "en el campo {$a->campo_nombre} el {$dia}: se quitó de sus actividades aceptadas.";
                $a->delete();
            }
        }
        return $mensajes;
    }
}
