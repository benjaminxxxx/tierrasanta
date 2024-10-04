<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoAsistencia;

class TipoAsistenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'A', 'descripcion' => 'Asistido', 'horas_jornal' => 8],
            ['codigo' => 'F', 'descripcion' => 'Falta', 'horas_jornal' => 0],
            ['codigo' => 'V', 'descripcion' => 'Vacaciones', 'horas_jornal' => 8],
            ['codigo' => 'LM', 'descripcion' => 'Licencia Maternidad', 'horas_jornal' => 0],
            ['codigo' => 'LSG', 'descripcion' => 'Licencia Sin Goce', 'horas_jornal' => 0],
            ['codigo' => 'LCG', 'descripcion' => 'Licencia Con Goce', 'horas_jornal' => 8],
            ['codigo' => 'DM', 'descripcion' => 'Descanso Médico', 'horas_jornal' => 0],
            ['codigo' => 'AM', 'descripcion' => 'Atención Médica', 'horas_jornal' => 8],
        ];

        foreach ($tipos as $tipo) {
            TipoAsistencia::create($tipo);
        }
    }
}
