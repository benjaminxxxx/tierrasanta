<?php

namespace App\Services\Campo\Produccion;

use App\Exceptions\ErrorControlado;
use App\Models\Actividad;
use App\Models\Labores;
use App\Models\ProduccionRegistro;
use App\Services\Campo\Labor\CampoLaborVigenciaConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de escritura de la producción de campo. Bonificaciones guarda aquí las cantidades con bono de cada
 * trabajador; el módulo de producción guarda las sin bono (trabajador opcional). La unidad siempre es la de la labor.
 */
class CampoProduccionProceso
{
    /**
     * Cantidades con bono de un trabajador en una actividad (una por recojo). Recojos vacíos se quitan.
     * @param array<int, mixed> $porRecojo recojo => cantidad
     */
    public function guardarConBono(Actividad $actividad, ?int $cuadrilleroId, ?int $empleadoId, array $porRecojo, int $recojos): void
    {
        if (!$cuadrilleroId && !$empleadoId) {
            throw new ErrorControlado('Falta el trabajador de la bonificación.');
        }
        // Sin unidad en la labor no se frena el pago del bono: se guarda sin unidad (las consultas usan la de la labor)
        $unidad = CampoProduccionReglas::unidadDeLabor($actividad->codigo_labor, $actividad->fecha);
        $base = ProduccionRegistro::where('actividad_id', $actividad->id)->where('con_bono', true)
            ->where('cuadrillero_id', $cuadrilleroId)->where('plan_empleado_id', $empleadoId);
        (clone $base)->where('numero_recojo', '>', $recojos)->delete();
        for ($i = 1; $i <= $recojos; $i++) {
            $v = $porRecojo[$i] ?? null;
            if ($v === null || $v === '' || (float) $v == 0) {
                (clone $base)->where('numero_recojo', $i)->delete();
                continue;
            }
            $this->validarCantidad($v);
            ProduccionRegistro::updateOrCreate(
                ['actividad_id' => $actividad->id, 'cuadrillero_id' => $cuadrilleroId, 'plan_empleado_id' => $empleadoId, 'numero_recojo' => $i, 'con_bono' => true],
                ['cantidad' => round((float) $v, 2), 'unidad' => $unidad]
            );
        }
    }

    /**
     * Reemplaza las filas sin bono de una actividad.
     * @param array<int, array{tipo?: ?string, trabajador_id?: ?int, recojos: array<int, mixed>, observacion?: ?string}> $filas
     * @return int registros guardados
     */
    public function guardarSinBono(Actividad $actividad, array $filas): int
    {
        $limpias = [];
        foreach ($filas as $n => $f) {
            $recojos = array_filter($f['recojos'] ?? [], fn($v) => $v !== null && $v !== '' && (float) $v != 0);
            if (!$recojos) {
                continue; // fila vacía
            }
            foreach ($recojos as $v) {
                $this->validarCantidad($v, $n + 1);
            }
            $tipo = $f['tipo'] ?? null;
            $id = blank($f['trabajador_id'] ?? null) ? null : (int) $f['trabajador_id'];
            if ($id && !in_array($tipo, ['cuadrilla', 'planilla'], true)) {
                throw new ErrorControlado('Fila ' . ($n + 1) . ': indica si el trabajador es de cuadrilla o de planilla.');
            }
            $limpias[] = ['cuadrillero_id' => $tipo === 'cuadrilla' ? $id : null, 'plan_empleado_id' => $tipo === 'planilla' ? $id : null,
                'recojos' => $recojos, 'observacion' => blank($f['observacion'] ?? null) ? null : trim($f['observacion'])];
        }
        $unidad = $limpias ? $this->unidad($actividad, [1]) : null;

        return DB::transaction(function () use ($actividad, $limpias, $unidad) {
            ProduccionRegistro::where('actividad_id', $actividad->id)->where('con_bono', false)->delete();
            $n = 0;
            foreach ($limpias as $f) {
                foreach ($f['recojos'] as $recojo => $cantidad) {
                    ProduccionRegistro::create([
                        'actividad_id' => $actividad->id, 'cuadrillero_id' => $f['cuadrillero_id'], 'plan_empleado_id' => $f['plan_empleado_id'],
                        'numero_recojo' => (int) $recojo, 'cantidad' => round((float) $cantidad, 2), 'unidad' => $unidad,
                        'con_bono' => false, 'observacion' => $f['observacion'],
                    ]);
                    $n++;
                }
            }
            return $n;
        });
    }

    /**
     * Actividad del día, campo y labor; si no existe se crea (días completos sin bono). La labor debe producir algo.
     */
    public function asegurarActividad(string $fecha, string $campo, int $codigoLabor): Actividad
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $labor = app(CampoLaborVigenciaConsulta::class)->labor($codigoLabor, $fecha);
        if (!$labor) {
            throw new ErrorControlado("La labor {$codigoLabor} no existe.");
        }
        if (!CampoProduccionReglas::codigo($labor->unidades)) {
            throw new ErrorControlado("La labor {$codigoLabor} ({$labor->nombre_labor}) no tiene unidad de producción: asígnale una en Labores.");
        }
        if (!DB::table('campos')->where('nombre', $campo)->exists()) {
            throw new ErrorControlado("El campo \"{$campo}\" no existe.");
        }
        return CampoProduccionConsulta::actividad($fecha, $campo, $codigoLabor) ?? Actividad::create([
            'fecha' => $fecha, 'campo' => $campo, 'codigo_labor' => $codigoLabor, 'nombre_labor' => $labor->nombre_labor,
            'labor_id' => Labores::where('codigo', $codigoLabor)->value('id'), 'recojos' => 1,
            'unidades' => CampoProduccionReglas::codigo($labor->unidades),
        ]);
    }

    /** Unidad de la labor de la actividad (obligatoria si hay cantidades). */
    private function unidad(Actividad $actividad, array $hayCantidades): ?string
    {
        $unidad = CampoProduccionReglas::unidadDeLabor($actividad->codigo_labor, $actividad->fecha);
        if (!$unidad && $hayCantidades) {
            throw new ErrorControlado("La labor {$actividad->codigo_labor} ({$actividad->nombre_labor}) no tiene unidad de producción: asígnale una en Labores.");
        }
        return $unidad;
    }

    private function validarCantidad($v, ?int $fila = null): void
    {
        if (!is_numeric($v) || (float) $v < 0) {
            throw new ErrorControlado(($fila ? "Fila {$fila}: " : '') . "\"{$v}\" no es una cantidad válida.");
        }
    }
}
