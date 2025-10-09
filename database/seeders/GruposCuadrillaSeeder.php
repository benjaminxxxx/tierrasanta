<?php

namespace Database\Seeders;

use App\Models\CuaGrupo;
use DB;
use Illuminate\Database\Seeder;

class GruposCuadrillaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('cuad_grupos')->insert([
            [
                'codigo' => 'CMSR',
                'color' => '#D0CECE',
                'nombre' => 'CUAD. MENSUAL SANTA RITA',
                'modalidad_pago' => 'mensual',
                'costo_dia_sugerido' => 115.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-10-02 17:52:21',
            ],
            [
                'codigo' => 'COSED',
                'color' => '#FFFF00',
                'nombre' => 'COSEDORES',
                'modalidad_pago' => 'mensual',
                'costo_dia_sugerido' => 70.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-12 20:01:55',
            ],
            [
                'codigo' => 'CQSR',
                'color' => '#B4C6E7',
                'nombre' => 'CUAD. QUINCENAL STA RITA',
                'modalidad_pago' => 'quincenal',
                'costo_dia_sugerido' => 100.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-12 20:01:55',
            ],
            [
                'codigo' => 'CQSR2',
                'color' => '#8EA9DB',
                'nombre' => 'CUAD. QUINCENAL SANTA RITA',
                'modalidad_pago' => 'quincenal',
                'costo_dia_sugerido' => 85.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-05 16:04:48',
            ],
            [
                'codigo' => 'CSC',
                'color' => '#1feacc',
                'nombre' => 'CUAD. SEMANAL CARGADORES',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 120.00,
                'created_at' => '2025-07-08 19:41:20',
                'updated_at' => '2025-08-12 20:01:55',
            ],
            [
                'codigo' => 'CSJ',
                'color' => '#51cd59',
                'nombre' => 'CUAD. SEMANAL LA JOYA',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 120.00,
                'created_at' => '2024-11-02 09:01:25',
                'updated_at' => '2025-08-12 20:01:55',
            ],
            [
                'codigo' => 'CSSR',
                'color' => '#FF8904',
                'nombre' => 'CUAD. SEMANAL SANTA RITA-NANCY',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 110.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-12 20:01:55',
            ],
            [
                'codigo' => 'CSSRD',
                'color' => '#f6b11e',
                'nombre' => 'CUAD. SEMANAL SANTA RITA- DESBROTADORAS',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 100.00,
                'created_at' => '2025-06-25 19:24:19',
                'updated_at' => '2025-08-12 20:01:55'
            ],
            [
                'codigo' => 'CSSRFL',
                'color' => '#FF6467',
                'nombre' => 'CUAD. SEMANAL SANTA RITA IV-FL',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 110.00,
                'created_at' => '2025-08-05 16:00:26',
                'updated_at' => '2025-08-12 20:01:55'
            ],
            [
                'codigo' => 'CSSRII',
                'color' => '#c1bb0b',
                'nombre' => 'CUAD. SEMANAL SANTA RITA-PATY',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 105.00,
                'created_at' => '2025-03-28 20:39:42',
                'updated_at' => '2025-08-12 20:01:55'
            ],
            [
                'codigo' => 'CSSRRY',
                'color' => '#FF637E',
                'nombre' => 'CUAD. SEMANAL SANTA RITA V-RY',
                'modalidad_pago' => 'semanal',
                'costo_dia_sugerido' => 110.00,
                'created_at' => '2025-08-05 16:01:03',
                'updated_at' => '2025-08-12 20:01:55'
            ],
            [
                'codigo' => 'FUMIG',
                'color' => '#C6E0B4',
                'nombre' => 'FUMIGADORES',
                'modalidad_pago' => 'mensual',
                'costo_dia_sugerido' => 75.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-05 16:04:40'
            ],
            [
                'codigo' => 'LAJOYA',
                'color' => '#92D050',
                'nombre' => 'CUAD. QUINCENAL LA JOYA',
                'modalidad_pago' => 'quincenal',
                'costo_dia_sugerido' => 0.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-12 20:01:55'
            ],
            [
                'codigo' => 'REGADOR',
                'color' => '#D0CECE',
                'nombre' => 'REGADORES',
                'modalidad_pago' => 'mensual',
                'costo_dia_sugerido' => 81.00,
                'created_at' => '2024-08-25 14:20:55',
                'updated_at' => '2025-08-05 16:04:32'
            ]
        ]);
    }
}
