<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Instalación nueva: solo lo necesario para entrar y administrar. Los catálogos de la empresa (campos, labores,
 * asistencia, configuración, AFP, maquinarias, grupos, roles y usuarios) se cargan con el Excel de recursos
 * (escritorio de archivos del inicio, Sistema → Configuración, o php artisan libro:excel recursos importar RECURSOS.xlsx --guardar).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermisosSeeder::class);
        $this->call(SuperAdminSeeder::class);
        $this->call(SunatTablasSeeder::class);
        $this->call(AlmacenSeeder::class);
    }
}
