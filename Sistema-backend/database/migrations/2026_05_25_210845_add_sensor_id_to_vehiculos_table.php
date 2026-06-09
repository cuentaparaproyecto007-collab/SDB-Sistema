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
        Schema::table('vehiculos', function (Blueprint $table) {
            // Creamos la llave foránea apuntando a la tabla 'sensores'
            // nullable(): El vehículo puede existir temporalmente sin hardware instalado
            // unique(): Evita que el mismo sensor físico sea asignado a dos carros diferentes
            $table->foreignId('sensor_id')
                  ->nullable()
                  ->unique()
                  ->after('tipo') // Lo posiciona visualmente después del tipo de vehículo
                  ->constrained('sensores')
                  ->onDelete('set null'); // Si se da de baja el sensor, el vehículo no se borra, solo queda sin sensor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            // Eliminamos primero la relación y luego la columna
            $table->dropForeign(['sensor_id']);
            $table->dropColumn('sensor_id');
        });
    }
};
