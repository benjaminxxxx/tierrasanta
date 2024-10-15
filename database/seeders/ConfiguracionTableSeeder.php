<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla 'configuracion'
        DB::table('configuracion')->truncate();

        DB::table('configuracion')->insert([
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
            [
                'codigo' => 'remuneracion_basica',
                'valor' => '34.1666666666667',
                'descripcion' => 'Remuneración Basica',
            ],
            [
                'codigo' => 'cts_porcentaje',
                'valor' => '9.72',
                'descripcion' => 'CTS',
            ],
            [
                'codigo' => 'gratificaciones',
                'valor' => '16.66',
                'descripcion' => 'Gratificaciones',
            ],
            [
                'codigo' => 'essalud_gratificaciones',
                'valor' => '6',
                'descripcion' => 'Essalud Gratificaciones',
            ],
            [
                'codigo' => 'beta30',
                'valor' => '30',
                'descripcion' => 'Beta 30%',
            ],
            [
                'codigo' => 'essalud',
                'valor' => '6',
                'descripcion' => 'Essalud',
            ],
            [
                'codigo' => 'vida_ley_porcentaje',
                'valor' => '0.63',
                'descripcion' => 'Vida Ley Porcentaje',
            ],
            [
                'codigo' => 'vida_ley',
                'valor' => '1.18',
                'descripcion' => 'Vida Ley',
            ],
            [
                'codigo' => 'pension_sctr_porcentaje',
                'valor' => '0.62',
                'descripcion' => 'Pensión SCTR Porcentaje',
            ],
            [
                'codigo' => 'pension_sctr',
                'valor' => '1.18',
                'descripcion' => 'Pensión SCTR',
            ],
            [
                'codigo' => 'tiempo_almuerzo',
                'valor' => '60',
                'descripcion' => 'Tiempo de Almuerzo',
            ],
            [
                'codigo' => 'essalud_eps',
                'valor' => '0.55',
                'descripcion' => 'Essalud EPS',
            ],
            [
                'codigo' => 'porcentaje_constante',
                'valor' => '1.18',
                'descripcion' => 'Porcentaje Constante',
            ]
        ]);
    }
}
