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
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            
            // Llaves foráneas con eliminación en cascada restringida para proteger la integridad
            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->onDelete('restrict');
                  
            $table->foreignId('personal_taller_id')
                  ->constrained('personal_taller')
                  ->onDelete('restrict');

            // Detalle técnico de la operación realizada
            $table->text('descripcion');
            
            // Tipo para clasificar si fue por Baches (Mecánico) o por Sensores (Electrónico)
            $table->enum('tipo', ['Mecánico', 'Electrónico']);
            
            $table->timestamp('fecha_mantenimiento')->useCurrent();
            $table->timestamps();
            $table->softDeletes(); // Para mantener consistencia con tu papelera centralizada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
