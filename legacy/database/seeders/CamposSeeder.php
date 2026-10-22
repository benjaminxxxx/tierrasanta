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
        $campos = [
            ['nombre' => '1', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 1, 'etapa' => null, 'area' => 3.1340, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '1-1', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 100, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '1-2', 'campo_parent_nombre' => '1', 'grupo' => 'G1', 'orden' => 101, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '10', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 10, 'etapa' => null, 'area' => 3.9820, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '10-1', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 118, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '10-2', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 119, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '10-3', 'campo_parent_nombre' => '10', 'grupo' => 'G10', 'orden' => 120, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '11', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 11, 'etapa' => null, 'area' => 2.7880, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '12', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 12, 'etapa' => null, 'area' => 2.8900, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '13', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 13, 'etapa' => null, 'area' => 2.2980, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '14', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 14, 'etapa' => null, 'area' => 2.3670, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '15', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 15, 'etapa' => null, 'area' => 2.5180, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '16', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 15, 'etapa' => null, 'area' => 2.2710, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '17', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 17, 'etapa' => null, 'area' => 2.1180, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '18', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 18, 'etapa' => null, 'area' => 1.9070, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '19', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 19, 'etapa' => null, 'area' => 2.0770, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '2', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 2, 'etapa' => null, 'area' => 2.3210, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '2-1', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 102, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '2-2', 'campo_parent_nombre' => '2', 'grupo' => 'G2', 'orden' => 103, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '20', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 20, 'etapa' => null, 'area' => 2.1610, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '21', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 21, 'etapa' => null, 'area' => 1.9330, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '22', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO2', 'orden' => 22, 'etapa' => null, 'area' => 1.8650, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '3', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 3, 'etapa' => null, 'area' => 4.2010, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '3-1', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 104, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '3-2', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 105, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '3-3', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 106, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '3-4', 'campo_parent_nombre' => '3', 'grupo' => 'G3', 'orden' => 107, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '4', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 4, 'etapa' => null, 'area' => 4.1900, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '4-1', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 108, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '4-2', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 109, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '4-3', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 110, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '4-4', 'campo_parent_nombre' => '4', 'grupo' => 'G4', 'orden' => 111, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '8', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 8, 'etapa' => null, 'area' => 3.2580, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '8-1', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 112, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '8-2', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 113, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '8-3', 'campo_parent_nombre' => '8', 'grupo' => 'G8', 'orden' => 114, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '9', 'campo_parent_nombre' => null, 'grupo' => 'NUMERICO', 'orden' => 9, 'etapa' => null, 'area' => 4.3360, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '9-1', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 115, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '9-2', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 116, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => '9-3', 'campo_parent_nombre' => '9', 'grupo' => 'G9', 'orden' => 117, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A1', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 31, 'etapa' => null, 'area' => 3.3920, 'alias' => 'a-1', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A10', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 40, 'etapa' => null, 'area' => 2.9790, 'alias' => 'a-10,a.10,a 10', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A11', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 41, 'etapa' => null, 'area' => 3.4560, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A12', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 3.4707, 'alias' => null, 'created_at' => '2026-01-16 08:51:44', 'updated_at' => '2026-01-16 08:51:44'],
            ['nombre' => 'A13', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.7077, 'alias' => null, 'created_at' => '2026-01-16 08:52:12', 'updated_at' => '2026-01-16 08:52:12'],
            ['nombre' => 'A14', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.7077, 'alias' => null, 'created_at' => '2026-02-17 18:54:49', 'updated_at' => '2026-02-17 18:54:49'],
            ['nombre' => 'A15', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.8280, 'alias' => null, 'created_at' => '2026-06-15 15:29:23', 'updated_at' => '2026-06-15 15:29:23'],
            ['nombre' => 'A16', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.3985, 'alias' => null, 'created_at' => '2026-06-15 15:29:59', 'updated_at' => '2026-06-15 15:29:59'],
            ['nombre' => 'A2', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 32, 'etapa' => null, 'area' => 1.9800, 'alias' => 'a-2', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A3', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 33, 'etapa' => null, 'area' => 2.8170, 'alias' => 'a.3,a-3', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A4', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 34, 'etapa' => null, 'area' => 2.7980, 'alias' => 'a-4', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A5', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 35, 'etapa' => null, 'area' => 3.0660, 'alias' => 'a-5', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A6', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 36, 'etapa' => null, 'area' => 3.1970, 'alias' => 'a-6', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A7', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 37, 'etapa' => null, 'area' => 3.2280, 'alias' => 'a-7', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A8', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 38, 'etapa' => null, 'area' => 3.0330, 'alias' => 'a-8', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'A9', 'campo_parent_nombre' => null, 'grupo' => 'A', 'orden' => 39, 'etapa' => null, 'area' => 2.9590, 'alias' => 'a-9', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B1', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 51, 'etapa' => null, 'area' => 2.7640, 'alias' => 'b-1', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B10', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 60, 'etapa' => null, 'area' => 2.4310, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B11', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 61, 'etapa' => null, 'area' => 3.9680, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B12', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.6484, 'alias' => null, 'created_at' => '2026-01-16 08:52:42', 'updated_at' => '2026-01-16 08:52:42'],
            ['nombre' => 'B13', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.7030, 'alias' => null, 'created_at' => '2026-01-16 08:52:51', 'updated_at' => '2026-01-16 08:52:51'],
            ['nombre' => 'B14', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.7030, 'alias' => null, 'created_at' => '2026-02-17 18:55:08', 'updated_at' => '2026-02-17 18:55:08'],
            ['nombre' => 'B15', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.5981, 'alias' => null, 'created_at' => '2026-06-15 15:29:43', 'updated_at' => '2026-06-15 15:29:43'],
            ['nombre' => 'B16', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.4220, 'alias' => null, 'created_at' => '2026-06-15 15:30:17', 'updated_at' => '2026-06-15 15:30:17'],
            ['nombre' => 'B2', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 52, 'etapa' => null, 'area' => 2.3940, 'alias' => 'b-2', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B3', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 53, 'etapa' => null, 'area' => 3.0560, 'alias' => 'b-3', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B4', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 54, 'etapa' => null, 'area' => 2.5010, 'alias' => 'b-4', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B5', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 55, 'etapa' => null, 'area' => 2.7140, 'alias' => 'b-5', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B6', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 56, 'etapa' => null, 'area' => 3.0630, 'alias' => 'b-6', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B7', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 57, 'etapa' => null, 'area' => 3.1670, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B8', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 58, 'etapa' => null, 'area' => 3.0420, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'B9', 'campo_parent_nombre' => null, 'grupo' => 'B', 'orden' => 59, 'etapa' => null, 'area' => 3.5230, 'alias' => 'b-9', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'C1', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 67, 'etapa' => null, 'area' => 1.9530, 'alias' => 'c-1', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'C2', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 68, 'etapa' => null, 'area' => 2.9980, 'alias' => 'c-2', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'C3', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 69, 'etapa' => null, 'area' => 3.1620, 'alias' => 'c-3', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'C4', 'campo_parent_nombre' => null, 'grupo' => 'C', 'orden' => 70, 'etapa' => null, 'area' => 3.3220, 'alias' => 'c-4', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'CARMEN', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'etapa' => null, 'area' => 3.0560, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'CE', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'etapa' => null, 'area' => 2.3830, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'D', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 76, 'etapa' => null, 'area' => 1.5000, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'D1', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 74, 'etapa' => null, 'area' => 1.6000, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'D2', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 75, 'etapa' => null, 'area' => 1.0000, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'D3', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 71, 'etapa' => null, 'area' => 3.4390, 'alias' => 'd-3', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'D4', 'campo_parent_nombre' => null, 'grupo' => 'D', 'orden' => 72, 'etapa' => null, 'area' => 3.1290, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'E1', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 4.8100, 'alias' => null, 'created_at' => '2026-01-16 08:54:55', 'updated_at' => '2026-01-16 08:54:55'],
            ['nombre' => 'E2', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 4.7406, 'alias' => null, 'created_at' => '2026-01-16 08:55:05', 'updated_at' => '2026-01-16 08:55:05'],
            ['nombre' => 'E3', 'campo_parent_nombre' => null, 'grupo' => 'F', 'orden' => 78, 'etapa' => null, 'area' => null, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'E4', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 3.0063, 'alias' => null, 'created_at' => '2026-01-16 08:55:16', 'updated_at' => '2026-01-16 08:55:16'],
            ['nombre' => 'E7', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 1.8300, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'F1', 'campo_parent_nombre' => null, 'grupo' => 'F', 'orden' => 73, 'etapa' => null, 'area' => 4.1230, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'F2', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 1.7300, 'alias' => null, 'created_at' => '2026-01-16 08:55:44', 'updated_at' => '2026-01-16 08:55:44'],
            ['nombre' => 'F3', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 0.6000, 'alias' => null, 'created_at' => '2026-01-16 08:55:53', 'updated_at' => '2026-01-16 08:55:53'],
            ['nombre' => 'F7', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 2.3700, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'FDM', 'campo_parent_nombre' => null, 'grupo' => 'FDM', 'orden' => 77, 'etapa' => null, 'area' => 1.0000, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'I-3', 'campo_parent_nombre' => null, 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 0.0000, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'JUANC', 'campo_parent_nombre' => null, 'grupo' => 'DESUSO', 'orden' => null, 'etapa' => null, 'area' => 2.3940, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'Limonero3', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 64, 'etapa' => null, 'area' => 1.9500, 'alias' => 'l-3,l3', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-01-24 00:17:02'],
            ['nombre' => 'Limonero3B', 'campo_parent_nombre' => 'Limonero3', 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 0.6986, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'Limonero4', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 65, 'etapa' => null, 'area' => 1.1730, 'alias' => 'l-4,l4', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-03-19 07:33:48'],
            ['nombre' => 'Limonero4A', 'campo_parent_nombre' => 'Limonero4', 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 1.1734, 'alias' => null, 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2025-10-17 14:24:39'],
            ['nombre' => 'Limonero5', 'campo_parent_nombre' => null, 'grupo' => 'L', 'orden' => 66, 'etapa' => null, 'area' => 0.9770, 'alias' => 'l-5,l5,limonero05', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-04-10 16:37:53'],
            ['nombre' => 'Limonero5A', 'campo_parent_nombre' => 'Limonero5', 'grupo' => null, 'orden' => null, 'etapa' => null, 'area' => 1.5000, 'alias' => 'limonero 5.a,limonero 5a', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-01-16 08:54:11'],
            ['nombre' => 'Naranjos', 'campo_parent_nombre' => null, 'grupo' => 'NARANJOS', 'orden' => 62, 'etapa' => null, 'area' => 2.7660, 'alias' => 'Naranjas, Naranja', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-03-19 08:45:58'],
            ['nombre' => 'NaranjosB', 'campo_parent_nombre' => null, 'grupo' => 'NARANJOS', 'orden' => 63, 'etapa' => null, 'area' => 2.3830, 'alias' => 'n-b,n_b,nb,naranjos b,naranjob', 'created_at' => '2025-10-17 14:24:39', 'updated_at' => '2026-03-19 07:35:02'],
        ];

        foreach ($campos as $campo) {
            Campo::create($campo);
        }

    }

}
