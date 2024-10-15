<?php

namespace Database\Seeders;

use App\Models\Campo;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CamposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campo::truncate();
        
        // Insertar datos de campos
        $campos = [
            ['nombre' => '1', 'grupo' => 'NUMERICO', 'orden' => 1, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 44],
            ['nombre' => '2', 'grupo' => 'NUMERICO', 'orden' => 2, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 104],
            ['nombre' => '3', 'grupo' => 'NUMERICO', 'orden' => 3, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 164],
            ['nombre' => '4', 'grupo' => 'NUMERICO', 'orden' => 4, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 224],
            ['nombre' => '8', 'grupo' => 'NUMERICO', 'orden' => 8, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 284],
            ['nombre' => '9', 'grupo' => 'NUMERICO', 'orden' => 9, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 344],
            ['nombre' => '10', 'grupo' => 'NUMERICO', 'orden' => 10, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 404],

            ['nombre' => '11', 'grupo' => 'NUMERICO2', 'orden' => 11, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 77],
            ['nombre' => '12', 'grupo' => 'NUMERICO2', 'orden' => 12, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 137],
            ['nombre' => '13', 'grupo' => 'NUMERICO2', 'orden' => 13, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 197],
            ['nombre' => '14', 'grupo' => 'NUMERICO2', 'orden' => 14, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 257],
            ['nombre' => '15', 'grupo' => 'NUMERICO2', 'orden' => 15, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 317],
            ['nombre' => '16', 'grupo' => 'NUMERICO2', 'orden' => 15, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 377],
            ['nombre' => '17', 'grupo' => 'NUMERICO2', 'orden' => 17, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 437],
            ['nombre' => '18', 'grupo' => 'NUMERICO2', 'orden' => 18, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 497],
            ['nombre' => '19', 'grupo' => 'NUMERICO2', 'orden' => 19, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 557],
            ['nombre' => '20', 'grupo' => 'NUMERICO2', 'orden' => 20, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 617],
            ['nombre' => '21', 'grupo' => 'NUMERICO2', 'orden' => 21, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 677],
            ['nombre' => '22', 'grupo' => 'NUMERICO2', 'orden' => 22, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 737],

            ['nombre' => 'A1', 'grupo' => 'A', 'orden' => 31, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 952],
            ['nombre' => 'A2', 'grupo' => 'A', 'orden' => 32, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1012],
            ['nombre' => 'A3', 'grupo' => 'A', 'orden' => 33, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1072],
            ['nombre' => 'A4', 'grupo' => 'A', 'orden' => 34, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1132],
            ['nombre' => 'A5', 'grupo' => 'A', 'orden' => 35, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1192],
            ['nombre' => 'A6', 'grupo' => 'A', 'orden' => 36, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1252],
            ['nombre' => 'A7', 'grupo' => 'A', 'orden' => 37, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1312],
            ['nombre' => 'A8', 'grupo' => 'A', 'orden' => 38, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1372],
            ['nombre' => 'A9', 'grupo' => 'A', 'orden' => 39, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1432],
            ['nombre' => 'A10', 'grupo' => 'A', 'orden' => 40, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1492],

            ['nombre' => 'A11', 'grupo' => 'A', 'orden' => 41, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1552],

            ['nombre' => 'B1', 'grupo' => 'B', 'orden' => 51, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1058],
            ['nombre' => 'B2', 'grupo' => 'B', 'orden' => 52, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1118],
            ['nombre' => 'B3', 'grupo' => 'B', 'orden' => 53, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1178],
            ['nombre' => 'B4', 'grupo' => 'B', 'orden' => 54, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1238],
            ['nombre' => 'B5', 'grupo' => 'B', 'orden' => 55, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1298],
            ['nombre' => 'B6', 'grupo' => 'B', 'orden' => 56, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1358],
            ['nombre' => 'B7', 'grupo' => 'B', 'orden' => 57, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1418],
            ['nombre' => 'B8', 'grupo' => 'B', 'orden' => 58, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1478],
            ['nombre' => 'B9', 'grupo' => 'B', 'orden' => 59, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1538],
            ['nombre' => 'B10', 'grupo' => 'B', 'orden' => 60, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1598],
            ['nombre' => 'B11', 'grupo' => 'B', 'orden' => 61, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1658],

            ['nombre' => 'Naranjos', 'grupo' => 'NARANJOS', 'orden' => 62],
            ['nombre' => 'NaranjosB', 'grupo' => 'NARANJOS', 'orden' => 63],

            ['nombre' => 'Limonero3', 'grupo' => 'L', 'orden' => 64],
            ['nombre' => 'Limonero4', 'grupo' => 'L', 'orden' => 65],
            ['nombre' => 'Limonero5', 'grupo' => 'L', 'orden' => 66],

            ['nombre' => 'C1', 'grupo' => 'C', 'orden' => 67],
            ['nombre' => 'C2', 'grupo' => 'C', 'orden' => 68],
            ['nombre' => 'C3', 'grupo' => 'C', 'orden' => 69],
            ['nombre' => 'C4', 'grupo' => 'C', 'orden' => 70],

            
            ['nombre' => 'D3', 'grupo' => 'D', 'orden' => 71],
            ['nombre' => 'D4', 'grupo' => 'D', 'orden' => 72],
            
            ['nombre' => 'F1', 'grupo' => 'F', 'orden' => 73],

            ['nombre' => 'D1', 'grupo' => 'D', 'orden' => 74],
            ['nombre' => 'D2', 'grupo' => 'D', 'orden' => 75],
            ['nombre' => 'D', 'grupo' => 'D', 'orden' => 76],

            
            ['nombre' => 'FDM', 'grupo' => 'FDM', 'orden' => 77],
            ['nombre' => 'E3', 'grupo' => 'F', 'orden' => 78],
        ];
        

        foreach ($campos as $campo) {
            Campo::create($campo);
        }
        $campos2 = [
            // Campos hijos para el grupo 1
            ['nombre' => '1-1', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 100, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '1-2', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 101, 'pos_x' => 36, 'pos_y' => 0],
        
            // Campos hijos para el grupo 2
            ['nombre' => '2-1', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 102, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '2-2', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 103, 'pos_x' => 36, 'pos_y' => 0],
        
            // Campos hijos para el grupo 3
            ['nombre' => '3-1', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 104, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '3-2', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 105, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '3-3', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 106, 'pos_x' => 72, 'pos_y' => 0],
            ['nombre' => '3-4', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 107, 'pos_x' => 108, 'pos_y' => 0],
        
            // Campos hijos para el grupo 4
            ['nombre' => '4-1', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 108, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '4-2', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 109, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '4-3', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 110, 'pos_x' => 72, 'pos_y' => 0],
            ['nombre' => '4-4', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 111, 'pos_x' => 108, 'pos_y' => 0],
        
            // Campos hijos para el grupo 8
            ['nombre' => '8-1', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 112, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '8-2', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 113, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '8-3', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 114, 'pos_x' => 72, 'pos_y' => 0],
        
            // Campos hijos para el grupo 9
            ['nombre' => '9-1', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 115, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '9-2', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 116, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '9-3', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 117, 'pos_x' => 72, 'pos_y' => 0],
        
            // Campos hijos para el grupo 10
            ['nombre' => '10-1', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 118, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '10-2', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 119, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '10-3', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 120, 'pos_x' => 72, 'pos_y' => 0],
        ];

        foreach ($campos2 as $campo2) {
            Campo::create($campo2);
        }

    }

}
