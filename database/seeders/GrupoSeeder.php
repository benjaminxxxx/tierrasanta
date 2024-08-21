<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('grupos')->insert([
            [
                'codigo' => 'PLAANT',
                'descripcion' => 'PLANILLA ANTIGUA',
                'color' => '#E6B8B7',
            ],
            [
                'codigo' => 'GRUQUIVAR',
                'descripcion' => 'GRUPO QUINCENAL VARONES',
                'color' => '#B7ECFF',
            ],
            [
                'codigo' => 'GRUQUIMUJ',
                'descripcion' => 'GRUPO QUINCENAL MUJERES',
                'color' => '#FFDDFF',
            ],
        ]);
    }
}
