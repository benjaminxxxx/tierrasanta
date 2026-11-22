<?php

namespace App\Services\Evaluacion;

use App\Exports\Evaluacion\BrotesPorPisoExport;
use App\Models\CampoCampania;
use App\Models\EvalBrotesPorPiso;
use App\Services\Campania\CampaniaServicio;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Evaluaciones de brotes por piso. Cada siembra de la campaña tiene varias (una por fecha) y sus propios metros de cama
 * por hectárea del campo. Las métricas de la campaña (brotexpiso_*) suman la última evaluación de cada siembra.
 *
 * Crear, editar y eliminar quedan en la auditoría con quién lo hizo; eliminar es físico, pero la auditoría guarda
 * la evaluación completa con sus camas.
 */
class BrotesPorPisoServicio
{
    /** Promedios de una evaluación (los mismos nombres que en la campaña, sin el prefijo). */
    public const PROMEDIOS = [
        'promedio_actual_brotes_2piso' => '2° piso actual',
        'promedio_brotes_2piso_n_dias' => '2° piso para eliminar',
        'promedio_actual_brotes_3piso' => '3° piso actual',
        'promedio_brotes_3piso_n_dias' => '3° piso para eliminar',
        'promedio_actual_brotes_4piso' => '4° piso actual',
        'promedio_brotes_4piso_n_dias' => '4° piso para eliminar',
        'promedio_actual_total_brotes_2y3piso' => 'Total aptos',
        'promedio_total_brotes_2y3piso_n_dias' => 'Total para eliminar',
    ];

    protected CampaniaServicio $campaniaServicio;

    public function __construct(CampaniaServicio $campaniaServicio)
    {
        $this->campaniaServicio = $campaniaServicio;
    }

    public function exportar($filtros)
    {
        $crudos = EvalBrotesPorPiso::with(['campania', 'detalles', 'sector'])->orderBy('fecha')->get();
        $ordenado = $this->ordenarDatosExportBrotesPorPiso($filtros, $crudos);
        return Excel::download(new BrotesPorPisoExport($ordenado), date('Y-m-d') . '_brotes_por_piso.xlsx');
    }

    /**
     * Lista: solo la última evaluación (por fecha) de cada siembra de cada campaña, con cuántas tiene esa siembra.
     */
    public static function buscar(array $filtros, bool $paginado = true)
    {
        $siembra = 'COALESCE(campania_sector_id, 0)';
        $ultimasFechas = DB::table('eval_brotes_por_pisos')
            ->selectRaw("campania_id, {$siembra} as siembra, MAX(fecha) as ultima")
            ->groupBy('campania_id', DB::raw($siembra));
        $ultimas = DB::table('eval_brotes_por_pisos as e')
            ->joinSub($ultimasFechas, 'u', fn($j) => $j->on('u.campania_id', '=', 'e.campania_id')
                ->on('u.ultima', '=', 'e.fecha')->whereRaw('u.siembra = COALESCE(e.campania_sector_id, 0)'))
            ->selectRaw('MAX(e.id) as id')
            ->groupBy('e.campania_id', DB::raw('COALESCE(e.campania_sector_id, 0)'));

        $query = EvalBrotesPorPiso::query()
            ->select('eval_brotes_por_pisos.*')
            ->with(['campania', 'detalles', 'sector', 'creadoPor:id,name', 'actualizadoPor:id,name'])
            ->whereIn('id', $ultimas)
            ->addSelect(['evaluaciones_campania' => EvalBrotesPorPiso::from('eval_brotes_por_pisos as e2')
                ->selectRaw('COUNT(*)')->whereColumn('e2.campania_id', 'eval_brotes_por_pisos.campania_id')
                ->whereRaw('COALESCE(e2.campania_sector_id, 0) = COALESCE(eval_brotes_por_pisos.campania_sector_id, 0)')])
            ->orderByDesc('fecha')->orderByDesc('id');

        if (!empty($filtros['campo'])) {
            $query->whereHas('campania', fn($q) => $q->where('campo', $filtros['campo']));
        }
        if (!empty($filtros['campania_id'])) {
            $query->where('campania_id', $filtros['campania_id']);
        }
        if (!empty($filtros['evaluador'])) {
            $query->where('evaluador', 'like', '%' . $filtros['evaluador'] . '%');
        }
        if (!empty($filtros['fecha'])) {
            $query->whereDate('fecha', $filtros['fecha']);
        }

        return $paginado ? $query->paginate(20) : $query->get();
    }

