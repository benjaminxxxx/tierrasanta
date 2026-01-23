<?php

namespace App\Services\RecursosHumanos\Data;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanFamiliar;
use App\Models\PlanSueldo;

class DataEmpleadoServicio
{
    /*
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'documento',
        'fecha_ingreso',
        'comentarios',
        'email',
        'numero',
        'fecha_nacimiento',
        'direccion',
        'genero',
        'orden',

        //
        'plan_empleado_id',
        'tipo_contrato',
        'fecha_inicio',
        'fecha_fin',
        'sueldo',
        'cargo_codigo',
        'grupo_codigo',
        'compensacion_vacacional',
        'tipo_planilla',
        'plan_sp_codigo',
        'esta_jubilado',
        'modalidad_pago',
        'motivo_despido',

        //
         'plan_empleado_id',
        'sueldo',
        'fecha_inicio',
        'fecha_fin',

        //
        'plan_empleado_id',
        'nombres',
        'fecha_nacimiento',
        'documento',
        'creado_por',
        'actualizado_por',
        'esta_estudiando',
        */
    public function obtenerDataEmpleados(): array
    {
        return [
            // Usamos toArray() para convertir la colección de modelos en un array asociativo
            'EMPLEADOS' => PlanEmpleado::all()->toArray(),
            'CONTRATACIONES' => $this->contratosPorDocumento()->toArray(),
            'SUELDOS' => $this->sueldosPorDocumento()->toArray(),
            'HIJOS' => $this->familiaresPorDocumento()->toArray(),
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

    protected function familiaresPorDocumento()
    {
        return PlanFamiliar::join('plan_empleados', 'plan_empleados.id', '=', 'plan_familiares.plan_empleado_id')
            ->select('plan_empleados.documento as documento_padre', 'plan_familiares.*')
            ->get();
    }
}
