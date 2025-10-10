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
        //truncar
        //DB::table('descuento_sp')->truncate();
        DB::table('plan_sp_desc')->insert([
            ['codigo' => 'HAB F','orden'=>'1','color'=>'#000000', 'referencia' => 'HABITAT', 'descripcion' => 'Habitat Flujo', 'porcentaje' => 13.17, 'porcentaje_65' => 11.67, 'tipo' => 'Flujo'],
            ['codigo' => 'INT F','orden'=>'2','color'=>'#33CC33', 'referencia' => 'INTEGRA', 'descripcion' => 'Integra Flujo', 'porcentaje' => 13.25, 'porcentaje_65' => 11.75, 'tipo' => 'Flujo'],
            ['codigo' => 'PRI F','orden'=>'3','color'=>'#000000', 'referencia' => 'PRIMA', 'descripcion' => 'Prima Flujo', 'porcentaje' => 13.30, 'porcentaje_65' => 11.80, 'tipo' => 'Flujo'],
            ['codigo' => 'PRO F','orden'=>'4','color'=>'#0000FF', 'referencia' => 'PROFUTURO', 'descripcion' => 'Profuturo Flujo', 'porcentaje' => 13.39, 'porcentaje_65' => 11.89, 'tipo' => 'Flujo'],
            ['codigo' => 'SNP','orden'=>'5','color'=>'#000000', 'referencia' => 'SNP', 'descripcion' => 'Sistema Nacional de Pensiones', 'porcentaje' => 13.00, 'porcentaje_65' => 13.00, 'tipo' => null],
            ['codigo' => 'HAB M','orden'=>'6','color'=>'#FF0000', 'referencia' => 'HABITAT', 'descripcion' => 'Habitat Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'INT M','orden'=>'7','color'=>'#00B0F0', 'referencia' => 'INTEGRA', 'descripcion' => 'Integra Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'PRI M','orden'=>'8','color'=>'#DD13CF', 'referencia' => 'PRIMA', 'descripcion' => 'Prima Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
            ['codigo' => 'PRO M','orden'=>'9','color'=>'#E26B0A', 'referencia' => 'PROFUTURO', 'descripcion' => 'Profuturo Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'tipo' => 'Mixta'],
        ]);
    }
}
