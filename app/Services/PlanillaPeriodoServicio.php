<?php

namespace App\Services;

use App\Models\PlanPeriodo;
use App\Models\PlanTipoAsistencia;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlanillaPeriodoServicio
{
    /**
     * Guarda o actualiza un periodo de planilla.
     * * @param array $data
     * @param mixed $periodoId
     * @return PlanPeriodo
     * @throws ValidationException
     */
    public function guardarPeriodo(array $data, $periodoId = null)
    {
        $id = (!empty($periodoId)) ? $periodoId : null;
        $this->validar($data, $id);

        // Usamos una transacción para asegurar que si falla la asistencia, no se guarde el periodo
        return DB::transaction(function () use ($data, $id) {

            if ($id) {
                $periodo = PlanPeriodo::findOrFail($id);
                $data['modificado_por'] = Auth::id();
                $periodo->update($data);
            } else {
                $periodo = PlanPeriodo::create($data);
            }

            // Llamamos al servicio de sincronización
            app(PlanillaRegistroDiarioServicio::class)->actualizarAsistenciaPorRango(
                $data['plan_empleado_id'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['codigo']
            );

            return $periodo;
        });
    }
    public function obtenerPaginacion($filtros, $page = 2)
    {
        $query = PlanPeriodo::query()
            ->with('tipoAsistencia');

        // 🔹 Filtro por empleado
        if (!empty($filtros['plan_empleado_id'])) {
            $query->where('plan_empleado_id', $filtros['plan_empleado_id']);
        }

        // 🔹 Filtro por año (fecha_inicio)
        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha_inicio', $filtros['anio']);
        }

        return $query
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($page);
    }
    /**
     * Elimina un periodo de forma lógica registrando auditoría.
     * * @param int|string $id
     * @param string $motivo
     * @throws \Exception
     */
    public function eliminarPeriodo($id, $motivo)
    {
        if (empty($motivo)) {
            throw new \Exception("Es obligatorio indicar el motivo de la eliminación.");
        }

        $periodo = PlanPeriodo::findOrFail($id);

        // Registramos quién y por qué elimina antes del soft delete
        $periodo->update([
            'motivo_eliminacion' => $motivo,
            'eliminado_por' => Auth::id(),
        ]);

        $periodo->delete();

        return true;
    }
    /**
     * Obtiene los periodos activos hoy, agrupados por código y cruzados con sus tipos.
     */
    public function obtenerResumenPeriodosActivos(): array // Ahora puedes tiparlo
    {
        $hoy = Carbon::today()->toDateString();

        $conteos = PlanPeriodo::whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->select('codigo')
            ->selectRaw('count(*) as total')
            ->groupBy('codigo')
            ->get();

        $tiposConfig = PlanTipoAsistencia::whereIn('codigo', $conteos->pluck('codigo'))
            ->get()
            ->keyBy('codigo');

        return $conteos->map(function ($item) use ($tiposConfig) {
            $config = $tiposConfig->get($item->codigo);

            return [
                'code' => $item->codigo,
                'label' => $config->descripcion ?? 'Sin descripción',
                'count' => $item->total,
                'color' => $config->color ?? '#D1D5DB',
            ];
        })->toArray(); // <--- ESTO convierte la Collection en un array de PHP
    }

    /**
     * Define las reglas de validación y las ejecuta.
     */
    protected function validar(array $data, $id = null)
    {
        $rules = [
            'plan_empleado_id' => 'required|exists:plan_empleados,id',
            'codigo' => 'required|string|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
            'motivo_modificacion' => $id ? 'required|string' : 'nullable|string',
            'modificado_por' => 'nullable|exists:users,id',
        ];

        $messages = [
            'plan_empleado_id.required' => 'El empleado es obligatorio.',
            'plan_empleado_id.exists' => 'El empleado seleccionado no es válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser menor a la fecha de inicio.',
            'codigo.max' => 'El código no debe exceder los 10 caracteres.',
            'motivo_modificacion.required' => 'Debe especificar un motivo para modificar este registro.',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}