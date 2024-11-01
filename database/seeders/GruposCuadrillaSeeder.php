<?php

namespace Database\Seeders;

use App\Models\CuaGrupo;
use Illuminate\Database\Seeder;

class GruposCuadrillaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuadrillas = [
            ['codigo' => 'CMSR', 'nombre' => 'CUAD. MENSUAL SANTA RITA', 'costo_dia_sugerido' => 75.00, 'color' => '#D0CECE','modalidad_pago'=>'mensual'],
            ['codigo' => 'FUMIG', 'nombre' => 'FUMIGADORES', 'costo_dia_sugerido' => 75.00, 'color' => '#C6E0B4','modalidad_pago'=>'mensual'],
            ['codigo' => 'COSED', 'nombre' => 'COSEDORES', 'costo_dia_sugerido' => 70.00, 'color' => '#FFFF00','modalidad_pago'=>'mensual'],
            ['codigo' => 'CQSR', 'nombre' => 'CUAD. QUINCENAL STA RITA', 'costo_dia_sugerido' => 80.00, 'color' => '#B4C6E7','modalidad_pago'=>'quincenal'],
            ['codigo' => 'CQSR2', 'nombre' => 'CUAD. QUINCENAL SANTA RITA', 'costo_dia_sugerido' => 85.00, 'color' => '#8EA9DB','modalidad_pago'=>'quincenal'],
            ['codigo' => 'CSSR', 'nombre' => 'CUAD. SEMANAL SANTA RITA', 'costo_dia_sugerido' => 90.00, 'color' => '#FFD966','modalidad_pago'=>'semanal'],
            ['codigo' => 'LAJOYA', 'nombre' => 'LA JOYA', 'costo_dia_sugerido' => 85.00, 'color' => '#92D050','modalidad_pago'=>'semanal'],
            ['codigo' => 'AMAMANI', 'nombre' => 'ANDRES MAMANI', 'costo_dia_sugerido' => 81.00, 'color' => '#D0CECE','modalidad_pago'=>'mensual'],
        ];

        foreach ($cuadrillas as $cuadrilla) {
            CuaGrupo::create([
                'codigo' => $cuadrilla['codigo'],
                'nombre' => $cuadrilla['nombre'],
                'costo_dia_sugerido' => $cuadrilla['costo_dia_sugerido'],
                'color' => $cuadrilla['color'],
                'modalidad_pago' => $cuadrilla['modalidad_pago'],
            ]);
        }
    }
}
