<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 30)->unique();

            $table->enum('tipo', ['individual', 'empresa'])->default('individual');

            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_documento', 30)->nullable();

            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();

            $table->string('razon_social')->nullable(); // para tipo = 'empresa'

            $table->string('nombre_mostrar');
            $table->string('nombre_legal')->nullable();

            $table->date('fecha_nacimiento')->nullable();

            $table->enum('genero', ['masculino', 'femenino', 'otro'])->nullable();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo'])->nullable();

            $table->string('telefono_movil', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();

            $table->string('pais', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('distrito', 100)->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->text('direccion')->nullable();

            $table->text('notas')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo_documento', 'numero_documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
