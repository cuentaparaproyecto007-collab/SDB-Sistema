<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baches', function (Blueprint $table) {
            // Campos de dimensionamiento geométrico (Todos con nullable para no romper el IoT)
            $table->decimal('eje_x', 8, 3)->nullable(); // Medido en metros o cm en front, estandarizado
            $table->decimal('eje_y', 8, 3)->nullable(); 
            $table->decimal('profundidad_cm', 6, 2)->nullable(); // 🔥 Fijo en centímetros como pediste
            $table->decimal('volumen_m3', 10, 4)->nullable(); // Resultado final calculado en metros cúbicos
            
            // Relación de Llave Foránea con la nueva tabla de materiales
            $table->foreignId('material_id')->nullable()->constrained('materiales')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('baches', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->dropColumn(['eje_x', 'eje_y', 'profundidad_cm', 'volumen_m3', 'material_id']);
        });
    }
};
