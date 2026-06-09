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
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // Ej: "Sopocachi", "Miraflores"
            
            // Relación 3FN: Una zona pertenece a un distrito
            $table->foreignId('distrito_id')
                ->constrained('distritos')
                ->onDelete('restrict'); // Evita borrar un distrito si tiene zonas activas
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
