<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Almacén principal: compras y salidas lo necesitan desde el primer día (AlmacenPrincipalServicio). */
class AlmacenSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('almacenes')->where('es_principal', true)->exists()) {
            return;
        }
        DB::table('almacenes')->updateOrInsert(['codigo' => 'ALM-001'], [
            'nombre' => 'Almacén Principal', 'direccion' => null, 'es_principal' => true, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
