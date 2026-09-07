<?php

namespace Database\Seeders;

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
        //DB::table('configuracion')->truncate();

        DB::table('configuracion')->upsert([
            [
                'codigo' => 'asignacion_familiar',
                'valor' => '102.50',
                'descripcion' => 'Asignación Familiar por Hijo',
            ],
            [
                'codigo' => 'beta30',
                'valor' => '30',
                'descripcion' => 'Beta 30%',
            ],
            [
                'codigo' => 'cts',
                'valor' => 'CTS',
                'descripcion' => '',
            ],
            [
                'codigo' => 'cts_porcentaje',
                'valor' => '9.72',
                'descripcion' => 'CTS',
            ],
            [
                'codigo' => 'descuento_snp',
                'valor' => '13',
                'descripcion' => 'Descuento del SNP',
            ],
            [
                'codigo' => 'essalud',
                'valor' => '6',
                'descripcion' => 'Essalud',
            ],
            [
                'codigo' => 'essalud_eps',
                'valor' => '0.55',
                'descripcion' => 'Essalud EPS',
            ],
            [
                'codigo' => 'essalud_gratificaciones',
                'valor' => '6',
                'descripcion' => 'Essalud Gratificaciones',
            ],
            [
                'codigo' => 'gratificaciones',
                'valor' => '16.66',
                'descripcion' => 'Gratificaciones',
            ],
            [
                'codigo' => 'orden_planilla_asistencia',
                'valor' => json_encode([
                    [
                        'campo' => 'genero',
                        'direccion' => 'desc',
                    ],
                    [
                        'campo' => 'apellido_paterno',
                        'direccion' => 'asc',
                    ],
                    [
                        'campo' => 'apellido_materno',
                        'direccion' => 'asc',
                    ],
                    [
                        'campo' => 'nombres',
                        'direccion' => 'asc',
                    ],
                ]),
                'descripcion' => 'Orden de visualización de la planilla de asistencia mensual',
            ],
            [
                'codigo' => 'pension_sctr',
                'valor' => '1.18',
                'descripcion' => 'Pensión SCTR',
            ],
            [
                'codigo' => 'pension_sctr_porcentaje',
                'valor' => '0.62',
                'descripcion' => 'Pensión SCTR Porcentaje',
            ],
            [
                'codigo' => 'porcentaje_constante',
                'valor' => '1.18',
                'descripcion' => 'Porcentaje Constante',
            ],
            [
                'codigo' => 'rem_basica_essalud',
                'valor' => '1.06',
                'descripcion' => 'Remuneración básica essalud',
            ],
            [
                'codigo' => 'remuneracion_basica',
                'valor' => '34.1666666666667',
                'descripcion' => 'Remuneración Basica',
            ],
            [
                'codigo' => 'rmv',
                'valor' => '1025',
                'descripcion' => 'Remuneración Mínima Vital',
            ],
            [
                'codigo' => 'tiempo_almuerzo',
                'valor' => '60',
                'descripcion' => 'Tiempo de Almuerzo',
            ],
            [
                'codigo' => 'vida_ley',
                'valor' => '1.18',
                'descripcion' => 'Vida Ley',
            ],
            [
                'codigo' => 'vida_ley_porcentaje',
                'valor' => '0.63',
                'descripcion' => 'Vida Ley Porcentaje',
            ],
        ], ['codigo'], ['valor', 'descripcion']);
    }
}
