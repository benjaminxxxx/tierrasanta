<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaborRiegoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reg_labores')->insert([
            ['id' => 1, 'nombre_labor' => 'Instalación Arco de Riego', 'created_at' => '2024-10-16 01:22:44', 'updated_at' => '2024-10-16 01:22:44'],
            ['id' => 2, 'nombre_labor' => 'Reparación de Tuberia', 'created_at' => '2024-10-16 01:22:58', 'updated_at' => '2024-10-16 01:22:58'],
            ['id' => 4, 'nombre_labor' => 'Mantenimiento de Válvula', 'created_at' => '2024-10-16 01:25:27', 'updated_at' => '2024-10-16 01:25:27'],
            ['id' => 5, 'nombre_labor' => 'Lavado de Filtro', 'created_at' => '2024-10-16 01:28:09', 'updated_at' => '2024-10-16 01:28:09'],
            ['id' => 9, 'nombre_labor' => 'Riego', 'created_at' => '2024-10-21 18:02:09', 'updated_at' => '2024-10-21 18:02:09'],
            ['id' => 10, 'nombre_labor' => 'Observacion', 'created_at' => '2024-10-21 19:32:43', 'updated_at' => '2024-10-21 19:32:43'],
            ['id' => 12, 'nombre_labor' => 'Instalación de Tuberia', 'created_at' => '2024-10-22 17:22:02', 'updated_at' => '2024-10-22 17:22:02'],
            ['id' => 13, 'nombre_labor' => 'Colocación de Cinta', 'created_at' => '2024-10-22 17:24:04', 'updated_at' => '2024-10-22 17:24:04'],
            ['id' => 15, 'nombre_labor' => 'Mantenimiento de Estanque', 'created_at' => '2024-11-15 18:57:15', 'updated_at' => '2024-11-15 18:57:15'],
            ['id' => 16, 'nombre_labor' => 'Instalación Sistema de Aspersión', 'created_at' => '2024-11-15 18:58:22', 'updated_at' => '2024-11-15 18:58:22'],
            ['id' => 17, 'nombre_labor' => 'Apoyo Riego', 'created_at' => '2024-11-15 19:14:37', 'updated_at' => '2024-11-15 19:14:37'],
            ['id' => 18, 'nombre_labor' => 'instalación salidas para cinta de riego', 'created_at' => '2024-12-19 18:36:40', 'updated_at' => '2024-12-19 18:36:40'],
            ['id' => 20, 'nombre_labor' => 'Instalación salidas para sistema de aspersión', 'created_at' => '2024-12-19 18:38:00', 'updated_at' => '2024-12-19 18:38:00'],
            ['id' => 21, 'nombre_labor' => 'instalación salidas para cinta de riego', 'created_at' => '2024-12-19 18:38:17', 'updated_at' => '2024-12-19 18:38:17'],
            ['id' => 22, 'nombre_labor' => 'Desmonte sistema de aspersión', 'created_at' => '2024-12-19 18:38:40', 'updated_at' => '2024-12-19 18:38:40'],
            ['id' => 23, 'nombre_labor' => 'Parchado de cinta', 'created_at' => '2024-12-19 18:38:56', 'updated_at' => '2024-12-19 18:38:56'],
            ['id' => 24, 'nombre_labor' => 'Deshierbo', 'created_at' => '2024-12-19 18:39:16', 'updated_at' => '2024-12-19 18:39:16'],
            ['id' => 25, 'nombre_labor' => 'Cerco Perimetrico', 'created_at' => '2025-08-05 15:01:17', 'updated_at' => '2025-08-05 15:01:17'],
        ]);
    }
}
