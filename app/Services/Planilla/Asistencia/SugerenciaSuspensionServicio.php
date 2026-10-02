<?php

namespace App\Services\Planilla\Asistencia;

use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Models\PlanTipoSuspension;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sugiere las suspensiones (PLAME) que faltan registrar a partir del registro diario del mes.
 *
 * - Cada código de asistencia usa la suspensión vinculada en su tipo (plan_tipo_asistencias).
 * - Un día A cuyo detalle es todo de una labor de suspensión (labores.tipo_asistencia_codigo) cuenta como
 *   ese código. Los días que mezclan trabajo y suspensión se devuelven en "parciales" (solo informativo).
 *   Si no tiene vínculo, queda "por decidir" (se vincula una vez y aplica siempre).
 * - Los días ya cubiertos por una suspensión del mismo tipo no se sugieren; si los cubre una
 *   suspensión de OTRO tipo, se informa como conflicto.
 * - Los días consecutivos se agrupan en un solo rango. Si el tipo de suspensión incluye domingos
 *   (vacaciones, enfermedad, maternidad), un domingo sin asistencia entre dos días del mismo tipo
 *   se incluye en el rango: en el campo no se registra el domingo, pero sí cuenta.
 * - Si el rango toca una suspensión existente del mismo tipo, se sugiere extenderla (o unir dos).
 */
class SugerenciaSuspensionServicio
{
    /**
     * @return array{
     *   sugerencias: array<int, array>,
     *   por_decidir: array<string, array{codigo:string, descripcion:string, dias:int, trabajadores:int}>,
     *   conflictos: array<int, array>,
     *   parciales: array<int, array{trabajador:string, fecha:string, codigo:string, descripcion:string, horas_suspension:float, horas_trabajo:float}>
     * }
     */
    public function sugerir(int $mes, int $anio): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $tiposAsistencia = PlanTipoAsistencia::get()->keyBy('codigo');
        $tiposSuspension = PlanTipoSuspension::get()->keyBy('id');

        $registros = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->whereNotNull('r.asistencia')->where('r.asistencia', '<>', '')
            ->orderBy('d.plan_empleado_id')->orderBy('r.fecha')
            ->get(['r.id', 'd.plan_empleado_id', 'd.nombres', 'r.fecha', 'r.asistencia']);

        // El detalle también puede traer labores de suspensión (A + 8 h de 97 = día DM). El día cuenta con
        // su código efectivo; si mezcla trabajo y suspensión se informa aparte (el PLAME declara días completos).
        $laborAsistencia = app(PlanillaAsistenciaLaborConsulta::class);
        $parciales = [];
        $codigosLaborSuspension = array_keys($laborAsistencia->asistenciaPorLabor());
        $tramosPorRegistro = $codigosLaborSuspension
            ? DB::table('plan_detalles_horas as h')
                ->join('plan_registros_diarios as r', 'r.id', '=', 'h.plan_reg_dia_id')
                ->whereBetween('r.fecha', [$inicio, $fin])
                ->where('r.asistencia', 'A')
                // Solo los días que tienen al menos un tramo de suspensión
                ->whereExists(fn($q) => $q->from('plan_detalles_horas as s')->whereColumn('s.plan_reg_dia_id', 'r.id')
                    ->whereIn('s.codigo_labor', $codigosLaborSuspension))
                ->get(['h.plan_reg_dia_id', 'h.codigo_labor', DB::raw('TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) as minutos')])
                ->groupBy('plan_reg_dia_id')
            : collect();
        foreach ($registros as $r) {
            $tramos = $tramosPorRegistro->get($r->id);
            if (!$tramos) {
                continue;
            }
            $tramos = $tramos->map(fn($t) => ['codigo_labor' => $t->codigo_labor, 'minutos' => (int) $t->minutos]);
            $efectivo = $laborAsistencia->codigoEfectivo($r->asistencia, $tramos);
            if ($efectivo !== $r->asistencia) {
                $r->asistencia = $efectivo;
                $r->desde_detalle = true;
                continue;
            }
            $reparto = $laborAsistencia->repartirHoras($tramos);
            foreach ($reparto['suspension'] as $codigo => $horas) {
                $parciales[] = [
                    'trabajador' => $r->nombres,
                    'fecha' => $this->dia($r->fecha),
                    'codigo' => $codigo,
                    'descripcion' => $tiposAsistencia->get($codigo)?->descripcion ?? $codigo,
                    'horas_suspension' => round($horas, 2),
                    'horas_trabajo' => round($reparto['trabajo'], 2),
                ];
            }
        }

        // Suspensiones existentes alrededor del mes (±7 días para detectar las adyacentes)
        $existentes = PlanSuspension::whereIn('plan_empleado_id', $registros->pluck('plan_empleado_id')->unique())
            ->where('fecha_inicio', '<=', Carbon::parse($fin)->addDays(7)->toDateString())
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', Carbon::parse($inicio)->subDays(7)->toDateString()))
            ->get()
            ->groupBy('plan_empleado_id');

