<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // Ej: 'Asfalto en Caliente RC-250', 'Hormigón Premezclado'
            $table->decimal('stock_actual', 12, 4); // Cantidad disponible en m³
            $table->string('unidad_medida', 10)->default('m³');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
