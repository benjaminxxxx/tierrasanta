<?php

namespace Database\Seeders;

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
        DB::table('maquinarias')->insert([
            [
                'id' => 1,
                'nombre' => 'DEUZT',
                'alias_blanco' => 'CAMION - D',
                'created_at' => '2024-11-28 08:00:37',
                'updated_at' => '2024-11-28 11:13:33',
            ],
            [
                'id' => 2,
                'nombre' => 'MASSEY CHICO',
                'alias_blanco' => 'CAMION - MCH',
                'created_at' => '2024-11-28 08:02:15',
                'updated_at' => '2024-11-28 11:17:05',
            ],
            [
                'id' => 3,
                'nombre' => 'MASSEY GRANDE',
                'alias_blanco' => 'CAMION - MG',
                'created_at' => '2024-11-28 11:17:49',
                'updated_at' => '2024-11-28 11:17:49',
            ],
            [
                'id' => 4,
                'nombre' => 'CAMION',
                'alias_blanco' => 'CAMION',
                'created_at' => '2024-11-28 11:19:06',
                'updated_at' => '2024-11-28 11:19:06',
            ],
            [
                'id' => 5,
                'nombre' => 'MOTOR',
                'alias_blanco' => 'CAMION - M',
                'created_at' => '2024-11-28 11:31:53',
                'updated_at' => '2024-11-28 11:31:53',
            ],
            [
                'id' => 6,
                'nombre' => 'CGL-125 A',
                'alias_blanco' => 'CGL-125 A',
                'created_at' => '2024-11-30 21:04:23',
                'updated_at' => '2024-11-30 21:04:23',
            ],
            [
                'id' => 7,
                'nombre' => 'XR-125',
                'alias_blanco' => 'XR-125',
                'created_at' => '2024-11-30 21:04:37',
                'updated_at' => '2024-11-30 21:04:37',
            ],
            [
                'id' => 8,
                'nombre' => 'CGL-125 R',
                'alias_blanco' => 'CGL-125 R',
                'created_at' => '2024-11-30 21:04:47',
                'updated_at' => '2024-11-30 21:04:47',
            ],
        ]);
    }
}
