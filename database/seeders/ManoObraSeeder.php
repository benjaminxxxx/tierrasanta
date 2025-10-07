<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ManoObraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mano_obras')->insert([
            ['codigo' => 'cosecha', 'descripcion' => 'Cosecha', 'created_at' => '2025-08-01 09:35:24', 'updated_at' => '2025-08-01 09:35:24'],
            ['codigo' => 'fdm', 'descripcion' => 'FDM', 'created_at' => '2025-08-01 09:36:05', 'updated_at' => '2025-08-01 09:36:05'],
            ['codigo' => 'infestacion', 'descripcion' => 'Infestación', 'created_at' => '2025-08-01 09:34:06', 'updated_at' => '2025-08-01 09:34:06'],
            ['codigo' => 'labores_culturales', 'descripcion' => 'Labores culturales', 'created_at' => '2025-08-01 09:35:09', 'updated_at' => '2025-08-01 09:35:09'],
            ['codigo' => 'labores_mantenimiento', 'descripcion' => 'Labores de mantenimiento', 'created_at' => '2025-08-01 09:36:36', 'updated_at' => '2025-08-01 09:36:36'],
            ['codigo' => 'naranja', 'descripcion' => 'Naranja', 'created_at' => '2025-08-01 09:36:20', 'updated_at' => '2025-08-01 09:36:20'],
            ['codigo' => 'postcosecha', 'descripcion' => 'Postcosecha', 'created_at' => '2025-08-01 09:35:39', 'updated_at' => '2025-08-01 09:35:39'],
            ['codigo' => 'precosecha', 'descripcion' => 'Pre-cosecha', 'created_at' => '2025-08-01 09:34:42', 'updated_at' => '2025-08-01 09:34:42'],
            ['codigo' => 'preparacion_terreno', 'descripcion' => 'Preparación de terreno', 'created_at' => '2025-08-01 09:33:36', 'updated_at' => '2025-08-01 09:33:36'],
            ['codigo' => 'reinfestacion', 'descripcion' => 'Re-infestación', 'created_at' => '2025-08-01 09:34:26', 'updated_at' => '2025-08-01 09:34:26'],
            ['codigo' => 'riego_fertilizacion', 'descripcion' => 'Riego y fertilización', 'created_at' => '2025-08-01 09:35:56', 'updated_at' => '2025-08-01 09:35:56'],
            ['codigo' => 'sanidad', 'descripcion' => 'Sanidad', 'created_at' => '2025-08-01 09:34:54', 'updated_at' => '2025-08-01 09:34:54'],
            ['codigo' => 'siembra', 'descripcion' => 'Siembra', 'created_at' => '2025-08-01 09:33:52', 'updated_at' => '2025-08-01 09:33:52'],
        ]);
    }
}
