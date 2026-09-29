<?php

namespace App\Services\Planilla\Empleado;

use App\Models\PlanContrato;
use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tareas pendientes de contratos a partir del registro diario de planilla (por mes):
 *
 * 1. contrato-sin-finalizar: hay un código con rol "renuncia" (R) y el contrato sigue abierto
 *    (o termina después). Acción: finalizarlo al último día trabajado, con motivo 01 Renuncia.
 * 2. posible-abandono: contrato activo sin ningún registro 5+ días hábiles seguidos hasta el
 *    último día registrado, o 3 faltas seguidas, sin R. Hay que colocar R (o su permiso).
 * 3. registros-despues-renuncia: después de la R hay registros de asistencia en el mismo mes.
 */
class VerificacionContratosServicio
{
    public const TIPO_SIN_FINALIZAR = 'contrato-sin-finalizar';
    public const TIPO_ABANDONO = 'posible-abandono';
    public const TIPO_POST_RENUNCIA = 'registros-despues-renuncia';

    public const DIAS_SIN_REGISTRO = 5;
    public const FALTAS_SEGUIDAS = 3;
    public const MOTIVO_RENUNCIA = '01';

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $inicio = Carbon::parse($fechaInicio ?? now()->startOfMonth())->startOfMonth()->toDateString();
        $fin = Carbon::parse($inicio)->endOfMonth()->toDateString();
        $claveMes = substr($inicio, 0, 7);
        $nombreMes = Carbon::parse($inicio)->translatedFormat('F Y');

        $codigosRenuncia = PlanTipoAsistencia::codigosConRol(PlanTipoAsistencia::ROL_RENUNCIA);

        // Registros del mes por trabajador (fecha => código)
        $registros = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->orderBy('r.fecha')
            ->get(['d.plan_empleado_id', 'd.nombres', 'r.fecha', 'r.asistencia'])
            ->map(fn($r) => (object) [
                'empleado' => (int) $r->plan_empleado_id,
                'nombre' => $r->nombres,
                'fecha' => Carbon::parse($r->fecha)->toDateString(),
                'codigo' => trim((string) $r->asistencia),
            ]);

        $porEmpleado = $registros->groupBy('empleado');
        $nombres = $registros->pluck('nombre', 'empleado');

        // Trabajadores de la planilla del mes (aunque no tengan registros)
        $enPlanilla = DB::table('plan_mensual_detalles as d')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', (int) substr($inicio, 5, 2))->where('m.anio', (int) substr($inicio, 0, 4))
            ->pluck('d.nombres', 'd.plan_empleado_id');
        foreach ($enPlanilla as $id => $nombre) {
            $nombres[$id] ??= $nombre;
        }

