<?php

namespace App\Services\Cuadrilla\Persona;

use App\Models\CuadDetalleHora;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\PlanRegistroDiario;
use App\Models\TareaPendiente;
use App\Services\Riego\Cuadrilla\RiegoCuadrillaReglas;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente "Misma persona en planilla y cuadrilla" (por mes): un cuadrillero que parece ser un trabajador de
 * planilla (CuadrillaPersonaConsulta) tiene horas que se cruzan el mismo día en los dos registros. Esas horas se
 * pagarían y costearían dos veces. Un día de planilla sin tramos (feriado, descanso) con trabajo en cuadrilla no se
 * marca: puede ser trabajo extra pagado aparte.
 */
class CuadrillaPersonaDetector
{
    public const TIPO = 'persona-planilla-y-cuadrilla';

    public function __construct(private CuadrillaPersonaConsulta $personas)
    {
    }

    /** @return array<int, array{cuadrillero_id:int, cuadrillero:string, empleado:string, fecha:string, minutos:int, pagado:bool, planilla:string, cuadrilla:string}> */
    public function cruces(string $inicio, string $fin): array
    {
        $registros = CuadRegistroDiario::with('cuadrillero')->whereBetween('fecha', [$inicio, $fin])->get();
        // Días ya justificados (trabajo extra, otro día, otra persona): no son tarea
        $justificados = app(CuadrillaPersonaJustificacionProceso::class)->justificadosEnRango($inicio, $fin);
        $casos = [];
        foreach ($registros as $rc) {
            if (!$rc->cuadrillero || !($emp = $this->personas->empleadoDePlanilla($rc->cuadrillero))) {
                continue;
            }
            $fecha = Carbon::parse($rc->fecha)->toDateString();
            if (isset($justificados[$rc->cuadrillero_id . '|' . $fecha])) {
                continue;
            }
            $rp = PlanRegistroDiario::whereDate('fecha', $fecha)->with('detalles')
                ->whereHas('detalleMensual', fn($q) => $q->where('plan_empleado_id', $emp['id']))->first();
            if (!$rp || $rp->detalles->isEmpty()) {
                continue;
            }
            $tc = CuadDetalleHora::where('registro_diario_id', $rc->id)->get();
            $min = 0;
            foreach ($tc as $a) {
                foreach ($rp->detalles as $b) {
                    $min += RiegoCuadrillaReglas::minutosCruce($a->hora_inicio, $a->hora_fin, $b->hora_inicio, $b->hora_fin);
                }
            }
            if ($min <= 0) {
                continue;
            }
            $texto = fn($ts) => $ts->map(fn($t) => "{$t->campo_nombre}/{$t->codigo_labor} " . RiegoCuadrillaReglas::hora($t->hora_inicio) . '-' . RiegoCuadrillaReglas::hora($t->hora_fin))->implode(', ');
            $casos[] = [
                'cuadrillero_id' => $rc->cuadrillero_id, 'cuadrillero' => $rc->cuadrillero->nombres, 'empleado' => $emp['nombre'],
                'fecha' => $fecha, 'minutos' => $min, 'pagado' => (bool) $rc->esta_pagado,
                'planilla' => $rp->asistencia . ' ' . $texto($rp->detalles), 'cuadrilla' => $texto($tc),
            ];
        }
        return $casos;
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $inicio = Carbon::parse($fechaInicio ?? now()->startOfMonth())->startOfMonth()->toDateString();
        $fin = Carbon::parse($inicio)->endOfMonth()->toDateString();
        $claveMes = substr($inicio, 0, 7);
        $casos = $this->cruces($inicio, $fin);

        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => 'Misma persona en planilla y cuadrilla — ' . Carbon::parse($inicio)->translatedFormat('F Y'),
            'descripcion' => 'Cuadrilleros que parecen ser trabajadores de planilla y tienen horas que se cruzan el mismo día en los dos registros: '
                . 'se pagarían y costearían dos veces. El trabajo extra de alguien de planilla se puede pagar como cuadrilla, pero en otro horario '
                . 'o en la fecha real (p. ej. el domingo, no el lunes). Deja cada hora en un solo registro.',
            'variante' => 'danger',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-c{$c['cuadrillero_id']}-{$c['fecha']}";
            $vigentes[] = $clave;
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $c['fecha'], 'fecha_fin' => $c['fecha'],
                'titulo' => "{$c['cuadrillero']} (cuadrilla) = {$c['empleado']} (planilla) — " . Carbon::parse($c['fecha'])->format('d/m')
                    . ': ' . number_format($c['minutos'] / 60, 2) . ' h en los dos' . ($c['pagado'] ? ' · YA PAGADO en cuadrilla' : ''),
                'descripcion' => "Planilla: {$c['planilla']}. Cuadrilla: {$c['cuadrilla']}.",
                'variante' => 'danger', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => array_merge([
                    ['titulo' => 'Registro de planilla', 'tipo_accion' => 'link', 'url' => route('planilla.registro_diario', ['fecha' => $c['fecha']])],
                    ['titulo' => 'Registro de cuadrilla', 'tipo_accion' => 'link', 'url' => route('cuadrilla.registro_diario', ['fecha' => $c['fecha']])],
                ], self::accionesJustificar($c['cuadrillero_id'], $c['fecha'])),
            ]);
        }
        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')
            ->where('clave', 'like', "{$claveMes}-c%")->whereNotIn('clave', $vigentes)->get()
            ->each(fn($t) => $registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $t->clave, 'cantidad_afectados' => 0]));
    }

    /** Botones de la tarea para justificar el día (datos que ya existen). Se ejecutan con justificar(). */
    public static function accionesJustificar(int $cuadrilleroId, string $fecha): array
    {
        $domingo = \Illuminate\Support\Carbon::parse(CuadrillaPersonaReglas::domingoAnterior($fecha))->format('d/m');
        return [
            ['titulo' => 'Trabajo extra', 'metodo' => 'justificar', 'parametros' => ['cuadrilleroId' => $cuadrilleroId, 'fecha' => $fecha, 'motivo' => 'trabajo_extra']],
            ['titulo' => "Otro día (dom. {$domingo})", 'metodo' => 'justificar', 'parametros' => ['cuadrilleroId' => $cuadrilleroId, 'fecha' => $fecha, 'motivo' => 'otro_dia']],
            ['titulo' => 'Otra persona', 'metodo' => 'justificar', 'parametros' => ['cuadrilleroId' => $cuadrilleroId, 'fecha' => $fecha, 'motivo' => 'otra_persona']],
        ];
    }

    public function justificar(int $cuadrilleroId, string $fecha, string $motivo): void
    {
        app(CuadrillaPersonaJustificacionProceso::class)->justificar($cuadrilleroId, $fecha, $motivo);
        // La otra tarea (riego de cuadrilleros) también usa la justificación
        app(\App\Services\Riego\Cuadrilla\RiegoCuadrillaDetector::class)->detectarTareasPendientes($fecha);
    }
}
