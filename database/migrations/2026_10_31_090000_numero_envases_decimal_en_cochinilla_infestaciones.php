<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El número de envases (lavadores) admite medios: 157.5, 80.5… (así se registra desde 2023). Las migraciones
 * consolidadas lo dejaron como entero y un medio lavador se truncaba. Vuelve a decimal(10,3), como en la base antigua.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE cochinilla_infestaciones MODIFY numero_envases DECIMAL(10,3) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cochinilla_infestaciones MODIFY numero_envases INT NOT NULL');
    }
};
