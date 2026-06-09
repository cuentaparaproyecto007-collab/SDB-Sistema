<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCuadrillaMaterialTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuadrilla_material', function (Blueprint $table) {
            $table->id();

            // Llaves foráneas relacionales
            $table->foreignId('cuadrilla_id')->constrained('cuadrillas')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materiales')->onDelete('cascade');

            // Métricas de asfalto en tránsito
            $table->decimal('cantidad_despachada', 8, 2); // Lo que sale en la mañana
            $table->decimal('cantidad_actual', 8, 2);     // Lo que va quedando en el camión

            // Logística de control diario
            $table->date('fecha');
            $table->string('estado')->default('Activo'); // 'Activo' o 'Cerrado'

            // Campos de auditoría, control de tiempo e historial técnico
            $table->timestamps();    // Genera automáticamente created_at y updated_at
            $table->softDeletes();   // Genera automáticamente deleted_at para la eliminación lógica
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_material');
    }
}
