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
        //Campo::truncate();
        
        // Campos principales (sin parent)
        $campos = [
            // Grupo NUMERICO
            ['nombre' => '1', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 1, 'estado' => null, 'etapa' => null, 'area' => 3.134, 'pos_x' => 59.00, 'pos_y' => 53.00, 'alias' => null],
            ['nombre' => '2', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 2, 'estado' => null, 'etapa' => null, 'area' => 2.321, 'pos_x' => 59.00, 'pos_y' => 113.00, 'alias' => null],
            ['nombre' => '3', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 3, 'estado' => null, 'etapa' => null, 'area' => 4.201, 'pos_x' => 59.00, 'pos_y' => 173.00, 'alias' => null],
            ['nombre' => '4', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 4, 'estado' => null, 'etapa' => null, 'area' => 4.19, 'pos_x' => 59.00, 'pos_y' => 233.00, 'alias' => null],
            ['nombre' => '8', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 8, 'estado' => null, 'etapa' => null, 'area' => 3.258, 'pos_x' => 59.00, 'pos_y' => 293.00, 'alias' => null],
            ['nombre' => '9', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 9, 'estado' => null, 'etapa' => null, 'area' => 4.336, 'pos_x' => 59.00, 'pos_y' => 353.00, 'alias' => null],
            ['nombre' => '10', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 10, 'estado' => null, 'etapa' => null, 'area' => 3.982, 'pos_x' => 59.00, 'pos_y' => 413.00, 'alias' => null],

            // Grupo NUMERICO2
            ['nombre' => '11', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 11, 'estado' => null, 'etapa' => null, 'area' => 2.788, 'pos_x' => 694.00, 'pos_y' => 77.00, 'alias' => null],
            ['nombre' => '12', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 12, 'estado' => null, 'etapa' => null, 'area' => 2.89, 'pos_x' => 694.00, 'pos_y' => 137.00, 'alias' => null],
            ['nombre' => '13', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 13, 'estado' => null, 'etapa' => null, 'area' => 2.298, 'pos_x' => 694.00, 'pos_y' => 197.00, 'alias' => null],
            ['nombre' => '14', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 14, 'estado' => null, 'etapa' => null, 'area' => 2.367, 'pos_x' => 694.00, 'pos_y' => 257.00, 'alias' => null],
            ['nombre' => '15', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 15, 'estado' => null, 'etapa' => null, 'area' => 2.518, 'pos_x' => 694.00, 'pos_y' => 317.00, 'alias' => null],
            ['nombre' => '16', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 15, 'estado' => null, 'etapa' => null, 'area' => 2.271, 'pos_x' => 694.00, 'pos_y' => 377.00, 'alias' => null],
            ['nombre' => '17', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 17, 'estado' => null, 'etapa' => null, 'area' => 2.118, 'pos_x' => 694.00, 'pos_y' => 437.00, 'alias' => null],
            ['nombre' => '18', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 18, 'estado' => null, 'etapa' => null, 'area' => 1.907, 'pos_x' => 694.00, 'pos_y' => 497.00, 'alias' => null],
            ['nombre' => '19', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 19, 'estado' => null, 'etapa' => null, 'area' => 2.077, 'pos_x' => 694.00, 'pos_y' => 557.00, 'alias' => null],
            ['nombre' => '20', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 20, 'estado' => null, 'etapa' => null, 'area' => 2.161, 'pos_x' => 694.00, 'pos_y' => 617.00, 'alias' => null],
            ['nombre' => '21', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 21, 'estado' => null, 'etapa' => null, 'area' => 1.933, 'pos_x' => 694.00, 'pos_y' => 677.00, 'alias' => null],
            ['nombre' => '22', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 22, 'estado' => null, 'etapa' => null, 'area' => 1.865, 'pos_x' => 694.00, 'pos_y' => 737.00, 'alias' => null],

            // Grupo A
            ['nombre' => 'A1', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 31, 'estado' => null, 'etapa' => null, 'area' => 3.392, 'pos_x' => 139.00, 'pos_y' => 952.00, 'alias' => 'a-1'],
            ['nombre' => 'A2', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 32, 'estado' => null, 'etapa' => null, 'area' => 1.98, 'pos_x' => 139.00, 'pos_y' => 1012.00, 'alias' => 'a-2'],
            ['nombre' => 'A3', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 33, 'estado' => null, 'etapa' => null, 'area' => 2.817, 'pos_x' => 139.00, 'pos_y' => 1072.00, 'alias' => 'a.3,a-3'],
            ['nombre' => 'A4', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 34, 'estado' => null, 'etapa' => null, 'area' => 2.798, 'pos_x' => 139.00, 'pos_y' => 1132.00, 'alias' => 'a-4'],
            ['nombre' => 'A5', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 35, 'estado' => null, 'etapa' => null, 'area' => 3.066, 'pos_x' => 139.00, 'pos_y' => 1192.00, 'alias' => 'a-5'],
            ['nombre' => 'A6', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 36, 'estado' => null, 'etapa' => null, 'area' => 3.197, 'pos_x' => 139.00, 'pos_y' => 1252.00, 'alias' => 'a-6'],
            ['nombre' => 'A7', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 37, 'estado' => null, 'etapa' => null, 'area' => 3.228, 'pos_x' => 139.00, 'pos_y' => 1312.00, 'alias' => 'a-7'],
            ['nombre' => 'A8', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 38, 'estado' => null, 'etapa' => null, 'area' => 3.033, 'pos_x' => 139.00, 'pos_y' => 1372.00, 'alias' => 'a-8'],
            ['nombre' => 'A9', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 39, 'estado' => null, 'etapa' => null, 'area' => 2.959, 'pos_x' => 139.00, 'pos_y' => 1432.00, 'alias' => 'a-9'],
            ['nombre' => 'A10', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 40, 'estado' => null, 'etapa' => null, 'area' => 2.979, 'pos_x' => 139.00, 'pos_y' => 1492.00, 'alias' => 'a-10,a.10,a 10'],
            ['nombre' => 'A11', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 41, 'estado' => null, 'etapa' => null, 'area' => 3.456, 'pos_x' => 139.00, 'pos_y' => 1552.00, 'alias' => null],

            // Grupo B
            ['nombre' => 'B1', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 51, 'estado' => null, 'etapa' => null, 'area' => 2.764, 'pos_x' => 439.00, 'pos_y' => 1058.00, 'alias' => 'b-1'],
            ['nombre' => 'B2', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 52, 'estado' => null, 'etapa' => null, 'area' => 2.394, 'pos_x' => 439.00, 'pos_y' => 1118.00, 'alias' => 'b-2'],
            ['nombre' => 'B3', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 53, 'estado' => null, 'etapa' => null, 'area' => 3.056, 'pos_x' => 439.00, 'pos_y' => 1178.00, 'alias' => 'b-3'],
            ['nombre' => 'B4', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 54, 'estado' => null, 'etapa' => null, 'area' => 2.501, 'pos_x' => 439.00, 'pos_y' => 1238.00, 'alias' => 'b-4'],
            ['nombre' => 'B5', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 55, 'estado' => null, 'etapa' => null, 'area' => 2.714, 'pos_x' => 439.00, 'pos_y' => 1298.00, 'alias' => 'b-5'],
            ['nombre' => 'B6', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 56, 'estado' => null, 'etapa' => null, 'area' => 3.063, 'pos_x' => 439.00, 'pos_y' => 1358.00, 'alias' => 'b-6'],
            ['nombre' => 'B7', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 57, 'estado' => null, 'etapa' => null, 'area' => 3.167, 'pos_x' => 439.00, 'pos_y' => 1418.00, 'alias' => null],
            ['nombre' => 'B8', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 58, 'estado' => null, 'etapa' => null, 'area' => 3.042, 'pos_x' => 439.00, 'pos_y' => 1478.00, 'alias' => null],
            ['nombre' => 'B9', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 59, 'estado' => null, 'etapa' => null, 'area' => 3.523, 'pos_x' => 439.00, 'pos_y' => 1538.00, 'alias' => 'b-9'],
            ['nombre' => 'B10', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 60, 'estado' => null, 'etapa' => null, 'area' => 2.431, 'pos_x' => 439.00, 'pos_y' => 1598.00, 'alias' => null],
            ['nombre' => 'B11', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 61, 'estado' => null, 'etapa' => null, 'area' => 3.968, 'pos_x' => 439.00, 'pos_y' => 1658.00, 'alias' => null],

            // Grupo NARANJOS
            ['nombre' => 'Naranjos', 'campo_parent_nombre' => null, 'grupo' => 'NARANJOS', 'orden' => 62, 'estado' => null, 'etapa' => null, 'area' => 2.766, 'pos_x' => 381.00, 'pos_y' => 770.00, 'alias' => null],
            ['nombre' => 'NaranjosB', 'campo_parent_nombre' => null, 'grupo' => 'NARANJOS', 'orden' => 63, 'estado' => null, 'etapa' => null, 'area' => 2.383, 'pos_x' => 383.00, 'pos_y' => 832.00, 'alias' => 'n-b,n_b,nb'],

            // Grupo L (Limoneros)
            ['nombre' => 'Limonero3', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 64, 'estado' => null, 'etapa' => null, 'area' => 1.95, 'pos_x' => 247.00, 'pos_y' => 710.00, 'alias' => 'l-3'],
            ['nombre' => 'Limonero4', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 65, 'estado' => null, 'etapa' => null, 'area' => 1.173, 'pos_x' => 245.00, 'pos_y' => 776.00, 'alias' => 'l-4'],
            ['nombre' => 'Limonero5', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 66, 'estado' => null, 'etapa' => null, 'area' => 0.977, 'pos_x' => 245.00, 'pos_y' => 840.00, 'alias' => 'l-5'],

            // Grupo C
            ['nombre' => 'C1', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 67, 'estado' => null, 'etapa' => null, 'area' => 1.953, 'pos_x' => 544.00, 'pos_y' => 725.00, 'alias' => 'c-1'],
            ['nombre' => 'C2', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 68, 'estado' => null, 'etapa' => null, 'area' => 2.998, 'pos_x' => 544.00, 'pos_y' => 787.00, 'alias' => 'c-2'],
            ['nombre' => 'C3', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 69, 'estado' => null, 'etapa' => null, 'area' => 3.162, 'pos_x' => 545.00, 'pos_y' => 851.00, 'alias' => 'c-3'],
            ['nombre' => 'C4', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 70, 'estado' => null, 'etapa' => null, 'area' => 3.322, 'pos_x' => 545.00, 'pos_y' => 907.00, 'alias' => 'c-4'],

            // Grupo D
            ['nombre' => 'D3', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 71, 'estado' => null, 'etapa' => null, 'area' => 3.439, 'pos_x' => 641.00, 'pos_y' => 1145.00, 'alias' => 'd-3'],
            ['nombre' => 'D4', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 72, 'estado' => null, 'etapa' => null, 'area' => 3.129, 'pos_x' => 643.00, 'pos_y' => 1207.00, 'alias' => null],

            // Grupo F
            ['nombre' => 'F1', 'campo_parent_nombre' => null, 'grupo' => 'F', 'orden' => 73, 'estado' => null, 'etapa' => null, 'area' => 4.123, 'pos_x' => 46.00, 'pos_y' => 690.00, 'alias' => null],

            // Grupo D (continuación)
            ['nombre' => 'D1', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 74, 'estado' => null, 'etapa' => null, 'area' => 1.6, 'pos_x' => 641.00, 'pos_y' => 1031.00, 'alias' => null],
            ['nombre' => 'D2', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 75, 'estado' => null, 'etapa' => null, 'area' => 1, 'pos_x' => 641.00, 'pos_y' => 1087.00, 'alias' => null],
            ['nombre' => 'D', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 76, 'estado' => null, 'etapa' => null, 'area' => 1.5, 'pos_x' => 642.00, 'pos_y' => 973.00, 'alias' => null],

            // Grupo FDM
            ['nombre' => 'FDM', 'campo_parent_nombre' => null, 'grupo' => 'FDM', 'orden' => 77, 'estado' => null, 'etapa' => null, 'area' => 1, 'pos_x' => 47.00, 'pos_y' => 613.00, 'alias' => null],

            // Grupo F (continuación)
            ['nombre' => 'E3', 'campo_parent_nombre' => null, 'grupo' => 'F', 'orden' => 78, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 365.00, 'pos_y' => 565.00, 'alias' => null],

            // Campos en desuso (sin grupo definido o grupo DESUSO)
            ['nombre' => 'CARMEN', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 3.056, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'CE', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 2.383, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'E7', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 1.83, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'F7', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 2.37, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'I-3', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 0, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'JUANC', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 2.394, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'Limonero3B', 'campo_parent_nombre' => 'Limonero3', 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 0.6986, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'Limonero4A', 'campo_parent_nombre' => 'Limonero4', 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 1.1734, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
            ['nombre' => 'Limonero5A', 'campo_parent_nombre' => 'Limonero5', 'grupo' => null, 'orden' => null, 'estado' => null, 'etapa' => null, 'area' => 1.5, 'pos_x' => null, 'pos_y' => null, 'alias' => null],
        ];

        foreach ($campos as $campo) {
            Campo::create($campo);
        }

        // Campos hijos (con parent)
        $camposHijos = [
            // Grupo G1 (hijos de '1')
            ['nombre' => '1-1', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 100, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 53.00, 'alias' => null],
            ['nombre' => '1-2', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 101, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 53.00, 'alias' => null],

            // Grupo G2 (hijos de '2')
            ['nombre' => '2-1', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 102, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 113.00, 'alias' => null],
            ['nombre' => '2-2', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 103, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 113.00, 'alias' => null],

            // Grupo G3 (hijos de '3')
            ['nombre' => '3-1', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 104, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 173.00, 'alias' => null],
            ['nombre' => '3-2', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 105, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 173.00, 'alias' => null],
            ['nombre' => '3-3', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 106, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 389.00, 'pos_y' => 173.00, 'alias' => null],
            ['nombre' => '3-4', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 107, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 499.00, 'pos_y' => 173.00, 'alias' => null],

            // Grupo G4 (hijos de '4')
            ['nombre' => '4-1', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 108, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 233.00, 'alias' => null],
            ['nombre' => '4-2', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 109, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 233.00, 'alias' => null],
            ['nombre' => '4-3', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 110, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 389.00, 'pos_y' => 233.00, 'alias' => null],
            ['nombre' => '4-4', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 111, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 500.00, 'pos_y' => 231.00, 'alias' => null],

            // Grupo G8 (hijos de '8')
            ['nombre' => '8-1', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 112, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 293.00, 'alias' => null],
            ['nombre' => '8-2', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 113, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 293.00, 'alias' => null],
            ['nombre' => '8-3', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 114, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 389.00, 'pos_y' => 293.00, 'alias' => null],

            // Grupo G9 (hijos de '9')
            ['nombre' => '9-1', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 115, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 353.00, 'alias' => null],
            ['nombre' => '9-2', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 116, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 353.00, 'alias' => null],
            ['nombre' => '9-3', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 117, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 389.00, 'pos_y' => 353.00, 'alias' => null],

            // Grupo G10 (hijos de '10')
            ['nombre' => '10-1', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 118, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 169.00, 'pos_y' => 413.00, 'alias' => null],
            ['nombre' => '10-2', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 119, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 279.00, 'pos_y' => 413.00, 'alias' => null],
            ['nombre' => '10-3', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 120, 'estado' => null, 'etapa' => null, 'area' => null, 'pos_x' => 389.00, 'pos_y' => 413.00, 'alias' => null],
        ];

        foreach ($camposHijos as $campoHijo) {
            Campo::create($campoHijo);
        }

    }

}
