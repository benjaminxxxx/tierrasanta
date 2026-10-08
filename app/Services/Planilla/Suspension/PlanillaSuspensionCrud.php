<?php

namespace App\Services\Planilla\Suspension;

use App\Models\PlanSuspension;
use App\Models\PlanTipoSuspension;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Guarda de una vez los rangos de suspensión de un trabajador (modal de Permisos y suspensiones).
 *
 * Se valida el conjunto completo: los rangos del modal entre sí y contra los demás del trabajador que no están en el
 * modal. Así se puede correr el límite entre dos rangos seguidos (acortar uno y alargar el otro) en un solo guardado,
 * lo que antes obligaba a guardar fila por fila. Los rangos pueden ser futuros (vacaciones o descanso médico que ya
 * se sabe): el registro diario después no los duplica.
 */
class PlanillaSuspensionCrud
{
    /**
     * @param array<int, array{id?: ?int, tipo_suspension_id: mixed, fecha_inicio: ?string, fecha_fin: ?string, observaciones?: ?string}> $rangos
     *        los rangos que quedan (los que estaban en el modal y ya no vienen, se eliminan)
     * @param int[] $idsEnModal ids que se mostraron en el modal (solo esos se pueden eliminar)
     * @return array{creados: int, actualizados: int, eliminados: int}
     */
    public function guardarRangos(int $planEmpleadoId, array $rangos, array $idsEnModal): array
    {
        $rangos = array_values(array_filter($rangos, fn($r) => !empty($r['tipo_suspension_id']) || !empty($r['fecha_inicio']) || !empty($r['fecha_fin'])));
        $errores = [];
        $tipos = PlanTipoSuspension::pluck('id')->all();

        foreach ($rangos as $i => &$r) {
            $n = $i + 1;
            if (empty($r['tipo_suspension_id']) || !in_array((int) $r['tipo_suspension_id'], $tipos, true)) {
                $errores["rangos.{$i}.tipo_suspension_id"] = "Fila {$n}: elige el tipo de suspensión.";
            }
            if (empty($r['fecha_inicio'])) {
                $errores["rangos.{$i}.fecha_inicio"] = "Fila {$n}: falta la fecha de inicio.";
                continue;
            }
            $r['fecha_fin'] = !empty($r['fecha_fin']) ? $r['fecha_fin'] : $r['fecha_inicio'];
            if (Carbon::parse($r['fecha_fin'])->lt(Carbon::parse($r['fecha_inicio']))) {
                $errores["rangos.{$i}.fecha_fin"] = "Fila {$n}: el fin es anterior al inicio.";
            }
        }
        unset($r);
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        // Cruces entre los rangos del modal
        $ordenados = $rangos;
        usort($ordenados, fn($a, $b) => strcmp($a['fecha_inicio'], $b['fecha_inicio']));
        for ($i = 1; $i < count($ordenados); $i++) {
            if ($ordenados[$i]['fecha_inicio'] <= $ordenados[$i - 1]['fecha_fin']) {
                $errores['rangos'] = 'Dos rangos se cruzan: ' . $this->texto($ordenados[$i - 1]) . ' y ' . $this->texto($ordenados[$i]) . '. Un día solo puede tener una suspensión.';
                break;
            }
        }

        // Cruces con los demás rangos del trabajador (los que no estaban en el modal)
        $idsQueQuedan = array_filter(array_map(fn($r) => (int) ($r['id'] ?? 0), $rangos));
        $otros = PlanSuspension::with('tipoSuspension')->where('plan_empleado_id', $planEmpleadoId)
            ->whereNotIn('id', array_unique(array_merge($idsEnModal, $idsQueQuedan)))->get();
        foreach ($rangos as $r) {
            foreach ($otros as $o) {
                $finOtro = $o->fecha_fin?->toDateString() ?? '9999-12-31';
                if ($r['fecha_inicio'] <= $finOtro && $o->fecha_inicio->toDateString() <= $r['fecha_fin']) {
                    $errores['rangos'] = 'El rango ' . $this->texto($r) . ' se cruza con ' . ($o->tipoSuspension?->codigo ?? '') . ' del '
                        . $o->fecha_inicio->format('d/m/Y') . ($o->fecha_fin ? ' al ' . $o->fecha_fin->format('d/m/Y') : ' (sin fin)') . ', que no está en esta lista.';
                    break 2;
                }
            }
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        return DB::transaction(function () use ($planEmpleadoId, $rangos, $idsEnModal, $idsQueQuedan) {
            $res = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];
            $aEliminar = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
                ->whereIn('id', array_diff($idsEnModal, $idsQueQuedan))->get();
            foreach ($aEliminar as $s) {
                AuditoriaServicio::registrar(PlanSuspension::class, $s->id, 'eliminar', $s->only(['tipo_suspension_id', 'fecha_inicio', 'fecha_fin', 'observaciones']), null, 'Suspensión eliminada');
                $s->delete();
                $res['eliminados']++;
            }

            foreach ($rangos as $r) {
                $datos = [
                    'tipo_suspension_id' => (int) $r['tipo_suspension_id'],
                    'fecha_inicio' => $r['fecha_inicio'],
                    'fecha_fin' => $r['fecha_fin'],
                    'observaciones' => !empty($r['observaciones']) ? trim($r['observaciones']) : null,
                ];
                $existente = !empty($r['id']) ? PlanSuspension::where('plan_empleado_id', $planEmpleadoId)->find($r['id']) : null;
                if ($existente) {
                    $antes = $existente->only(array_keys($datos));
                    $existente->fill($datos + ['actualizado_por' => Auth::id()]);
                    if ($existente->isDirty(array_keys($datos))) {
                        $existente->save();
                        AuditoriaServicio::registrar(PlanSuspension::class, $existente->id, 'editar', $antes, $datos, 'Suspensión editada');
                        $res['actualizados']++;
                    }
                } else {
                    $nueva = PlanSuspension::create($datos + ['plan_empleado_id' => $planEmpleadoId, 'creado_por' => Auth::id()]);
                    AuditoriaServicio::registrar(PlanSuspension::class, $nueva->id, 'crear', null, $datos, 'Suspensión registrada');
                    $res['creados']++;
                }
            }
            return $res;
        });
    }

    private function texto(array $r): string
    {
        return Carbon::parse($r['fecha_inicio'])->format('d/m/Y') . ' – ' . Carbon::parse($r['fecha_fin'])->format('d/m/Y');
    }
}
