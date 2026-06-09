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
        Schema::create('baches', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('latitud', 10, 8); // Precisión para GPS
            $table->decimal('longitud', 11, 8);
            $table->enum('severidad', ['Baja', 'Media', 'Alta']);
            $table->enum('estado', ['Pendiente', 'En proceso', 'Reparado'])->default('Pendiente');
            
            // Llaves Foráneas (Relaciones 3FN)
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('restrict');
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('restrict');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potholes');
    }
};