<?php

namespace Database\Seeders;

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
            ['codigo' => 'A', 'descripcion' => 'Asistido', 'horas_jornal' => 8, 'color' => '#FFFFFF'], // Blanco (presente)
            ['codigo' => 'F', 'descripcion' => 'Falta', 'horas_jornal' => 0, 'color' => '#DA9694'],  // Rojo claro (falta)
            ['codigo' => 'V', 'descripcion' => 'Vacaciones', 'horas_jornal' => 8, 'color' => '#92CDDC'],  // Azul claro (vacaciones)
            ['codigo' => 'LM', 'descripcion' => 'Licencia Maternidad', 'horas_jornal' => 0, 'color' => '#FFC0CB'],  // Rosa claro (licencia maternidad)
            ['codigo' => 'LSG', 'descripcion' => 'Licencia Sin Goce', 'horas_jornal' => 0, 'color' => '#FFFF00'],  // Amarillo (licencia sin goce)
            ['codigo' => 'LCG', 'descripcion' => 'Licencia Con Goce', 'horas_jornal' => 8, 'color' => '#FFFF00'],  // Amarillo (licencia con goce)
            ['codigo' => 'DM', 'descripcion' => 'Descanso Médico', 'horas_jornal' => 8, 'color' => '#FABF8F'],  // Naranja claro (descanso médico)
            ['codigo' => 'AM', 'descripcion' => 'Atención Médica', 'horas_jornal' => 8, 'color' => '#C4D79B'],  // Verde claro (atención médica)
        ];

        foreach ($tipos as $tipo) {
            TipoAsistencia::create($tipo);
        }
    }
}
