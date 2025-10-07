<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NutrientesSeeder extends Seeder
{
    public function run(): void
    {
        // Truncar la tabla antes de insertar nuevos datos
        //DB::table('nutrientes')->truncate();
        // Insertar los nutrientes
        DB::table('nutrientes')->insert([
            ['codigo' => 'N', 'nombre' => 'Nitrógeno', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'P', 'nombre' => 'Fósforo', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'K', 'nombre' => 'Potasio', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'Ca', 'nombre' => 'Calcio', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'Mg', 'nombre' => 'Magnesio', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'Zn', 'nombre' => 'Zinc', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'Mn', 'nombre' => 'Manganeso', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'Fe', 'nombre' => 'Hierro', 'unidad' => '%', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