    /**
     * Evaluaciones de una siembra por fecha, con sus promedios y la diferencia contra la anterior.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evaluacionesDeSiembra(int $campaniaId, int $sectorId): array
    {
        $anterior = null;
        return EvalBrotesPorPiso::with(['detalles', 'creadoPor:id,name', 'actualizadoPor:id,name'])
            ->where('campania_id', $campaniaId)->where('campania_sector_id', $sectorId)->orderBy('fecha')->orderBy('id')->get()
            ->map(function (EvalBrotesPorPiso $e) use (&$anterior) {
                $promedios = [];
                foreach (array_keys(self::PROMEDIOS) as $clave) {
                    // 4° piso: null si la evaluación no lo tiene (no había)
                    $valor = $e->$clave === null ? null : (float) $e->$clave;
                    $previo = $anterior[$clave]['valor'] ?? null;
                    $promedios[$clave] = [
                        'valor' => $valor,
                        'diferencia' => $valor !== null && $previo !== null ? $valor - $previo : null,
                    ];
                }
                $fila = [
                    'id' => $e->id,
                    'fecha' => $e->fecha?->toDateString(),
                    'evaluador' => $e->evaluador,
                    'metros_cama_ha' => (float) $e->metros_cama_ha,
                    'camas' => $e->detalles->count(),
                    'promedios' => $promedios,
                    'registrado' => trim(($e->creadoPor?->name ?? 'Sin registro') . ' · ' . $e->created_at?->format('d/m/Y H:i')),
                    'editado' => $e->actualizado_por ? trim($e->actualizadoPor?->name . ' · ' . $e->updated_at?->format('d/m/Y H:i')) : null,
                ];
                $anterior = $promedios;
                return $fila;
            })->all();
    }

    /** Filas de camas de una evaluación, con los valores por hectárea calculados. */
    public function detallesDeEvaluacion(int $id): array
    {
        return EvalBrotesPorPiso::with('detalles')->findOrFail($id)->detalles->map(fn($d) => [
            'numero_cama' => $d->numero_cama,
            'longitud_cama' => $d->longitud_cama,
            'brotes_aptos_2p_actual' => $d->brotes_aptos_2p_actual,
            'brotes_aptos_2p_despues_n_dias' => $d->brotes_aptos_2p_despues_n_dias,
            'brotes_aptos_3p_actual' => $d->brotes_aptos_3p_actual,
            'brotes_aptos_3p_despues_n_dias' => $d->brotes_aptos_3p_despues_n_dias,
            'brotes_aptos_4p_actual' => $d->brotes_aptos_4p_actual,
            'brotes_aptos_4p_despues_n_dias' => $d->brotes_aptos_4p_despues_n_dias,
            'brotes_2p_actual_por_mt' => round($d->brotes_2p_actual_por_mt, 2),
            'brotes_2p_despues_por_mt' => round($d->brotes_2p_despues_por_mt, 2),
            'brotes_3p_actual_por_mt' => round($d->brotes_3p_actual_por_mt, 2),
            'brotes_3p_despues_por_mt' => round($d->brotes_3p_despues_por_mt, 2),
            'brotes_4p_actual_por_mt' => $d->brotes_aptos_4p_actual === null ? null : round($d->brotes_4p_actual_por_mt, 2),
            'brotes_4p_despues_por_mt' => $d->brotes_aptos_4p_despues_n_dias === null ? null : round($d->brotes_4p_despues_por_mt, 2),
            'total_actual_por_mt' => round($d->total_actual_por_mt, 2),
            'total_despues_por_mt' => round($d->total_despues_por_mt, 2),
        ])->all();
    }

