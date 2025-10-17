<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PlanEmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = Carbon::now();

        $empleados = [
            [
                'nombres' => 'Luis Alberto',
                'apellido_paterno' => 'Torres',
                'apellido_materno' => 'Quispe',
                'documento' => '74859632',
                'cargo_codigo' => 'ASISADM',
                'grupo_codigo' => 'GRUQUIVAR',
                'plan_sp_codigo' => 'HAB F',
                'sueldo_2024' => 1300.00,
                'sueldo_2025' => 1500.00,
            ],
            [
                'nombres' => 'María Fernanda',
                'apellido_paterno' => 'Rojas',
                'apellido_materno' => 'Luna',
                'documento' => '70985412',
                'cargo_codigo' => 'ASISCONT',
                'grupo_codigo' => 'GRUQUIMUJ',
                'plan_sp_codigo' => 'INT F',
                'sueldo_2024' => 1400.00,
                'sueldo_2025' => 1600.00,
            ],
            [
                'nombres' => 'Carlos Daniel',
                'apellido_paterno' => 'Ramírez',
                'apellido_materno' => 'Gómez',
                'documento' => '72596314',
                'cargo_codigo' => 'ASISFIN',
                'grupo_codigo' => 'GRUQUIVAR',
                'plan_sp_codigo' => 'PRI F',
                'sueldo_2024' => 1200.00,
                'sueldo_2025' => 1450.00,
            ],
            [
                'nombres' => 'Kiara Isabel',
                'apellido_paterno' => 'Huamán',
                'apellido_materno' => 'Peña',
                'documento' => '75849631',
                'cargo_codigo' => 'ASISGER',
                'grupo_codigo' => 'PLAANT',
                'plan_sp_codigo' => 'PRO F',
                'sueldo_2024' => 1550.00,
                'sueldo_2025' => 1700.00,
            ],
        ];

        foreach ($empleados as $e) {
            // Crear empleado
            $planEmpleadoId = DB::table('plan_empleados')->insertGetId([
                'uuid' => Str::uuid(),
                'nombres' => $e['nombres'],
                'apellido_paterno' => $e['apellido_paterno'],
                'apellido_materno' => $e['apellido_materno'],
                'documento' => $e['documento'],
                'fecha_ingreso' => '2023-01-15',
                'genero' => 'M',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            // Crear contrato (planilla agraria)
            $planContratoId = DB::table('plan_contratos')->insertGetId([
                'plan_empleado_id' => $planEmpleadoId,
                'tipo_contrato' => 'indefinido',
                'fecha_inicio' => '2023-01-15',
                'fecha_fin' => null,
                'cargo_codigo' => $e['cargo_codigo'],
                'grupo_codigo' => $e['grupo_codigo'],
                'compensacion_vacacional' => 0,
                'tipo_planilla' => 'AGRARIA',
                'plan_sp_codigo' => $e['plan_sp_codigo'],
                'esta_jubilado' => false,
                'modalidad_pago' => 'mensual',
                'motivo_despido' => null,
                'creado_por' => 1,
                'actualizado_por' => 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            // Crear sueldos: uno del año pasado y otro actual
            DB::table('plan_sueldos')->insert([
                [
                    'plan_empleado_id' => $planEmpleadoId,
                    'sueldo' => $e['sueldo_2024'],
                    'fecha_inicio' => '2024-01-01',
                    'fecha_fin' => '2024-12-31',
                    'creado_por' => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ],
                [
                    'plan_empleado_id' => $planEmpleadoId,
                    'sueldo' => $e['sueldo_2025'],
                    'fecha_inicio' => '2025-01-01',
                    'fecha_fin' => null,
                    'creado_por' => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ],
            ]);
        }
    }
}