        $sugerencias = [];
        $porDecidir = [];
        $conflictos = [];

        foreach ($registros->groupBy('plan_empleado_id') as $empleadoId => $delEmpleado) {
            $nombre = $delEmpleado->first()->nombres;
            $suspensiones = $existentes->get($empleadoId, collect());
            $asistidos = $delEmpleado->where('asistencia', 'A')->map(fn($r) => $this->dia($r->fecha))->flip();
            $porTipo = []; // tipo_suspension_id => [fecha => codigo]

            foreach ($delEmpleado as $r) {
                $codigo = $r->asistencia;
                $tipo = $tiposAsistencia->get($codigo);
                if ($codigo === 'A' || ($tipo && $tipo->sin_suspension)) {
                    continue;
                }
                if (!$tipo || !$tipo->plan_tipo_suspension_id) {
                    $porDecidir[$codigo]['codigo'] = $codigo;
                    $porDecidir[$codigo]['descripcion'] = $tipo?->descripcion ?? 'Código sin tipo de asistencia';
                    $porDecidir[$codigo]['dias'] = ($porDecidir[$codigo]['dias'] ?? 0) + 1;
                    $porDecidir[$codigo]['empleados'][$empleadoId] = true;
                    continue;
                }

                $fecha = $this->dia($r->fecha);
                $tipoSuspensionId = (int) $tipo->plan_tipo_suspension_id;
                $cubre = $suspensiones->first(fn($s) => $this->cubre($s, $fecha));

                if ($cubre && (int) $cubre->tipo_suspension_id === $tipoSuspensionId) {
                    continue; // ya registrado
                }
                if ($cubre) {
                    $conflictos[] = [
                        'plan_empleado_id' => $empleadoId,
                        'trabajador' => $nombre,
                        'fecha' => $fecha,
                        'codigo' => $codigo,
                        'esperado' => $this->etiqueta($tiposSuspension->get($tipoSuspensionId)),
                        'registrado' => $this->etiqueta($tiposSuspension->get($cubre->tipo_suspension_id)),
                    ];
                    continue;
                }
                $porTipo[$tipoSuspensionId][$fecha] = !empty($r->desde_detalle) ? "{$codigo} (en detalle)" : $codigo;
            }

            foreach ($porTipo as $tipoSuspensionId => $fechas) {
                $tipoSusp = $tiposSuspension->get($tipoSuspensionId);
                $incluyeDomingos = (bool) $tipoSusp?->incluye_domingos;

                foreach ($this->agrupar(array_keys($fechas), $incluyeDomingos, $asistidos) as [$a, $b]) {
                    $delTipo = $suspensiones->where('tipo_suspension_id', $tipoSuspensionId);
                    $antes = $delTipo->first(fn($s) => $s->fecha_fin && $this->pegado($this->dia($s->fecha_fin), $a, $incluyeDomingos, $asistidos));
                    $despues = $delTipo->first(fn($s) => $this->pegado($b, $this->dia($s->fecha_inicio), $incluyeDomingos, $asistidos));

                    $accion = $antes && $despues ? 'unir' : ($antes ? 'extender_fin' : ($despues ? 'extender_inicio' : 'crear'));
                    $codigos = array_values(array_unique(array_intersect_key($fechas, array_flip($this->diasEntre($a, $b)))));

                    $sugerencias[] = [
                        'clave' => "{$empleadoId}|{$tipoSuspensionId}|{$a}|{$b}",
                        'plan_empleado_id' => (int) $empleadoId,
                        'trabajador' => $nombre,
                        'codigos' => implode(', ', $codigos),
                        'tipo_suspension_id' => (int) $tipoSuspensionId,
                        'tipo_suspension' => $this->etiqueta($tipoSusp),
                        'fecha_inicio' => $a,
                        'fecha_fin' => $b,
                        'dias' => Carbon::parse($a)->diffInDays(Carbon::parse($b)) + 1,
                        'domingos' => count(array_filter($this->diasEntre($a, $b), fn($d) => !isset($fechas[$d]))),
                        'accion' => $accion,
                        'suspension_antes_id' => $antes?->id,
                        'suspension_despues_id' => $despues?->id,
                        'rango_final' => [
                            $despues && !$antes ? $a : ($antes ? $this->dia($antes->fecha_inicio) : $a),
                            $despues ? ($despues->fecha_fin ? $this->dia($despues->fecha_fin) : null) : $b,
                        ],
                    ];
                }
            }
        }

        foreach ($porDecidir as &$p) {
            $p['trabajadores'] = count($p['empleados']);
            unset($p['empleados']);
        }
        unset($p);

        usort($sugerencias, fn($x, $y) => [$x['trabajador'], $x['fecha_inicio']] <=> [$y['trabajador'], $y['fecha_inicio']]);

