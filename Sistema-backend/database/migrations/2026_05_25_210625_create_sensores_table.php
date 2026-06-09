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
        Schema::create('sensores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // Ej: SNS-001, SNS-002
            $table->string('modelo', 50);          // Ej: MPU6050 (Acelerómetro/Giroscopio)
            $table->enum('estado', ['Disponible', 'Instalado', 'Mantenimiento'])->default('Disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