    /** Metros de cama por hectárea de la siembra (los de cualquiera de sus evaluaciones: son los mismos). */
    public function metrosCamaDeSiembra(int $sectorId): ?float
    {
        $metros = EvalBrotesPorPiso::where('campania_sector_id', $sectorId)->orderByDesc('fecha')->value('metros_cama_ha');
        return $metros !== null ? (float) $metros : null;
    }

    /**
     * Crea (sin id) o edita una evaluación. Devuelve su id.
     *
     * @param array{id?:?int, campania_id:int, campania_sector_id:int, fecha:string, evaluador:string, metros_cama_ha:float, detalles:array} $datos
     */
    public function registrar($datos)
    {
        $this->validarDatos($datos);
        EvaluacionSiembraConsulta::exigirAbierta(CampoCampania::findOrFail($datos['campania_id']));

        return DB::transaction(function () use ($datos) {
            $antes = !empty($datos['id']) ? $this->instantanea(EvalBrotesPorPiso::with('detalles')->findOrFail($datos['id'])) : null;
            $evaluacion = $this->guardarCabecera($datos);
            $this->guardarDetalles($evaluacion, $datos['detalles']);

            // Los metros de cama son de la siembra: si cambiaron, cambian en todas las evaluaciones de esa siembra
            $otras = EvalBrotesPorPiso::where('campania_sector_id', $evaluacion->campania_sector_id)->where('id', '<>', $evaluacion->id)
                ->where('metros_cama_ha', '<>', $evaluacion->metros_cama_ha)->get();
            foreach ($otras as $otra) {
                $previo = ['metros_cama_ha' => (float) $otra->metros_cama_ha];
                $otra->update(['metros_cama_ha' => $evaluacion->metros_cama_ha, 'actualizado_por' => auth()->id()]);
                AuditoriaServicio::registrar(EvalBrotesPorPiso::class, $otra->id, 'editar', $previo, ['metros_cama_ha' => (float) $otra->metros_cama_ha],
                    'Metros de cama/ha de la siembra cambiados en la evaluación del ' . $evaluacion->fecha->format('d/m/Y'));
            }

            $despues = $this->instantanea($evaluacion->fresh('detalles'));
            $antes
                ? AuditoriaServicio::registrar(EvalBrotesPorPiso::class, $evaluacion->id, 'editar', $antes, $despues, $this->descripcion($evaluacion))
                : AuditoriaServicio::registrar(EvalBrotesPorPiso::class, $evaluacion->id, 'crear', null, $despues, $this->descripcion($evaluacion));

            $this->recalcularMetricasCampania($evaluacion->campania_id);
            return $evaluacion->id;
        });
    }

    /** Eliminación física; la auditoría conserva la evaluación completa con sus camas. */
    public function eliminar(int $id)
    {
        DB::transaction(function () use ($id) {
            $evaluacion = EvalBrotesPorPiso::with(['detalles', 'campania'])->findOrFail($id);
            EvaluacionSiembraConsulta::exigirAbierta($evaluacion->campania);
            AuditoriaServicio::registrar(EvalBrotesPorPiso::class, $evaluacion->id, 'eliminar', $this->instantanea($evaluacion), null,
                'Eliminación de ' . $this->descripcion($evaluacion));
            $campaniaId = $evaluacion->campania_id;
            $evaluacion->delete(); // las camas se van en cascada

            $this->recalcularMetricasCampania($campaniaId);
        });
    }

