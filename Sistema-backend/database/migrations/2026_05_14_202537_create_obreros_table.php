<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('obreros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ci')->unique();
            $table->enum('genero', ['Masculino', 'Femenino', 'Otro']); // Aquí está tu menú
            $table->string('especialidad');
            $table->unsignedBigInteger('cuadrilla_id')->nullable(); // Puede no tener cuadrilla al inicio
            $table->foreign('cuadrilla_id')->references('id')->on('cuadrillas')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obreros');
    }
};