        $contratos = PlanContrato::whereIn('plan_empleado_id', $nombres->keys())
            ->where('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
            ->orderByDesc('fecha_inicio')
            ->get()
            ->groupBy('plan_empleado_id');

        // Días del mes que la empresa ya registró (al menos un trabajador con código), hasta hoy
        $diasRegistrados = $registros->filter(fn($r) => $r->codigo !== '')
            ->pluck('fecha')->unique()->filter(fn($f) => $f <= now()->toDateString())->sort()->values();

        $sinFinalizar = [];
        $abandono = [];
        $postRenuncia = [];

        foreach ($nombres as $empleadoId => $nombre) {
            $delEmpleado = ($porEmpleado->get($empleadoId) ?? collect())->keyBy('fecha');
            $primeraR = $delEmpleado->first(fn($r) => in_array($r->codigo, $codigosRenuncia, true))?->fecha;

            if ($primeraR) {
                // 1) contrato abierto (o que termina después) pese a la renuncia
                $contrato = ($contratos->get($empleadoId) ?? collect())
                    ->first(fn($c) => Carbon::parse($c->fecha_inicio)->toDateString() <= $primeraR
                        && (!$c->fecha_fin || Carbon::parse($c->fecha_fin)->toDateString() >= $primeraR));
                if ($contrato) {
                    $ultimoTrabajado = $delEmpleado
                        ->filter(fn($r) => $r->fecha < $primeraR && $r->codigo !== '' && !in_array($r->codigo, $codigosRenuncia, true) && $r->codigo !== 'F')
                        ->keys()->last() ?? Carbon::parse($primeraR)->subDay()->toDateString();
                    $sinFinalizar[] = compact('empleadoId', 'nombre', 'primeraR', 'ultimoTrabajado', 'contrato');
                }

                // 3) registros de asistencia después de la renuncia
                $despues = $delEmpleado->filter(fn($r) => $r->fecha > $primeraR && $r->codigo !== '' && !in_array($r->codigo, $codigosRenuncia, true));
                if ($despues->isNotEmpty()) {
                    $postRenuncia[] = ['empleadoId' => $empleadoId, 'nombre' => $nombre, 'primeraR' => $primeraR, 'dias' => $despues->values()];
                }
                continue;
            }

            // 2) posible abandono: solo con contrato vigente al último día registrado
            $ultimoDiaEmpresa = $diasRegistrados->last();
            $vigente = $ultimoDiaEmpresa && ($contratos->get($empleadoId) ?? collect())
                ->contains(fn($c) => !$c->fecha_fin || Carbon::parse($c->fecha_fin)->toDateString() >= $ultimoDiaEmpresa);
            if (!$vigente) {
                continue;
            }

            $suspensiones = PlanSuspension::where('plan_empleado_id', $empleadoId)
                ->where('fecha_inicio', '<=', $fin)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
                ->get();
            $cubierto = fn(string $f) => $suspensiones->contains(fn($s) => Carbon::parse($s->fecha_inicio)->toDateString() <= $f
                && (!$s->fecha_fin || Carbon::parse($s->fecha_fin)->toDateString() >= $f));

            // a) racha final de días (que la empresa registró) sin ningún registro del trabajador
            $racha = [];
            foreach ($diasRegistrados->reverse() as $dia) {
                $codigo = $delEmpleado->get($dia)?->codigo ?? '';
                if ($codigo !== '' || $cubierto($dia)) {
                    break;
                }
                $racha[] = $dia;
            }
            if (count($racha) >= self::DIAS_SIN_REGISTRO) {
                $abandono[] = ['empleadoId' => $empleadoId, 'nombre' => $nombre, 'desde' => end($racha), 'dias' => count($racha), 'motivo' => 'sin_registro'];
                continue;
            }

            // b) faltas seguidas (en días que la empresa registró)
            $seguidas = [];
            foreach ($diasRegistrados as $dia) {
                if (($delEmpleado->get($dia)?->codigo ?? '') === 'F') {
                    $seguidas[] = $dia;
                    if (count($seguidas) >= self::FALTAS_SEGUIDAS) {
                        break;
                    }
                } else {
                    $seguidas = [];
                }
            }
            if (count($seguidas) >= self::FALTAS_SEGUIDAS) {
                $abandono[] = ['empleadoId' => $empleadoId, 'nombre' => $nombre, 'desde' => $seguidas[0], 'dias' => count($seguidas), 'motivo' => 'faltas'];
            }
        }

        $this->registrarSinFinalizar($sinFinalizar, $claveMes, $nombreMes, $inicio, $fin);
        $this->registrarAbandono($abandono, $claveMes, $nombreMes, $inicio, $fin);
        $this->registrarPostRenuncia($postRenuncia, $claveMes, $nombreMes, $inicio, $fin);
    }

    /** Acción de la tarea: finaliza el contrato al último día trabajado con motivo Renuncia. */
    public function finalizarContrato(int $contratoId, string $fechaFin, string $fechaRenuncia): void
    {
        $fecha = Carbon::parse($fechaFin)->format('d/m/Y');
        app(ContratoServicio::class)->finalizarContrato($contratoId, [
            'fecha_fin' => $fechaFin,
            'motivo_cese_sunat' => self::MOTIVO_RENUNCIA,
            'comentario_cese' => "Renuncia registrada en asistencia desde el " . Carbon::parse($fechaRenuncia)->format('d/m/Y')
                . ". Último día trabajado: {$fecha} (finalizado desde tareas pendientes).",
        ]);
    }

    // ------------------------------------------------------------------------------------------