    /**
     * La campaña muestra la suma de la última evaluación de cada siembra (o nada si ya no tiene): cada siembra mide sus
     * brotes con sus metros de cama por ha del campo. Con una sola siembra es su última evaluación, igual que antes.
     */
    public function recalcularMetricasCampania(int $campaniaId): void
    {
        $ultimas = EvalBrotesPorPiso::with('detalles')->where('campania_id', $campaniaId)->orderBy('fecha')->orderBy('id')->get()
            ->groupBy(fn($e) => (int) $e->campania_sector_id)->map->last()->values();
        if ($ultimas->isEmpty()) {
            $this->campaniaServicio->actualizarMetricas($campaniaId, $this->calcularMetricasNull());
            return;
        }
        $metricas = $ultimas->map(fn($e) => $this->calcularMetricas($e));
        $total = [];
        foreach (array_keys($metricas->first()) as $clave) {
            $valores = $metricas->pluck($clave)->filter(fn($v) => $v !== null);
            $total[$clave] = $clave === 'brotexpiso_fecha_evaluacion' ? $valores->max()
                : ($valores->isEmpty() ? null : $valores->sum());
        }
        $this->campaniaServicio->actualizarMetricas($campaniaId, $total);
    }

    #region Métodos Privados
    private function descripcion(EvalBrotesPorPiso $e): string
    {
        $campania = $e->campania;
        return 'evaluación de brotes del ' . $e->fecha?->format('d/m/Y') . ' · ' . ($campania?->campo ?? '?') . ' / ' . ($campania?->nombre_campania ?? '?');
    }

    /** Todo lo de la evaluación, para la auditoría. */
    private function instantanea(EvalBrotesPorPiso $e): array
    {
        return [
            'campania_id' => $e->campania_id,
            'campania_sector_id' => $e->campania_sector_id,
            'fecha' => $e->fecha?->toDateString(),
            'evaluador' => $e->evaluador,
            'metros_cama_ha' => (float) $e->metros_cama_ha,
            'camas' => $e->detalles->map(fn($d) => $d->only(['numero_cama', 'longitud_cama', 'brotes_aptos_2p_actual',
                'brotes_aptos_2p_despues_n_dias', 'brotes_aptos_3p_actual', 'brotes_aptos_3p_despues_n_dias',
                'brotes_aptos_4p_actual', 'brotes_aptos_4p_despues_n_dias']))->values()->all(),
        ];
    }

    private function ordenarDatosExportBrotesPorPiso(array $filtros, $coleccion)
    {
        $resultado = [];
        $variasSiembras = $coleccion->groupBy('campania_id')->map(fn($g) => $g->pluck('campania_sector_id')->unique()->count() > 1);

        foreach ($coleccion as $item) {
            $campo = $item->campania->campo ?? 'SIN_CAMPO';
            // Cada evaluación de la campaña por separado (y por siembra, si tiene varias)
            $campania = ($item->campania->nombre_campania ?? 'SIN_CAMPANIA')
                . ($variasSiembras[$item->campania_id] ? ' · ' . ($item->sector?->nombre ?? 'Sin siembra') : '')
                . ' · ' . $item->fecha?->format('d/m/Y');

            if (!isset($resultado[$campo][$campania])) {
                $resultado[$campo][$campania] = [
                    'fecha_evaluacion' => $item->fecha?->toDateString(),
                    'evaluador' => $item->evaluador,
                    'metros_cama_ha' => $item->metros_cama_ha,
                    'detalles' => [],
                ];
            }

            foreach ($item->detalles as $detalle) {
                $resultado[$campo][$campania]['detalles'][] = [
                    'numero_cama' => $detalle->numero_cama,
                    'longitud_cama' => $detalle->longitud_cama,
                    'brotes_2p_actual' => $detalle->brotes_aptos_2p_actual,
                    'brotes_2p_despues_n_dias' => $detalle->brotes_aptos_2p_despues_n_dias,
                    'brotes_3p_actual' => $detalle->brotes_aptos_3p_actual,
                    'brotes_3p_despues_n_dias' => $detalle->brotes_aptos_3p_despues_n_dias,
                    'brotes_4p_actual' => $detalle->brotes_aptos_4p_actual,
                    'brotes_4p_despues_n_dias' => $detalle->brotes_aptos_4p_despues_n_dias,
                ];
            }
        }

        ksort($resultado);
        foreach ($resultado as $campo => $list) {
            ksort($resultado[$campo]);
        }

        return [
            'filtros' => $filtros,
            'datos' => $resultado,
        ];
    }

