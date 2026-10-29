<?php

namespace App\Services\Cuadrilla\Persona;

use App\Exceptions\ErrorControlado;
use App\Models\CuadPlanillaJustificacion;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\PlanRegistroDiario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Justificaciones de "trabajador de planilla que también figura como cuadrillero" (ver CuadrillaPersonaReglas):
 * a quién preguntar al guardar el registro de cuadrilla, y guardar o quitar la respuesta.
 */
class CuadrillaPersonaJustificacionProceso
{
    public function __construct(private CuadrillaPersonaConsulta $personas)
    {
    }

    public function justificado(int $cuadrilleroId, string $fecha): ?CuadPlanillaJustificacion
    {
        return CuadPlanillaJustificacion::where('cuadrillero_id', $cuadrilleroId)->whereDate('fecha', Carbon::parse($fecha)->toDateString())->first();
    }

    /** Claves "cuadrilleroId|fecha" justificadas en un rango (para los detectores). */
    public function justificadosEnRango(string $inicio, string $fin): array
    {
        return CuadPlanillaJustificacion::whereBetween('fecha', [$inicio, $fin])->get()
            ->mapWithKeys(fn($j) => [$j->cuadrillero_id . '|' . $j->fecha->toDateString() => $j])->all();
    }

    public function justificar(int $cuadrilleroId, string $fecha, string $motivo, ?string $fechaReal = null, ?string $nota = null): CuadPlanillaJustificacion
    {
        if (!isset(CuadrillaPersonaReglas::MOTIVOS[$motivo])) {
            throw new ErrorControlado("Motivo no válido: {$motivo}.");
        }
        $fecha = Carbon::parse($fecha)->toDateString();
        if ($motivo === 'otro_dia') {
            $fechaReal = $fechaReal ? Carbon::parse($fechaReal)->toDateString() : CuadrillaPersonaReglas::domingoAnterior($fecha);
            if ($fechaReal === $fecha) {
                throw new ErrorControlado('Para "trabajo de otro día" la fecha real debe ser distinta del ' . Carbon::parse($fecha)->format('d/m/Y') . '.');
            }
        } else {
            $fechaReal = null;
        }
        $cuadrillero = Cuadrillero::withTrashed()->findOrFail($cuadrilleroId);
        return CuadPlanillaJustificacion::updateOrCreate(
            ['cuadrillero_id' => $cuadrilleroId, 'fecha' => $fecha],
            ['plan_empleado_id' => $this->personas->empleadoDePlanilla($cuadrillero)['id'] ?? null, 'motivo' => $motivo,
                'fecha_real' => $fechaReal, 'nota' => $nota, 'creado_por' => Auth::id()]
        );
    }

    public function quitar(int $cuadrilleroId, string $fecha): void
    {
        CuadPlanillaJustificacion::where('cuadrillero_id', $cuadrilleroId)->whereDate('fecha', Carbon::parse($fecha)->toDateString())->delete();
    }

    /**
     * Cuadrilleros (de la lista dada, o todos los del día) que parecen ser un trabajador de planilla con contrato que
     * ese mismo día tiene registro en planilla (asistencia o tramos) y que aún no tienen justificación.
     *
     * @return array<int, array{cuadrillero_id:int, cuadrillero:string, empleado:string, planilla:string, cuadrilla:string, fecha_real:string}>
     */
    public function porPreguntar(string $fecha, ?array $cuadrilleroIds = null): array
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $registros = CuadRegistroDiario::with('cuadrillero')->whereDate('fecha', $fecha)
            ->when($cuadrilleroIds !== null, fn($q) => $q->whereIn('cuadrillero_id', $cuadrilleroIds))->get()
            ->unique('cuadrillero_id');
        $justificados = CuadPlanillaJustificacion::whereDate('fecha', $fecha)->pluck('cuadrillero_id')->map(fn($id) => (int) $id)->all();
        $hora = fn($h) => substr((string) $h, 0, 5);
        $casos = [];
        foreach ($registros as $rc) {
            if (!$rc->cuadrillero || in_array((int) $rc->cuadrillero_id, $justificados, true)) {
                continue;
            }
            $emp = $this->personas->empleadoConContrato($rc->cuadrillero, $fecha);
            if (!$emp) {
                continue;
            }
            $rp = PlanRegistroDiario::with('detalles')->whereDate('fecha', $fecha)
                ->whereHas('detalleMensual', fn($q) => $q->where('plan_empleado_id', $emp['id']))->first();
            if (!$rp || (trim((string) $rp->asistencia) === '' && $rp->detalles->isEmpty())) {
                continue; // ese día no figura en planilla: nada que justificar
            }
            $tramosCuad = \App\Models\CuadDetalleHora::whereIn('registro_diario_id',
                CuadRegistroDiario::where('cuadrillero_id', $rc->cuadrillero_id)->whereDate('fecha', $fecha)->pluck('id'))->get();
            $casos[] = [
                'cuadrillero_id' => (int) $rc->cuadrillero_id,
                'cuadrillero' => $rc->cuadrillero->nombres,
                'empleado' => $emp['nombre'],
                'planilla' => trim($rp->asistencia . ' ' . $rp->detalles->sortBy('hora_inicio')->map(fn($d) => "{$d->campo_nombre}/{$d->codigo_labor} {$hora($d->hora_inicio)}-{$hora($d->hora_fin)}")->implode(', ')),
                'cuadrilla' => $tramosCuad->sortBy('hora_inicio')->map(fn($d) => "{$d->campo_nombre}/{$d->codigo_labor} {$hora($d->hora_inicio)}-{$hora($d->hora_fin)}")->implode(', ') ?: 'sin tramos',
                'fecha_real' => CuadrillaPersonaReglas::domingoAnterior($fecha),
            ];
        }
        return $casos;
    }
}
