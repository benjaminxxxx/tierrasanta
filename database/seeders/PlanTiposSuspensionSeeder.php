<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanTiposSuspensionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('plan_tipos_suspension')->upsert([
            [
                'codigo' => '01',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Sanción disciplinaria',
                'descripcion_corta' => 'Sanción',
            ],
            [
                'codigo' => '02',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Ejercicio del derecho de huelga',
                'descripcion_corta' => 'Huelga',
            ],
            [
                'codigo' => '03',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Detención del trabajador, salvo el caso de condena privativa de la libertad',
                'descripcion_corta' => 'Detención',
            ],
            [
                'codigo' => '04',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Inhabilitación administrativa o judicial por período no superior a tres meses',
                'descripcion_corta' => 'Inhabilitación',
            ],
            [
                'codigo' => '05',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Permiso o licencia concedidos por el empleador sin goce de haber',
                'descripcion_corta' => 'Permiso',
            ],
            [
                'codigo' => '06',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Caso fortuito o fuerza mayor',
                'descripcion_corta' => 'Fuerza Mayor',
            ],
            [
                'codigo' => '07',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Falta no justificada',
                'descripcion_corta' => 'Falta',
            ],
            [
                'codigo' => '08',
                'grupo' => 'SP',
                'descripcion' => 'S.P. Por temporada o intermitente',
                'descripcion_corta' => 'Temporada',
            ],
            [
                'codigo' => '20',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Enfermedad o accidente (primeros veinte días)',
                'descripcion_corta' => 'Descanso medico',
            ],
            [
                'codigo' => '21',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Incapacidad temporal (invalidez, enfermedad y accidentes)',
                'descripcion_corta' => 'Incapacidad',
            ],
            [
                'codigo' => '22',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Maternidad durante el descanso pre y post natal',
                'descripcion_corta' => 'Licencia Maternidad',
            ],
            [
                'codigo' => '23',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Descanso vacacional',
                'descripcion_corta' => 'Vacaciones',
            ],
            [
                'codigo' => '24',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Licencia para desempeñar cargo cívico y para cumplir con el servicio militar obligatorio',
                'descripcion_corta' => 'Cargo Cívico',
            ],
            [
                'codigo' => '25',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Permiso y licencia para el desempeño de cargos sindicales',
                'descripcion_corta' => 'Cargo Sindical',
            ],
            [
                'codigo' => '26',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Licencia con goce de haber',
                'descripcion_corta' => 'Licencia Con Goce',
            ],
            [
                'codigo' => '27',
                'grupo' => 'SI',
                'descripcion' => 'S.I. Días compensados por horas trabajadas en sobretiempo',
                'descripcion_corta' => 'Compensado',
            ],
        ], ['codigo'], [
            'grupo',
            'descripcion',
            'descripcion_corta',
        ]);
    }
}