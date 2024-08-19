<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DescuentoSpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('descuento_sp')->insert([
            ['codigo' => 'HAB F','orden'=>'1', 'referencia' => 'HABITAT', 'descripcion' => 'Habitat Flujo', 'porcentaje' => 13.17, 'porcentaje_65' => 11.67, 'tipo' => 'Flujo'],
            ['codigo' => 'INT F','orden'=>'2', 'referencia' => 'INTEGRA', 'descripcion' => 'Integra Flujo', 'porcentaje' => 13.25, 'porcentaje_65' => 11.75, 'tipo' => 'Flujo'],
            ['codigo' => 'PRI F','orden'=>'3', 'referencia' => 'PRIMA', 'descripcion' => 'Prima Flujo', 'porcentaje' => 13.30, 'porcentaje_65' => 11.80, 'tipo' => 'Flujo'],
            ['codigo' => 'PRO F','orden'=>'4', 'referencia' => 'PROFUTURO', 'descripcion' => 'Profuturo Flujo', 'porcentaje' => 13.39, 'porcentaje_65' => 11.89, 'tipo' => 'Flujo'],
            ['codigo' => 'SNP','orden'=>'5', 'referencia' => 'SNP', 'descripcion' => 'Sistema Nacional de Pensiones', 'porcentaje' => 13.00, 'porcentaje_65' => 13.00, 'tipo' => null],
            ['codigo' => 'HAB M','orden'=>'6', 'referencia' => 'HABITAT', 'descripcion' => 'Habitat Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'INT M','orden'=>'7', 'referencia' => 'INTEGRA', 'descripcion' => 'Integra Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'PRI M','orden'=>'8', 'referencia' => 'PRIMA', 'descripcion' => 'Prima Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'PRO M','orden'=>'9', 'referencia' => 'PROFUTURO', 'descripcion' => 'Profuturo Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
        ]);
    }
}
