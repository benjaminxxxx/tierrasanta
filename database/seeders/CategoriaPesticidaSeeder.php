<?php

namespace Database\Seeders;

use App\Models\CategoriaPesticida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaPesticidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categorias = [
            'Insecticidas',
            'Fungicidas',
            'Bactericidas',
            'Enraizadores',
            'Herbicidas',
            'Reguladores de crecimiento',
            'Biológicos',
            'Dispersantes de sales',
        ];
        //CategoriaPesticida::truncate(); referenciado
        foreach ($categorias as $descripcion) {
            CategoriaPesticida::create([
                'codigo' => Str::slug($descripcion),
                'descripcion' => $descripcion,
            ]);
        }
    }
}
