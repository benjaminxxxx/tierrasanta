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
        DB::table('cargos')->insert([
            ['codigo' => 'ASISIST', 'nombre' => 'ASIST. SISTEMA'],
            ['codigo' => 'GER', 'nombre' => 'GERENTE'],
            ['codigo' => 'RIE', 'nombre' => 'RIEGO'],
            ['codigo' => 'OBR', 'nombre' => 'OBRERO'],
            ['codigo' => 'FIL', 'nombre' => 'FILTRADO'],
            ['codigo' => 'VARLAB', 'nombre' => 'VARIAS LABORES'],
            ['codigo' => 'ASISPROD', 'nombre' => 'ASIST. PRODUCCIÓN'],
            ['codigo' => 'ASISFIN', 'nombre' => 'ASIST. FINANZAS'],
            ['codigo' => 'INGAGR', 'nombre' => 'ING. GER. AGRIC'],
            ['codigo' => 'FUM', 'nombre' => 'FUMIGADOR'],
            ['codigo' => 'ASISGER', 'nombre' => 'ASIST. GERENCIA'],
            ['codigo' => 'EVAL', 'nombre' => 'EVALUADOR'],
            ['codigo' => 'SERVGEN', 'nombre' => 'SERV. GENERALES'],
            ['codigo' => 'TRAC', 'nombre' => 'TRACTORISTA'],
            ['codigo' => 'VIG', 'nombre' => 'VIGILANTE'],
            ['codigo' => 'CAPGRL', 'nombre' => 'CAPATAZ GENERAL'],
            ['codigo' => 'LOG', 'nombre' => 'LOGÍSTICA'],
            ['codigo' => 'ASISCONT', 'nombre' => 'ASIST. CONTABILIDAD'],
            ['codigo' => 'CAP2', 'nombre' => 'CAPATAZ 2'],
            ['codigo' => 'ASISADM', 'nombre' => 'ASIST. ADMINISTRATIVO CAMPO'],
            ['codigo' => 'COC', 'nombre' => 'COCINA'],
        ]);
    }
}