        usort($parciales, fn($x, $y) => [$x['trabajador'], $x['fecha']] <=> [$y['trabajador'], $y['fecha']]);

        return ['sugerencias' => $sugerencias, 'por_decidir' => array_values($porDecidir), 'conflictos' => $conflictos, 'parciales' => $parciales];
    }

    /**
     * Registra las sugerencias elegidas (por clave). Se recalculan antes de aplicar para no usar
     * datos viejos del navegador.
     *
     * @return array{creadas:int, extendidas:int, unidas:int}
     */
    public function aplicar(int $mes, int $anio, array $claves): array
    {
        $elegidas = array_flip($claves);
        $sugerencias = array_filter($this->sugerir($mes, $anio)['sugerencias'], fn($s) => isset($elegidas[$s['clave']]));
        $conteo = ['creadas' => 0, 'extendidas' => 0, 'unidas' => 0];

        DB::transaction(function () use ($sugerencias, &$conteo) {
            foreach ($sugerencias as $s) {
                [$inicioFinal, $finFinal] = $s['rango_final'];
                switch ($s['accion']) {
                    case 'crear':
                        PlanSuspension::create([
                            'plan_empleado_id' => $s['plan_empleado_id'],
                            'tipo_suspension_id' => $s['tipo_suspension_id'],
                            'fecha_inicio' => $inicioFinal,
                            'fecha_fin' => $finFinal,
                            'observaciones' => "Desde registro diario ({$s['codigos']})",
                            'creado_por' => auth()->id(),
                        ]);
                        $conteo['creadas']++;
                        break;
                    case 'extender_fin':
                    case 'extender_inicio':
                        $id = $s['suspension_antes_id'] ?? $s['suspension_despues_id'];
                        PlanSuspension::whereKey($id)->update([
                            'fecha_inicio' => $inicioFinal,
                            'fecha_fin' => $finFinal,
                            'actualizado_por' => auth()->id(),
                        ]);
                        $conteo['extendidas']++;
                        break;
                    case 'unir':
                        PlanSuspension::whereKey($s['suspension_antes_id'])->update([
                            'fecha_fin' => $finFinal,
                            'actualizado_por' => auth()->id(),
                        ]);
                        PlanSuspension::whereKey($s['suspension_despues_id'])->delete();
                        $conteo['unidas']++;
                        break;
                }
            }
        });

        return $conteo;
    }

    /**
     * Vincula un código de asistencia con su suspensión (o lo marca como "no genera suspensión").
     */
    public function vincular(string $codigo, ?int $tipoSuspensionId, bool $sinSuspension = false): void
    {
        PlanTipoAsistencia::where('codigo', $codigo)->update([
            'plan_tipo_suspension_id' => $sinSuspension ? null : $tipoSuspensionId,
            'sin_suspension' => $sinSuspension,
        ]);
    }

    // ------------------------------------------------------------------------------------------

    /** @return array<int, array{0:string,1:string}> */
    private function agrupar(array $fechas, bool $incluyeDomingos, $asistidos): array
    {
        sort($fechas);
        $rangos = [];
        foreach ($fechas as $f) {
            $ultimo = count($rangos) - 1;
            if ($ultimo >= 0 && $this->pegado($rangos[$ultimo][1], $f, $incluyeDomingos, $asistidos)) {
                $rangos[$ultimo][1] = $f;
            } else {
                $rangos[] = [$f, $f];
            }
        }
        return $rangos;
    }

    /**
     * ¿$despues continúa a $antes? Día siguiente, o (si incluye domingos) solo domingos sin
     * asistencia en medio.
     */
    private function pegado(string $antes, string $despues, bool $incluyeDomingos, $asistidos): bool
    {
        $d = Carbon::parse($antes)->addDay();
        $objetivo = Carbon::parse($despues);
        while ($d->lt($objetivo)) {
            if (!$incluyeDomingos || !$d->isSunday() || isset($asistidos[$d->toDateString()])) {
                return false;
            }
            $d->addDay();
        }
        return $d->eq($objetivo);
    }

    private function cubre($suspension, string $fecha): bool
    {
        return $this->dia($suspension->fecha_inicio) <= $fecha
            && (!$suspension->fecha_fin || $this->dia($suspension->fecha_fin) >= $fecha);
    }

    private function diasEntre(string $a, string $b): array
    {
        $dias = [];
        for ($d = Carbon::parse($a); $d->toDateString() <= $b; $d->addDay()) {
            $dias[] = $d->toDateString();
        }
        return $dias;
    }

    private function dia($fecha): string
    {
        return Carbon::parse($fecha)->toDateString();
    }

    private function etiqueta(?PlanTipoSuspension $t): string
    {
        return $t ? "{$t->codigo} - " . ($t->descripcion_corta ?: $t->descripcion) : '—';
    }
}
