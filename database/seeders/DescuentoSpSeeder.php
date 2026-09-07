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
        DB::table('plan_sp_desc')->upsert([
            ['codigo' => 'HAB F', 'referencia' => 'HABITAT', 'orden' => 1, 'descripcion' => 'Habitat Flujo', 'tipo' => 'Flujo', 'porcentaje' => 13.17, 'porcentaje_65' => 11.67, 'color' => '#000000',],
            ['codigo' => 'HAB M', 'referencia' => 'HABITAT', 'orden' => 6, 'descripcion' => 'Habitat Mixta', 'tipo' => 'Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'color' => '#FF0000',],
            ['codigo' => 'INT F', 'referencia' => 'INTEGRA', 'orden' => 2, 'descripcion' => 'Integra Flujo', 'tipo' => 'Flujo', 'porcentaje' => 13.25, 'porcentaje_65' => 11.75, 'color' => '#33CC33',],
            ['codigo' => 'INT M', 'referencia' => 'INTEGRA', 'orden' => 7, 'descripcion' => 'Integra Mixta', 'tipo' => 'Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'color' => '#00B0F0',],
            ['codigo' => 'PRI F', 'referencia' => 'PRIMA', 'orden' => 3, 'descripcion' => 'Prima Flujo', 'tipo' => 'Flujo', 'porcentaje' => 13.30, 'porcentaje_65' => 11.80, 'color' => '#000000',],
            ['codigo' => 'PRI M', 'referencia' => 'PRIMA', 'orden' => 8, 'descripcion' => 'Prima Mixta', 'tipo' => 'Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'color' => '#DD13CF',],
            ['codigo' => 'PRO F', 'referencia' => 'PROFUTURO', 'orden' => 4, 'descripcion' => 'Profuturo Flujo', 'tipo' => 'Flujo', 'porcentaje' => 13.39, 'porcentaje_65' => 11.89, 'color' => '#0000FF',],
            ['codigo' => 'PRO M', 'referencia' => 'PROFUTURO', 'orden' => 9, 'descripcion' => 'Profuturo Mixta', 'tipo' => 'Mixta', 'porcentaje' => 11.70, 'porcentaje_65' => 10.20, 'color' => '#E26B0A',],
            ['codigo' => 'SNP', 'referencia' => 'SNP', 'orden' => 5, 'descripcion' => 'Sistema Nacional de Pensiones', 'tipo' => null, 'porcentaje' => 13.00, 'porcentaje_65' => 13.00, 'color' => '#000000',],
        ], ['codigo'], ['referencia', 'orden', 'descripcion', 'tipo', 'porcentaje', 'porcentaje_65', 'color',]);
    }
}
