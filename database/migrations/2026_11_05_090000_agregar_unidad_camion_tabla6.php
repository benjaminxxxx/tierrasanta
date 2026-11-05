<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unidad "Camión" (camionada) para productos como el estiércol: se compra y se usa por camión (3.5 = tres camiones y
 * medio). No es de la tabla 6 de SUNAT: en el kardex sale como "99 - CAMIÓN" (otros, especificar).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sunat_tabla6_codigo_unidad_medida')->insertOrIgnore(['codigo' => 'CAM', 'descripcion' => 'CAMIÓN', 'alias' => 'CAMIÓN']);
    }

    public function down(): void
    {
        if (!DB::table('productos')->where('codigo_unidad_medida', 'CAM')->exists()) {
            DB::table('sunat_tabla6_codigo_unidad_medida')->where('codigo', 'CAM')->delete();
        }
    }
};
