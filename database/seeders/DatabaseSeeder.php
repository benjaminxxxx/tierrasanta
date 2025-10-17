<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CamposSeeder::class);
        $this->call(CategoriaPesticidaSeeder::class);
        $this->call(ManoObraSeeder::class);
        $this->call(LaborSeeder::class);
        $this->call(LaborRiegoSeeder::class);
        $this->call(NutrientesSeeder::class);
        $this->call(PlanTipoAsistenciaSeeder::class);
        $this->call(ConfiguracionTableSeeder::class);
        $this->call(DescuentoSpSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(CargosSeeder::class);
        $this->call(GrupoSeeder::class);
        $this->call(GruposCuadrillaSeeder::class);
        $this->call(CochinillaObservacionSeeder::class);
        $this->call(PlanEmpleadosSeeder::class);
    }
}
