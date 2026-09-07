<?php

namespace Database\Seeders;

use App\Models\Maquinaria;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaquinariasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maquinarias = [
            ['id' => 1, 'nombre' => 'DEUZT', 'alias_blanco' => 'CAMION - D', 'created_at' => '2024-11-28 13:00:37', 'updated_at' => '2024-11-28 16:13:33'],
            ['id' => 2, 'nombre' => 'MASSEY CHICO', 'alias_blanco' => 'CAMION - MCH', 'created_at' => '2024-11-28 13:02:15', 'updated_at' => '2024-11-28 16:17:05'],
            ['id' => 3, 'nombre' => 'MASSEY GRANDE', 'alias_blanco' => 'CAMION - MG', 'created_at' => '2024-11-28 16:17:49', 'updated_at' => '2024-11-28 16:17:49'],
            ['id' => 4, 'nombre' => 'CAMION', 'alias_blanco' => 'CAMION', 'created_at' => '2024-11-28 16:19:06', 'updated_at' => '2024-11-28 16:19:06'],
            ['id' => 5, 'nombre' => 'MOTOR', 'alias_blanco' => 'CAMION - M', 'created_at' => '2024-11-28 16:31:53', 'updated_at' => '2024-11-28 16:31:53'],
            ['id' => 6, 'nombre' => 'CGL-125 A', 'alias_blanco' => 'CGL-125 A', 'created_at' => '2024-12-01 02:04:23', 'updated_at' => '2024-12-01 02:04:23'],
            ['id' => 7, 'nombre' => 'XR-125', 'alias_blanco' => 'XR-125', 'created_at' => '2024-12-01 02:04:37', 'updated_at' => '2024-12-01 02:04:37'],
            ['id' => 8, 'nombre' => 'CGL-125 R', 'alias_blanco' => 'CGL-125 R', 'created_at' => '2024-12-01 02:04:47', 'updated_at' => '2024-12-01 02:04:47'],
            ['id' => 9, 'nombre' => 'ANDRES', 'alias_blanco' => 'ANDRES', 'created_at' => '2026-01-07 20:56:09', 'updated_at' => '2026-01-07 20:56:09'],
            ['id' => 10, 'nombre' => 'ELVER', 'alias_blanco' => 'ELVER', 'created_at' => '2026-01-07 20:56:23', 'updated_at' => '2026-01-07 20:56:23'],
            ['id' => 11, 'nombre' => 'CGL-125A', 'alias_blanco' => 'CGL-125A', 'created_at' => '2026-01-07 20:56:31', 'updated_at' => '2026-01-07 20:56:31'],
            ['id' => 12, 'nombre' => 'CGL-125R', 'alias_blanco' => 'CGL-125R', 'created_at' => '2026-01-07 20:56:44', 'updated_at' => '2026-01-07 20:56:44'],
            ['id' => 13, 'nombre' => 'MOTO CHICA', 'alias_blanco' => 'MOTO CHICA', 'created_at' => '2026-01-07 20:57:01', 'updated_at' => '2026-01-07 20:57:01'],
            ['id' => 14, 'nombre' => 'DONAL', 'alias_blanco' => 'DONAL', 'created_at' => '2026-01-07 20:57:14', 'updated_at' => '2026-01-07 20:57:14'],
            ['id' => 15, 'nombre' => 'MAQUINA FUMIGADORA', 'alias_blanco' => 'MAQUINA FUMIGADORA', 'created_at' => '2026-01-07 20:57:26', 'updated_at' => '2026-01-07 20:57:26'],
            ['id' => 16, 'nombre' => 'PODADORA PASTO', 'alias_blanco' => 'PODADORA PASTO', 'created_at' => '2026-01-07 20:58:21', 'updated_at' => '2026-01-07 20:58:21'],
            ['id' => 17, 'nombre' => 'FUMIGADORA', 'alias_blanco' => 'FUMIGADORA', 'created_at' => '2026-01-07 20:59:05', 'updated_at' => '2026-01-07 20:59:05'],
            ['id' => 18, 'nombre' => 'MANTENIMIENTO - MAQUINARIA', 'alias_blanco' => 'MANTENIMIENTO - MAQUINARIA', 'created_at' => '2026-01-07 20:59:19', 'updated_at' => '2026-01-07 20:59:19'],
            ['id' => 19, 'nombre' => 'PINTADO ESTRUCTURAS', 'alias_blanco' => 'PINTADO ESTRUCTURAS', 'created_at' => '2026-01-07 20:59:31', 'updated_at' => '2026-01-07 20:59:31'],
            ['id' => 20, 'nombre' => 'FDM', 'alias_blanco' => 'FDM', 'created_at' => '2026-01-07 21:00:13', 'updated_at' => '2026-01-07 21:00:13'],
            ['id' => 21, 'nombre' => 'MOTOCULTOR', 'alias_blanco' => 'MOTOCULTOR', 'created_at' => '2026-01-07 21:00:20', 'updated_at' => '2026-01-07 21:00:20'],
            ['id' => 22, 'nombre' => 'TROMPO', 'alias_blanco' => 'TROMPO', 'created_at' => '2026-01-07 21:00:29', 'updated_at' => '2026-01-07 21:00:29'],
            ['id' => 23, 'nombre' => 'CLAVER', 'alias_blanco' => 'CLAVER', 'created_at' => '2026-01-07 21:00:37', 'updated_at' => '2026-01-07 21:00:37'],
            ['id' => 24, 'nombre' => 'MOTO NUEVA', 'alias_blanco' => 'MOTO NUEVA', 'created_at' => '2026-01-07 21:00:47', 'updated_at' => '2026-01-07 21:00:47'],
            ['id' => 25, 'nombre' => 'COMPRESORA', 'alias_blanco' => 'COMPRESORA', 'created_at' => '2026-01-07 21:01:03', 'updated_at' => '2026-01-07 21:01:03'],
            ['id' => 26, 'nombre' => 'XR-200', 'alias_blanco' => 'XR-200', 'created_at' => '2026-01-07 21:01:10', 'updated_at' => '2026-01-07 21:01:10'],
            ['id' => 27, 'nombre' => 'MOTICULTOR', 'alias_blanco' => 'MOTICULTOR', 'created_at' => '2026-01-07 21:01:17', 'updated_at' => '2026-01-07 21:01:17'],
            ['id' => 28, 'nombre' => 'IGNACIO', 'alias_blanco' => 'IGNACIO', 'created_at' => '2026-01-07 21:01:25', 'updated_at' => '2026-01-07 21:01:25'],
            ['id' => 29, 'nombre' => 'DEUTZ', 'alias_blanco' => 'DEUTZ', 'created_at' => '2026-01-08 15:24:28', 'updated_at' => '2026-01-08 15:24:28'],
            ['id' => 30, 'nombre' => 'OMNIBUS', 'alias_blanco' => 'OMNIBUS', 'created_at' => '2026-01-08 15:24:37', 'updated_at' => '2026-01-08 15:24:37'],
            ['id' => 31, 'nombre' => 'MANTENIMIENTO', 'alias_blanco' => 'MANTENIMIENTO', 'created_at' => '2026-01-08 15:24:44', 'updated_at' => '2026-01-08 15:24:44'],
        ];

        foreach ($maquinarias as $maquinaria) {
            Maquinaria::create($maquinaria);
        }
    }
}
