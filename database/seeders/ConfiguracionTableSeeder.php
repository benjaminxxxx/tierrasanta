<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfiguracionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla 'configuracion'
        \DB::table('configuracion')->truncate();

        \DB::table('configuracion')->insert([
            [
                'codigo' => 'rmv',
                'valor' => '1025', // Almacena como texto
                'descripcion' => 'Remuneración Mínima Vital',
            ],
            [
                'codigo' => 'asignacion_familiar',
                'valor' => '102.50', // Almacena como texto
                'descripcion' => 'Asignación Familiar por Hijo',
            ],
            [
                'codigo' => 'descuento_snp',
                'valor' => '13',
                'descripcion' => 'Descuento del SNP',
            ],
        ]);
    }
}
