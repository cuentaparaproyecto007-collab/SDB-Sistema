<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: Añade el campo a la tabla existente
     */
    public function up(): void
    {
        Schema::table('personal_taller', function (Blueprint $table) {
            // 🔥 Esto inyecta 'deleted_at' de forma segura al final de la tabla
            $table->softDeletes(); 
        });
    }

    /**
     * Revertir la migración: Elimina el campo si fuera necesario
     */
    public function down(): void
    {
        Schema::table('personal_taller', function (Blueprint $table) {
            // Elimina la columna de borrado lógico
            $table->dropSoftDeletes(); 
        });
    }
};
