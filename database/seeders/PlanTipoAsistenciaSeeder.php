<?php

namespace Database\Seeders;

use App\Models\PlanTipoAsistencia;
use App\Services\PlanTipoAsistenciaServicio;
use Illuminate\Database\Seeder;

class PlanTipoAsistenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PlanTipoAsistenciaServicio $servicio): void
    {
        $tipos = [
            [
                'codigo' => 'A',
                'descripcion' => 'Asistido',
                'horas_jornal' => 8,
                'color' => '#FFFFFF',
                'tipo' => 'ASISTENCIA',
                'afecta_sueldo' => true,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => false,
                'acumula_vacaciones' => true,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'F',
                'descripcion' => 'Falta',
                'horas_jornal' => 0,
                'color' => '#DA9694',
                'tipo' => 'ASISTENCIA',
                'afecta_sueldo' => true,
                'porcentaje_remunerado' => 0,
                'requiere_documento' => false,
                'acumula_vacaciones' => false,
                'acumula_asistencia' => false,
                'activo' => true,
            ],
            [
                'codigo' => 'V',
                'descripcion' => 'Vacaciones',
                'horas_jornal' => 8,
                'color' => '#92CDDC',
                'tipo' => 'VACACIONES',
                'afecta_sueldo' => false,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => false,
                'acumula_vacaciones' => false,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'LM',
                'descripcion' => 'Licencia Maternidad',
                'horas_jornal' => 0,
                'color' => '#FFC0CB',
                'tipo' => 'LICENCIA',
                'afecta_sueldo' => false,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => true,
                'acumula_vacaciones' => true,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'LSG',
                'descripcion' => 'Licencia Sin Goce',
                'horas_jornal' => 0,
                'color' => '#FFFF00',
                'tipo' => 'LICENCIA',
                'afecta_sueldo' => true,
                'porcentaje_remunerado' => 0,
                'requiere_documento' => true,
                'acumula_vacaciones' => false,
                'acumula_asistencia' => false,
                'activo' => true,
            ],
            [
                'codigo' => 'LCG',
                'descripcion' => 'Licencia Con Goce',
                'horas_jornal' => 8,
                'color' => '#FFFF00',
                'tipo' => 'LICENCIA',
                'afecta_sueldo' => false,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => true,
                'acumula_vacaciones' => true,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'DM',
                'descripcion' => 'Descanso Médico',
                'horas_jornal' => 8,
                'color' => '#FABF8F',
                'tipo' => 'PERMISO',
                'afecta_sueldo' => false,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => true,
                'acumula_vacaciones' => true,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'AM',
                'descripcion' => 'Atención Médica',
                'horas_jornal' => 8,
                'color' => '#C4D79B',
                'tipo' => 'PERMISO',
                'afecta_sueldo' => false,
                'porcentaje_remunerado' => 100,
                'requiere_documento' => false,
                'acumula_vacaciones' => true,
                'acumula_asistencia' => true,
                'activo' => true,
            ],
        ];
        $servicio->registrarOActualizarLote($tipos);
    }
}
