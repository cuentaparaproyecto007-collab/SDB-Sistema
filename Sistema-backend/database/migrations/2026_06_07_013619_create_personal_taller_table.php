<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_taller', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 75);
            $table->string('apellido_materno', 75)->nullable();
            $table->string('ci', 25)->unique(); 
            $table->enum('genero', ['Masculino', 'Femenino', 'Otro']);
            $table->string('celular', 20);
            
            // 🏠 🔥 Campo de Dirección Añadido
            $table->string('direccion', 255);
            
            // Especialidades del taller
            $table->enum('rubro', ['Mecánica', 'Electrónica']);
            
            // Estado operativo interno del taller
            $table->string('estado', 30)->default('Disponible'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_taller');
    }
};