    private function calcularMetricas(EvalBrotesPorPiso $eval): array
    {
        return [
            'brotexpiso_fecha_evaluacion' => $eval->fecha,
            'brotexpiso_actual_brotes_2piso' => $eval->promedio_actual_brotes_2piso,
            'brotexpiso_brotes_2piso_n_dias' => $eval->promedio_brotes_2piso_n_dias,
            'brotexpiso_actual_brotes_3piso' => $eval->promedio_actual_brotes_3piso,
            'brotexpiso_brotes_3piso_n_dias' => $eval->promedio_brotes_3piso_n_dias,
            'brotexpiso_actual_brotes_4piso' => $eval->promedio_actual_brotes_4piso,
            'brotexpiso_brotes_4piso_n_dias' => $eval->promedio_brotes_4piso_n_dias,
            'brotexpiso_actual_total_brotes_2y3piso' => $eval->promedio_actual_total_brotes_2y3piso,
            'brotexpiso_total_brotes_2y3piso_n_dias' => $eval->promedio_total_brotes_2y3piso_n_dias,
        ];
    }

    private function calcularMetricasNull(): array
    {
        return array_fill_keys(array_keys($this->calcularMetricas(new EvalBrotesPorPiso())), null);
    }

    private function guardarDetalles(EvalBrotesPorPiso $evaluacion, array $detalles): void
    {
        $evaluacion->detalles()->delete();

        $entero = fn($fila, $campo) => isset($fila[$campo]) && $fila[$campo] !== '' ? intval($fila[$campo]) : null;
        $detallesInsert = collect($detalles)->map(fn($fila) => [
            'brotes_x_piso_id' => $evaluacion->id,
            'numero_cama' => intval($fila['numero_cama']),
            'longitud_cama' => isset($fila['longitud_cama']) && $fila['longitud_cama'] !== '' ? floatval($fila['longitud_cama']) : null,
            'brotes_aptos_2p_actual' => $entero($fila, 'brotes_aptos_2p_actual'),
            'brotes_aptos_2p_despues_n_dias' => $entero($fila, 'brotes_aptos_2p_despues_n_dias'),
            'brotes_aptos_3p_actual' => $entero($fila, 'brotes_aptos_3p_actual'),
            'brotes_aptos_3p_despues_n_dias' => $entero($fila, 'brotes_aptos_3p_despues_n_dias'),
            'brotes_aptos_4p_actual' => $entero($fila, 'brotes_aptos_4p_actual'),
            'brotes_aptos_4p_despues_n_dias' => $entero($fila, 'brotes_aptos_4p_despues_n_dias'),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        $evaluacion->detalles()->insert($detallesInsert);
    }

    private function guardarCabecera(array $datos): EvalBrotesPorPiso
    {
        $campos = [
            'campania_id' => $datos['campania_id'],
            'campania_sector_id' => $datos['campania_sector_id'],
            'fecha' => Carbon::parse($datos['fecha'])->toDateString(),
            'metros_cama_ha' => $datos['metros_cama_ha'],
            'evaluador' => $datos['evaluador'] ?? null,
        ];

        if (!empty($datos['id'])) {
            $eval = EvalBrotesPorPiso::findOrFail($datos['id']);
            $eval->update($campos + ['actualizado_por' => auth()->id()]);
            return $eval;
        }

        // Una siembra puede tener varias evaluaciones: cada guardado sin id es una nueva
        return EvalBrotesPorPiso::create($campos + ['creado_por' => auth()->id()]);
    }

    private function validarDatos(array &$datos): void
    {
        // Filas vacías fuera antes de validar
        if (isset($datos['detalles']) && is_array($datos['detalles'])) {
            $campos = ['numero_cama', 'longitud_cama', 'brotes_aptos_2p_actual', 'brotes_aptos_2p_despues_n_dias', 'brotes_aptos_3p_actual', 'brotes_aptos_3p_despues_n_dias',
                'brotes_aptos_4p_actual', 'brotes_aptos_4p_despues_n_dias'];
            $datos['detalles'] = array_values(array_filter($datos['detalles'], fn($fila) => collect($campos)
                ->contains(fn($c) => isset($fila[$c]) && $fila[$c] !== '')));
        }

        $validator = Validator::make($datos, [
            'id' => 'nullable|integer|exists:eval_brotes_por_pisos,id',
            'campania_id' => 'required|integer|exists:campos_campanias,id',
            'campania_sector_id' => ['required', 'integer', Rule::exists('campania_sectores', 'id')->where('campo_campania_id', $datos['campania_id'] ?? 0)],
            'fecha' => 'required|date',
            'metros_cama_ha' => 'required|numeric|min:0.1',
            'evaluador' => 'required|string|max:255',
            'detalles' => 'required|array|min:1',
        ], [
            'campania_sector_id.required' => 'Elige qué siembra estás evaluando.',
            'campania_sector_id.exists' => 'La siembra elegida no es de esta campaña.',
            'detalles.required' => 'Debe ingresar filas en la tabla.',
            'metros_cama_ha.required' => 'Los metros de cama por hectárea son obligatorios.',
            'fecha.required' => 'La fecha de evaluación es obligatoria.',
            'evaluador.required' => 'El evaluador es obligatorio.',
        ]);

        // La fecha identifica a la evaluación dentro de la siembra: no puede repetirse
        $validator->after(function ($v) use ($datos) {
            if (empty($datos['fecha']) || empty($datos['campania_sector_id'])) {
                return;
            }
            $cruce = EvalBrotesPorPiso::where('campania_sector_id', $datos['campania_sector_id'])
                ->whereDate('fecha', Carbon::parse($datos['fecha'])->toDateString())
                ->when(!empty($datos['id']), fn($q) => $q->where('id', '<>', $datos['id']))
                ->exists();
            if ($cruce) {
                $v->errors()->add('fecha', 'Ya hay una evaluación de esta siembra con fecha ' . Carbon::parse($datos['fecha'])->format('d/m/Y') . '.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        foreach ($datos['detalles'] as $i => $fila) {
            $filaValidator = Validator::make($fila, [
                'numero_cama' => 'required|integer|min:1',
                'longitud_cama' => 'nullable|numeric|min:0.01|max:999999.99',
                'brotes_aptos_2p_actual' => 'nullable|integer|min:0',
                'brotes_aptos_2p_despues_n_dias' => 'nullable|integer|min:0',
                'brotes_aptos_3p_actual' => 'nullable|integer|min:0',
                'brotes_aptos_3p_despues_n_dias' => 'nullable|integer|min:0',
                'brotes_aptos_4p_actual' => 'nullable|integer|min:0',
                'brotes_aptos_4p_despues_n_dias' => 'nullable|integer|min:0',
            ], [], ['numero_cama' => 'N° de cama', 'longitud_cama' => 'longitud de cama']);

            if ($filaValidator->fails()) {
                // Un solo mensaje legible: fila (de la tabla) y qué falla
                throw ValidationException::withMessages([
                    'detalles' => 'Fila ' . ($i + 1) . ': ' . implode(' ', $filaValidator->errors()->all()),
                ]);
            }
        }
    }
    #endregion
}
