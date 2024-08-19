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
            ['codigo' => 'ENC', 'nombre' => 'ENCARGADO'],
            ['codigo' => 'TRA', 'nombre' => 'TRACTORISTA'],
            ['codigo' => 'CAM', 'nombre' => 'CAMAYOS'],
            ['codigo' => 'OBR', 'nombre' => 'OBRERO'],
        ]);
    }
}
