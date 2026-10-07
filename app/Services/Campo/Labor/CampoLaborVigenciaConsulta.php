<?php

namespace App\Services\Campo\Labor;

use App\Models\Configuracion;
use App\Models\Labores;
use App\Models\LaborVigencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Qué labor significaba un código en una fecha. Los registros guardan solo el código; si el código se reutilizó,
 * lo anterior a labores.vigente_desde se lee de labor_vigencias. Así un reporte antiguo se ve como era entonces
 * y lo nuevo como es ahora.
 *
 * También da el último uso de cada código y los códigos disponibles para reutilizar (sin uso hace más de N meses,
 * configurable en Sistema → Configuración).
 */
class CampoLaborVigenciaConsulta
{
    public const CONFIG_MESES_REUTILIZAR = 'labor_meses_sin_uso_para_reutilizar';
    public const MESES_REUTILIZAR = 3;

    /** @var array<int, array>|null codigo => [vigencias pasadas], cargadas una vez por petición */
    private ?array $vigencias = null;
    /** @var array<int, Labores>|null */
    private ?array $actuales = null;

    /**
     * La labor del código en esa fecha, como objeto simple (codigo, nombre_labor, codigo_mano_obra,
     * tipo_asistencia_codigo, unidades, historica). null si el código no existe.
     */
    public function labor($codigo, $fecha = null): ?object
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }
        $codigo = (int) $codigo;
        $this->cargar();
        $actual = $this->actuales[$codigo] ?? null;
        $dia = $fecha ? Carbon::parse($fecha)->toDateString() : null;

        // Antes de que el código pasara a la labor actual: la vigencia que cubría esa fecha
        if ($dia && (!$actual || ($actual->vigente_desde && $dia < $actual->vigente_desde->toDateString()))) {
            foreach ($this->vigencias[$codigo] ?? [] as $v) {
                if ((!$v['desde'] || $v['desde'] <= $dia) && $dia <= $v['hasta']) {
                    return (object) ($v + ['codigo' => $codigo, 'historica' => true]);
                }
            }
        }
        return $actual ? (object) [
            'codigo' => $codigo,
            'nombre_labor' => $actual->nombre_labor,
            'codigo_mano_obra' => $actual->codigo_mano_obra,
            'tipo_asistencia_codigo' => $actual->tipo_asistencia_codigo,
            'unidades' => $actual->unidades,
            'historica' => false,
        ] : null;
    }

    public function nombre($codigo, $fecha = null): ?string
    {
        return $this->labor($codigo, $fecha)?->nombre_labor;
    }

    /** Historia completa de un código, de lo más reciente a lo más antiguo (la actual primero). */
    public function historia(int $codigo): array
    {
        $actual = Labores::withTrashed()->where('codigo', $codigo)->first();
        $lista = $actual ? [[
            'nombre_labor' => $actual->nombre_labor,
            'codigo_mano_obra' => $actual->codigo_mano_obra,
            'desde' => $actual->vigente_desde?->toDateString(),
            'hasta' => null,
            'motivo' => null,
            'actual' => true,
        ]] : [];
        foreach (LaborVigencia::with('creadoPor:id,name')->where('codigo', $codigo)->orderByDesc('hasta')->get() as $v) {
            $lista[] = [
                'nombre_labor' => $v->nombre_labor,
                'codigo_mano_obra' => $v->codigo_mano_obra,
                'desde' => $v->desde?->toDateString(),
                'hasta' => $v->hasta->toDateString(),
                'motivo' => trim(($v->motivo ?? '') . ($v->creadoPor ? ' · reasignó ' . $v->creadoPor->name : '')),
                'actual' => false,
            ];
        }
        return $lista;
    }

    /**
     * Fecha del último registro que usa cada código (planilla, cuadrilla y bonos).
     *
     * @param int[]|null $codigos null = todos
     * @return array<int, string> codigo => Y-m-d
     */
    public function ultimosUsos(?array $codigos = null): array
    {
        $consultas = [
            DB::table('plan_detalles_horas as d')->join('plan_registros_diarios as r', 'r.id', '=', 'd.plan_reg_dia_id')
                ->selectRaw('d.codigo_labor as codigo, MAX(r.fecha) as fecha')->groupBy('d.codigo_labor'),
            DB::table('cuad_detalles_horas as d')->join('cuad_registros_diarios as r', 'r.id', '=', 'd.registro_diario_id')
                ->selectRaw('d.codigo_labor as codigo, MAX(r.fecha) as fecha')->groupBy('d.codigo_labor'),
            DB::table('actividades')->selectRaw('codigo_labor as codigo, MAX(fecha) as fecha')->groupBy('codigo_labor'),
        ];
        $ultimos = [];
        foreach ($consultas as $q) {
            if ($codigos !== null) {
                $q->havingRaw('codigo IN (' . implode(',', array_map('intval', $codigos) ?: [0]) . ')');
            }
            foreach ($q->get() as $fila) {
                if ($fila->codigo === null) {
                    continue;
                }
                $fecha = Carbon::parse($fila->fecha)->toDateString();
                $ultimos[(int) $fila->codigo] = max($ultimos[(int) $fila->codigo] ?? '', $fecha);
            }
        }
        return $ultimos;
    }

    public static function mesesParaReutilizar(): int
    {
        $valor = (int) Configuracion::find(self::CONFIG_MESES_REUTILIZAR)?->valor;
        return $valor > 0 ? $valor : self::MESES_REUTILIZAR;
    }

    public static function guardarMesesParaReutilizar($meses): void
    {
        $meses = (int) $meses;
        if ($meses < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages(['meses_reutilizar' => 'Indica al menos 1 mes.']);
        }
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_MESES_REUTILIZAR], [
            'valor' => (string) $meses,
            'descripcion' => 'Meses sin uso para que un código de labor se ofrezca para reutilizarse en otra labor',
        ]);
    }

    /**
     * Códigos que se pueden reutilizar: los que tuvieron registros y no se usan hace más de N meses.
     * Primero los que llevan más tiempo sin uso.
     *
     * @return array<int, array{id:int, codigo:int, nombre_labor:string, ultimo_uso:string, meses_sin_uso:int, desactivada:bool}>
     */
    public function disponibles(): array
    {
        $limite = now()->subMonthsNoOverflow(self::mesesParaReutilizar())->toDateString();
        $viejos = array_filter($this->ultimosUsos(), fn($fecha) => $fecha < $limite);
        asort($viejos);

        $labores = Labores::withTrashed()->whereIn('codigo', array_keys($viejos))->get()->keyBy('codigo');
        $lista = [];
        foreach ($viejos as $codigo => $fecha) {
            if ($labor = $labores->get($codigo)) {
                $lista[] = [
                    'id' => $labor->id,
                    'codigo' => $codigo,
                    'nombre_labor' => $labor->nombre_labor,
                    'ultimo_uso' => $fecha,
                    'meses_sin_uso' => (int) Carbon::parse($fecha)->diffInMonths(now()),
                    'desactivada' => $labor->trashed(),
                ];
            }
        }
        return $lista;
    }

    private function cargar(): void
    {
        if ($this->actuales !== null) {
            return;
        }
        $this->actuales = Labores::withTrashed()->get()->keyBy(fn($l) => (int) $l->codigo)->all();
        $this->vigencias = [];
        foreach (LaborVigencia::orderByDesc('hasta')->get() as $v) {
            $this->vigencias[(int) $v->codigo][] = [
                'nombre_labor' => $v->nombre_labor,
                'codigo_mano_obra' => $v->codigo_mano_obra,
                'tipo_asistencia_codigo' => $v->tipo_asistencia_codigo,
                'unidades' => $v->unidades,
                'desde' => $v->desde?->toDateString(),
                'hasta' => $v->hasta->toDateString(),
            ];
        }
    }
}