    private function registrarSinFinalizar(array $casos, string $claveMes, string $nombreMes, string $inicio, string $fin): void
    {
        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO_SIN_FINALIZAR, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Contratos sin finalizar — {$nombreMes}",
            'descripcion' => 'Trabajadores con renuncia (R) en el registro diario cuyo contrato sigue abierto o termina después. '
                . 'El botón lo finaliza al último día trabajado; si la fecha no es esa, finalízalo desde el panel del empleado.',
            'variante' => 'danger',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-emp{$c['empleadoId']}";
            $vigentes[] = $clave;
            $ultimo = Carbon::parse($c['ultimoTrabajado'])->format('d/m/Y');
            $contrato = $c['contrato'];
            $yaFinalizado = $contrato->estado === 'finalizado';

            $registrador->registrarOActualizar([
                'tipo' => self::TIPO_SIN_FINALIZAR, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => "{$c['nombre']}: R desde el " . Carbon::parse($c['primeraR'])->format('d/m/Y') . " — último día trabajado {$ultimo}"
                    . ($yaFinalizado ? ' (su contrato está finalizado al ' . Carbon::parse($contrato->fecha_fin)->format('d/m/Y') . ')' : ''),
                'descripcion' => $yaFinalizado
                    ? 'La fecha de fin del contrato no coincide con la renuncia: corrígela desde el panel del empleado.'
                    : "Se finalizará el contrato al {$ultimo} (motivo 01 Renuncia). Si no es ese día, hazlo desde el panel del empleado.",
                'variante' => 'danger', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => $yaFinalizado ? [] : [[
                    'titulo' => "Finalizar al {$ultimo}",
                    'metodo' => 'finalizarContrato',
                    'parametros' => ['contratoId' => $contrato->id, 'fechaFin' => $c['ultimoTrabajado'], 'fechaRenuncia' => $c['primeraR']],
                ]],
            ]);
        }
        $this->cerrarNoVigentes(self::TIPO_SIN_FINALIZAR, $claveMes, $vigentes);
    }

    private function registrarAbandono(array $casos, string $claveMes, string $nombreMes, string $inicio, string $fin): void
    {
        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO_ABANDONO, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Posible abandono — {$nombreMes}",
            'descripcion' => 'Contratos activos sin registros ' . self::DIAS_SIN_REGISTRO . '+ días hábiles seguidos o con '
                . self::FALTAS_SEGUIDAS . ' faltas seguidas. Si renunció, coloca R desde el día que dejó de venir; si no, registra su permiso o vacaciones.',
            'variante' => 'warning',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-emp{$c['empleadoId']}";
            $vigentes[] = $clave;
            $desde = Carbon::parse($c['desde'])->format('d/m/Y');
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO_ABANDONO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => $c['motivo'] === 'faltas'
                    ? "{$c['nombre']}: {$c['dias']} faltas seguidas desde el {$desde}"
                    : "{$c['nombre']}: sin registros desde el {$desde} ({$c['dias']} días hábiles)",
                'descripcion' => "¿Renunció? Coloca R desde el {$desde}.",
                'variante' => 'warning', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Abrir registro diario',
                    'tipo_accion' => 'link',
                    'url' => route('planilla.registro_diario', ['fecha' => $c['desde']]),
                ]],
            ]);
        }
        $this->cerrarNoVigentes(self::TIPO_ABANDONO, $claveMes, $vigentes);
    }

    private function registrarPostRenuncia(array $casos, string $claveMes, string $nombreMes, string $inicio, string $fin): void
    {
        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO_POST_RENUNCIA, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Registros después de la renuncia — {$nombreMes}",
            'descripcion' => 'Después de la R no debería haber asistencia ni actividades del trabajador en el mes.',
            'variante' => 'danger',
            'cantidad_afectados' => collect($casos)->sum(fn($c) => $c['dias']->count()),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-emp{$c['empleadoId']}";
            $vigentes[] = $clave;
            $dias = $c['dias']->map(fn($r) => Carbon::parse($r->fecha)->format('d/m') . " ({$r->codigo})")->implode(', ');
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO_POST_RENUNCIA, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => "{$c['nombre']}: R desde el " . Carbon::parse($c['primeraR'])->format('d/m/Y') . " pero tiene registros el {$dias}",
                'descripcion' => 'Corrige esos días en el registro diario (o la fecha de la R).',
                'variante' => 'danger', 'cantidad_afectados' => $c['dias']->count(),
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Abrir registro diario',
                    'tipo_accion' => 'link',
                    'url' => route('planilla.registro_diario', ['fecha' => $c['dias']->first()->fecha]),
                ]],
            ]);
        }
        $this->cerrarNoVigentes(self::TIPO_POST_RENUNCIA, $claveMes, $vigentes);
    }

    /** Cierra las subtareas del mes que ya no aparecen (se resolvieron). */
    private function cerrarNoVigentes(string $tipo, string $claveMes, array $vigentes): void
    {
        TareaPendiente::where('tipo', $tipo)->where('estado', 'pendiente')
            ->where('clave', 'like', "{$claveMes}-emp%")
            ->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($t) => app(TareaPendienteServicio::class)->registrarOActualizar([
                'tipo' => $tipo, 'clave' => $t->clave, 'cantidad_afectados' => 0,
            ]));
    }
}
