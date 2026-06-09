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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 15)->unique(); // Unicidad garantizada para auditoría
            $table->string('marca', 50);
            $table->string('modelo', 50);
            
            // Mantenemos tu lógica de selección para categorizar la unidad sensorizada
            $table->enum('tipo', ['Camioneta', 'Sedán', 'Compacto', 'Bus', 'Otro']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
