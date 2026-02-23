<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanTipoSuspensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plan_tipos_suspension')->insert([
            // Suspensión Perfecta (SP)
            ['codigo' => '01', 'grupo' => 'SP', 'descripcion' => 'S.P. Sanción disciplinaria'],
            ['codigo' => '02', 'grupo' => 'SP', 'descripcion' => 'S.P. Ejercicio del derecho de huelga'],
            ['codigo' => '03', 'grupo' => 'SP', 'descripcion' => 'S.P. Detención del trabajador, salvo el caso de condena privativa de la libertad'],
            ['codigo' => '04', 'grupo' => 'SP', 'descripcion' => 'S.P. Inhabilitación administrativa o judicial por período no superior a tres meses'],
            ['codigo' => '05', 'grupo' => 'SP', 'descripcion' => 'S.P. Permiso o licencia concedidos por el empleador sin goce de haber'],
            ['codigo' => '06', 'grupo' => 'SP', 'descripcion' => 'S.P. Caso fortuito o fuerza mayor'],
            ['codigo' => '07', 'grupo' => 'SP', 'descripcion' => 'S.P. Falta no justificada'],
            ['codigo' => '08', 'grupo' => 'SP', 'descripcion' => 'S.P. Por temporada o intermitente'],

            // Suspensión Imperfecta (SI)
            ['codigo' => '20', 'grupo' => 'SI', 'descripcion' => 'S.I. Enfermedad o accidente (primeros veinte días)'],
            ['codigo' => '21', 'grupo' => 'SI', 'descripcion' => 'S.I. Incapacidad temporal (invalidez, enfermedad y accidentes)'],
            ['codigo' => '22', 'grupo' => 'SI', 'descripcion' => 'S.I. Maternidad durante el descanso pre y post natal'],
            ['codigo' => '23', 'grupo' => 'SI', 'descripcion' => 'S.I. Descanso vacacional'],
            ['codigo' => '24', 'grupo' => 'SI', 'descripcion' => 'S.I. Licencia para desempeñar cargo cívico y para cumplir con el servicio militar obligatorio'],
            ['codigo' => '25', 'grupo' => 'SI', 'descripcion' => 'S.I. Permiso y licencia para el desempeño de cargos sindicales'],
            ['codigo' => '26', 'grupo' => 'SI', 'descripcion' => 'S.I. Licencia con goce de haber'],
            ['codigo' => '27', 'grupo' => 'SI', 'descripcion' => 'S.I. Días compensados por horas trabajadas en sobretiempo'],
        ]);
    }
}
