<?php
// app/Domain/DerechoHabiente/DerechoHabienteQueryService.php
namespace App\Domain\DerechoHabiente;

use App\Models\EmpleadoDerechoHabiente;
final class DerechoHabienteQueryService
{
    public function listarResumenPorEmpleado(DerechoHabienteFiltroDTO $filtro): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = \App\Models\PlanEmpleado::query()
            ->whereHas('derechoHabientes')
            ->with(['derechoHabientes' => fn($q) => $q->with('derechoHabiente')]);

        if ($filtro->empleadoId) {
            $query->where('id', $filtro->empleadoId);
        }

        if ($filtro->search) {
            $query->where(function ($q) use ($filtro) {
                $q->where('nombres', 'like', "%{$filtro->search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$filtro->search}%")
                    ->orWhere('apellido_materno', 'like', "%{$filtro->search}%")
                    ->orWhere('documento', 'like', "%{$filtro->search}%");
            });
        }

        $paginador = $query->orderBy('nombres')->paginate(20);

        $servicio = app(\App\Domain\DerechoHabiente\EmpleadoAsignacionFamiliarService::class);
        $hoy = now();

        $paginador->getCollection()->transform(function (\App\Models\PlanEmpleado $empleado) use ($servicio, $hoy) {
            $hijos = $empleado->derechoHabientes->filter(
                fn($v) => $v->activo && $v->derechoHabiente->tipo === 'hijo'
            );

            $evaluacion = $servicio->evaluarColeccion($hijos, $hoy->month, $hoy->year);

            return (object) [
                'empleado' => $empleado,
                'cantidad_hijos' => $hijos->count(),
                'tiene_asignacion' => $evaluacion['tiene'],
            ];
        });

        return $paginador;
    }
    public function listar(DerechoHabienteFiltroDTO $filtro): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = EmpleadoDerechoHabiente::query()
            ->with(['empleado', 'derechoHabiente']);

        if ($filtro->empleadoId) {
            $query->where('empleado_id', $filtro->empleadoId);
        }

        if ($filtro->tipo) {
            $query->whereHas('derechoHabiente', fn($q) => $q->where('tipo', $filtro->tipo));
        }

        if ($filtro->rol) {
            $query->where('rol', $filtro->rol);
        }

        if (!is_null($filtro->activo)) {
            $query->where('activo', $filtro->activo);
        }

        if ($filtro->search) {
            $query->where(function ($q) use ($filtro) {
                $q->whereHas('derechoHabiente', function ($qq) use ($filtro) {
                    $qq->where('nombres', 'like', "%{$filtro->search}%")
                        ->orWhere('documento', 'like', "%{$filtro->search}%");
                })->orWhereHas('empleado', function ($qq) use ($filtro) {
                    $qq->where('nombres', 'like', "%{$filtro->search}%")
                        ->orWhere('apellido_paterno', 'like', "%{$filtro->search}%")
                        ->orWhere('apellido_materno', 'like', "%{$filtro->search}%")
                        ->orWhere('documento', 'like', "%{$filtro->search}%");
                });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }
}