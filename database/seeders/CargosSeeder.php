<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate the table before seeding
        //DB::table('cargos')->truncate();

        // Insert all cargos data
        DB::table('plan_cargos')->insert([
            ['codigo' => 'ASI', 'nombre' => 'ASIST. PROD', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'ASI1', 'nombre' => 'ASIST. FINANZ.', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'ASI2', 'nombre' => 'ASIST. CONTAB', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'ASI3', 'nombre' => 'ASIST.SISTEMA', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'ASISADM', 'nombre' => 'ASIST. ADMINISTRATIVO CAMPO', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ASISCONT', 'nombre' => 'ASIST. CONTABILIDAD', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ASISFIN', 'nombre' => 'ASIST. FINANZAS', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ASISGER', 'nombre' => 'ASIST. GERENCIA', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ASISIST', 'nombre' => 'ASIST. SISTEMA', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ASISPROD', 'nombre' => 'ASIST. PRODUCCIÓN', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'CAP', 'nombre' => 'CAPTZ. GRL', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'CAP1', 'nombre' => 'CAPTZ. 2', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'CAP2', 'nombre' => 'CAPATAZ 2', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'CAPGRL', 'nombre' => 'CAPATAZ GENERAL', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'COC', 'nombre' => 'COCINA', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'EVAL', 'nombre' => 'EVALUADOR', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'FIL', 'nombre' => 'FILTRADO', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'FUM', 'nombre' => 'FUMIGADOR', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'GER', 'nombre' => 'GERENTE', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'ING', 'nombre' => 'ING - GER. AGRIC', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'INGAGR', 'nombre' => 'ING. GER. AGRIC', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'LOG', 'nombre' => 'LOGÍSTICA', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'OBR', 'nombre' => 'OBRERO', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'RIE', 'nombre' => 'RIEGO', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'SER', 'nombre' => 'SERV GENER', 'created_at' => '2024-09-20 06:41:14', 'updated_at' => '2024-09-20 06:41:14'],
            ['codigo' => 'SERVGEN', 'nombre' => 'SERV. GENERALES', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'TRAC', 'nombre' => 'TRACTORISTA', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'VARLAB', 'nombre' => 'VARIAS LABORES', 'created_at' => null, 'updated_at' => null],
            ['codigo' => 'VIG', 'nombre' => 'VIGILANTE', 'created_at' => null, 'updated_at' => null],
        ]);
    }
}
