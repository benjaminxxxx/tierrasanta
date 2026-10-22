<?php

namespace Database\Seeders;

use App\Models\Nutriente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NutrientesSeeder extends Seeder
{
    public function run(): void
    {
        $nutrientes = [
            ['codigo' => 'Ca', 'nombre' => 'Calcio', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'Fe', 'nombre' => 'Hierro', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'K', 'nombre' => 'Potasio', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'Mg', 'nombre' => 'Magnesio', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'Mn', 'nombre' => 'Manganeso', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'N', 'nombre' => 'Nitrógeno', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'P', 'nombre' => 'Fósforo', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['codigo' => 'Zn', 'nombre' => 'Zinc', 'unidad' => '%', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
        ];

        foreach ($nutrientes as $nutriente) {
            Nutriente::create($nutriente);
        }
    }
}

