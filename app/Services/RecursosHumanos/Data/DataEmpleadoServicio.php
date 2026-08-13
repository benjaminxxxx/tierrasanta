<?php

namespace App\Services\RecursosHumanos\Data;

use App\Models\DerechoHabiente;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanSueldo;

class DataEmpleadoServicio
{
    public function obtenerDataEmpleados(): array
    {
        return [
            // Usamos toArray() para convertir la colección de modelos en un array asociativo
            'EMPLEADOS' => PlanEmpleado::all()->toArray(),
            'CONTRATACIONES' => $this->contratosPorDocumento()->toArray(),
            'SUELDOS' => $this->sueldosPorDocumento()->toArray(),
            'DERECHO_HABIENTES' => $this->derechoHabientesPorDocumento()->toArray(),
        ];
    }

    protected function contratosPorDocumento()
    {
        return PlanContrato::join('plan_empleados', 'plan_empleados.id', '=', 'plan_contratos.plan_empleado_id')
            ->select('plan_empleados.documento as documento', 'plan_contratos.*')
            ->get();
    }

    protected function sueldosPorDocumento()
    {
        return PlanSueldo::join('plan_empleados', 'plan_empleados.id', '=', 'plan_sueldos.plan_empleado_id')
            ->select('plan_empleados.documento as documento', 'plan_sueldos.*')
            ->get();
    }

    protected function derechoHabientesPorDocumento()
    {
        return DerechoHabiente::join('empleado_derecho_habientes', 'empleado_derecho_habientes.derecho_habiente_id', '=', 'derecho_habientes.id')
            ->join('plan_empleados', 'plan_empleados.id', '=', 'empleado_derecho_habientes.empleado_id')
            ->select(
                'plan_empleados.documento as documento_padre',
                'empleado_derecho_habientes.rol',
                'empleado_derecho_habientes.mes_vigencia',
                'empleado_derecho_habientes.anio_vigencia',
                'empleado_derecho_habientes.activo',
                'derecho_habientes.*'
            )
            ->get();
    }
}
