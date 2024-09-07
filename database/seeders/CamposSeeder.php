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
        // Insertar datos de campos
        $campos = [
            ['nombre' => '1', 'grupo' => 'NUMERICO', 'orden' => 1, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 44],
            ['nombre' => '10', 'grupo' => 'NUMERICO', 'orden' => 7, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 404],
            ['nombre' => '11', 'grupo' => 'NUMERICO2', 'orden' => 1, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 77],
            ['nombre' => '12', 'grupo' => 'NUMERICO2', 'orden' => 2, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 137],
            ['nombre' => '13', 'grupo' => 'NUMERICO2', 'orden' => 3, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 197],
            ['nombre' => '14', 'grupo' => 'NUMERICO2', 'orden' => 4, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 257],
            ['nombre' => '15', 'grupo' => 'NUMERICO2', 'orden' => 5, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 317],
            ['nombre' => '16', 'grupo' => 'NUMERICO2', 'orden' => 5, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 377],
            ['nombre' => '17', 'grupo' => 'NUMERICO2', 'orden' => 7, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 437],
            ['nombre' => '18', 'grupo' => 'NUMERICO2', 'orden' => 8, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 497],
            ['nombre' => '19', 'grupo' => 'NUMERICO2', 'orden' => 9, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 557],
            ['nombre' => '2', 'grupo' => 'NUMERICO', 'orden' => 2, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 104],
            ['nombre' => '20', 'grupo' => 'NUMERICO2', 'orden' => 10, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 617],
            ['nombre' => '21', 'grupo' => 'NUMERICO2', 'orden' => 11, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 677],
            ['nombre' => '22', 'grupo' => 'NUMERICO2', 'orden' => 12, 'estado' => null, 'area' => null, 'pos_x' => 694, 'pos_y' => 737],
            ['nombre' => '3', 'grupo' => 'NUMERICO', 'orden' => 3, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 164],
            ['nombre' => '4', 'grupo' => 'NUMERICO', 'orden' => 4, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 224],
            ['nombre' => '8', 'grupo' => 'NUMERICO', 'orden' => 5, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 284],
            ['nombre' => '9', 'grupo' => 'NUMERICO', 'orden' => 6, 'estado' => null, 'area' => null, 'pos_x' => 36, 'pos_y' => 344],
            ['nombre' => 'A-1', 'grupo' => 'A', 'orden' => 1, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 952],
            ['nombre' => 'A-10', 'grupo' => 'A', 'orden' => 10, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1492],
            ['nombre' => 'A-11', 'grupo' => 'A', 'orden' => 11, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1552],
            ['nombre' => 'A-2', 'grupo' => 'A', 'orden' => 2, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1012],
            ['nombre' => 'A-3', 'grupo' => 'A', 'orden' => 3, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1072],
            ['nombre' => 'A-4', 'grupo' => 'A', 'orden' => 4, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1132],
            ['nombre' => 'A-5', 'grupo' => 'A', 'orden' => 5, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1192],
            ['nombre' => 'A-6', 'grupo' => 'A', 'orden' => 6, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1252],
            ['nombre' => 'A-7', 'grupo' => 'A', 'orden' => 7, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1312],
            ['nombre' => 'A-8', 'grupo' => 'A', 'orden' => 8, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1372],
            ['nombre' => 'A-9', 'grupo' => 'A', 'orden' => 9, 'estado' => null, 'area' => null, 'pos_x' => 139, 'pos_y' => 1432],
            ['nombre' => 'B-1', 'grupo' => 'B', 'orden' => 1, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1058],
            ['nombre' => 'B-10', 'grupo' => 'B', 'orden' => 10, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1598],
            ['nombre' => 'B-11', 'grupo' => 'B', 'orden' => 11, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1658],
            ['nombre' => 'B-2', 'grupo' => 'B', 'orden' => 2, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1118],
            ['nombre' => 'B-3', 'grupo' => 'B', 'orden' => 3, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1178],
            ['nombre' => 'B-4', 'grupo' => 'B', 'orden' => 4, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1238],
            ['nombre' => 'B-5', 'grupo' => 'B', 'orden' => 5, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1298],
            ['nombre' => 'B-6', 'grupo' => 'B', 'orden' => 6, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1358],
            ['nombre' => 'B-7', 'grupo' => 'B', 'orden' => 7, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1418],
            ['nombre' => 'B-8', 'grupo' => 'B', 'orden' => 8, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1478],
            ['nombre' => 'B-9', 'grupo' => 'B', 'orden' => 9, 'estado' => null, 'area' => null, 'pos_x' => 439, 'pos_y' => 1538],
            ['nombre' => 'FDM', 'grupo' => 'FDM', 'orden' => 1],
            ['nombre' => 'NB', 'grupo' => 'NB', 'orden' => 1],
            ['nombre' => 'NARANJOS', 'grupo' => 'NARANJOS', 'orden' => 1],
            ['nombre' => 'C-1', 'grupo' => 'C', 'orden' => 1],
            ['nombre' => 'C-2', 'grupo' => 'C', 'orden' => 2],
            ['nombre' => 'C-3', 'grupo' => 'C', 'orden' => 3],
            ['nombre' => 'C-4', 'grupo' => 'C', 'orden' => 4],
            ['nombre' => 'D', 'grupo' => 'D', 'orden' => 1],
            ['nombre' => 'D1', 'grupo' => 'D', 'orden' => 2],
            ['nombre' => 'D-3', 'grupo' => 'D', 'orden' => 3],
            ['nombre' => 'D-4', 'grupo' => 'D', 'orden' => 4],
            ['nombre' => 'F-1', 'grupo' => 'F', 'orden' => 1],
            ['nombre' => 'E-3', 'grupo' => 'F', 'orden' => 2],
            ['nombre' => 'L-3', 'grupo' => 'L', 'orden' => 1],
            ['nombre' => 'L-4', 'grupo' => 'L', 'orden' => 2],
            ['nombre' => 'L-5', 'grupo' => 'L', 'orden' => 3],
        ];
        

        foreach ($campos as $campo) {
            Campo::create($campo);
        }
        $campos2 = [
            // Campos hijos para el grupo 1
            ['nombre' => '1-1', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '1-2', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
        
            // Campos hijos para el grupo 2
            ['nombre' => '2-1', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '2-2', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
        
            // Campos hijos para el grupo 3
            ['nombre' => '3-1', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '3-2', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '3-3', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 3, 'pos_x' => 72, 'pos_y' => 0],
            ['nombre' => '3-4', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 4, 'pos_x' => 108, 'pos_y' => 0],
        
            // Campos hijos para el grupo 4
            ['nombre' => '4-1', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '4-2', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '4-3', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 3, 'pos_x' => 72, 'pos_y' => 0],
            ['nombre' => '4-4', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 4, 'pos_x' => 108, 'pos_y' => 0],
        
            // Campos hijos para el grupo 8
            ['nombre' => '8-1', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '8-2', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '8-3', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 3, 'pos_x' => 72, 'pos_y' => 0],
        
            // Campos hijos para el grupo 9
            ['nombre' => '9-1', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '9-2', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '9-3', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 3, 'pos_x' => 72, 'pos_y' => 0],
        
            // Campos hijos para el grupo 10
            ['nombre' => '10-1', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 1, 'pos_x' => 0, 'pos_y' => 0],
            ['nombre' => '10-2', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 2, 'pos_x' => 36, 'pos_y' => 0],
            ['nombre' => '10-3', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 3, 'pos_x' => 72, 'pos_y' => 0],
        ];

        foreach ($campos2 as $campo2) {
            Campo::create($campo2);
        }

    }

}
