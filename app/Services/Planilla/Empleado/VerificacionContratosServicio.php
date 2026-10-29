<?php

namespace App\Services\Planilla\Empleado;

use App\Services\Planilla\Laboral\PlanillaLaboralReglas;
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
 *    o termina después de la R. El cese es el día de la R (fecha de renuncia, criterio de contabilidad):
 *    F, F, R los días 3, 4 y 5 → fin el 5. Un cese antes de la R también se acepta. Acción: finalizarlo
 *    el día de la R, con motivo 01 Renuncia.
 * 2. posible-abandono: contrato activo sin ningún registro 5+ días hábiles seguidos hasta el
 *    último día registrado, o 3 faltas seguidas, sin R. Hay que colocar R (o su permiso).
 * 3. registros-despues-renuncia: después de la R hay registros de asistencia en el mismo mes.
 * 4. cese-sin-documento: contrato finalizado en el mes por renuncia o despido sin su carta o acta adjunta
 *    (contabilidad da de baja en el T-Registro solo con ese documento). Se adjunta en el panel del empleado.
 */
class VerificacionContratosServicio
{
    public const TIPO_SIN_FINALIZAR = 'contrato-sin-finalizar';
    public const TIPO_ABANDONO = 'posible-abandono';
    public const TIPO_POST_RENUNCIA = 'registros-despues-renuncia';
    public const TIPO_SIN_DOCUMENTO = 'cese-sin-documento';

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
                // 1) contrato abierto o que termina después del día de la R (terminar ese día o antes es correcto)
                $contrato = ($contratos->get($empleadoId) ?? collect())
                    ->first(fn($c) => Carbon::parse($c->fecha_inicio)->toDateString() <= $primeraR
                        && (!$c->fecha_fin || Carbon::parse($c->fecha_fin)->toDateString() > $primeraR));
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
        $this->registrarSinDocumento($claveMes, $nombreMes, $inicio, $fin);
    }

    /** Acción de la tarea: finaliza (o corrige) el contrato el día de la R con motivo Renuncia. */
    public function finalizarContrato(int $contratoId, string $fechaFin, string $fechaRenuncia, ?string $ultimoTrabajado = null): void
    {
        app(ContratoServicio::class)->finalizarContrato($contratoId, [
            'fecha_fin' => $fechaFin,
            'motivo_cese_sunat' => self::MOTIVO_RENUNCIA,
            'comentario_cese' => 'Renuncia registrada en asistencia el ' . Carbon::parse($fechaRenuncia)->format('d/m/Y')
                . ($ultimoTrabajado ? '. Último día trabajado: ' . Carbon::parse($ultimoTrabajado)->format('d/m/Y') : '')
                . ' (finalizado desde tareas pendientes).',
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
            'descripcion' => 'Trabajadores con renuncia (R) en el registro diario cuyo contrato sigue abierto o termina después de la R. '
                . 'El cese es el día de la R (fecha de renuncia); el botón finaliza el contrato ese día.',
            'variante' => 'danger',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-emp{$c['empleadoId']}";
            $vigentes[] = $clave;
            $diaR = Carbon::parse($c['primeraR'])->format('d/m/Y');
            $ultimo = Carbon::parse($c['ultimoTrabajado'])->format('d/m/Y');
            $contrato = $c['contrato'];
            $yaFinalizado = $contrato->estado === 'finalizado';

            $registrador->registrarOActualizar([
                'tipo' => self::TIPO_SIN_FINALIZAR, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => "{$c['nombre']}: R el {$diaR}"
                    . ($yaFinalizado ? ' — su contrato termina después, el ' . Carbon::parse($contrato->fecha_fin)->format('d/m/Y') : ' — contrato abierto'),
                'descripcion' => "Se finalizará el contrato el {$diaR} (día de la R, motivo 01 Renuncia). Último día trabajado: {$ultimo}. "
                    . 'Si la fecha de cese es otra, hazlo desde el panel del empleado.',
                'variante' => 'danger', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => ($yaFinalizado ? 'Corregir cese al ' : 'Finalizar al ') . $diaR,
                    'metodo' => 'finalizarContrato',
                    'parametros' => ['contratoId' => $contrato->id, 'fechaFin' => $c['primeraR'], 'fechaRenuncia' => $c['primeraR'], 'ultimoTrabajado' => $c['ultimoTrabajado']],
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
    /** Contratos finalizados en el mes por renuncia o despido que aún no tienen su documento adjunto. */
    private function registrarSinDocumento(string $claveMes, string $nombreMes, string $inicio, string $fin): void
    {
        $contratos = PlanContrato::with(['empleado', 'documentos:id,plan_contrato_id,tipo'])
            ->whereBetween('fecha_fin', [$inicio, $fin])
            ->whereIn('motivo_cese_sunat', array_keys(PlanillaLaboralReglas::DOCUMENTO_POR_MOTIVO))
            ->orderBy('fecha_fin')
            ->get()
            ->filter(fn($c) => PlanillaLaboralReglas::documentoCeseFaltante($c->motivo_cese_sunat, $c->documentos->pluck('tipo')));

        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO_SIN_DOCUMENTO, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Ceses sin carta de renuncia o acta de despido — {$nombreMes}",
            'descripcion' => 'Contabilidad da de baja en el T-Registro solo con el documento firmado. '
                . 'Adjúntalo en el panel del empleado → Historial laboral → "Adjuntar carta de renuncia".',
            'variante' => 'warning',
            'cantidad_afectados' => $contratos->count(),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($contratos as $c) {
            $clave = "{$claveMes}-emp{$c->plan_empleado_id}-c{$c->id}";
            $vigentes[] = $clave;
            $falta = PlanillaLaboralReglas::documentoCeseFaltante($c->motivo_cese_sunat, $c->documentos->pluck('tipo'));
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO_SIN_DOCUMENTO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => ($c->empleado?->nombre_completo ?? "Empleado {$c->plan_empleado_id}") . ': cese el '
                    . $c->fecha_fin->format('d/m/Y') . ' — falta la ' . mb_strtolower(PlanillaLaboralReglas::TIPOS_DOCUMENTO[$falta]),
                'descripcion' => 'Motivo: ' . (\App\Services\Planilla\Empleado\EmpleadoPerfilServicio::MOTIVOS_CESE[$c->motivo_cese_sunat] ?? $c->motivo_cese_sunat)
                    . '. Adjunta el escaneo o la foto del documento firmado.',
                'variante' => 'warning', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Adjuntar carta de renuncia',
                    'tipo_accion' => 'link',
                    'url' => route('planilla.empleados', ['empleado' => $c->plan_empleado_id, 'tab' => 'contratos']),
                ]],
            ]);
        }
        $this->cerrarNoVigentes(self::TIPO_SIN_DOCUMENTO, $claveMes, $vigentes);
    }

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
