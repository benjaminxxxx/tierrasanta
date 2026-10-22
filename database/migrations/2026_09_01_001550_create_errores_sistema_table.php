<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('errores_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('huella', 32)->comment('md5 de clase + archivo + línea: agrupa el mismo error');
            $table->string('clase');
            $table->text('mensaje');
            $table->string('archivo')->nullable();
            $table->unsignedInteger('linea')->nullable();
            $table->longText('traza')->nullable();
            $table->string('url', 2048)->nullable()->comment('Pantalla donde ocurrió');
            $table->string('ruta')->nullable()->comment('Nombre de la ruta de esa pantalla');
            $table->string('metodo_http', 10)->nullable();
            $table->string('componente')->nullable()->comment('Componente Livewire');
            $table->string('accion')->nullable()->comment('Método del componente o de la clase donde se originó');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('navegador', 512)->nullable();
            $table->string('estado', 20)->default('pendiente')->comment('pendiente | revisado | resuelto');
            $table->text('nota')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('huella');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('errores_sistema');
    }
};
