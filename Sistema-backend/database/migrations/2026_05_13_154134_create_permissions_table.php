<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Tabla de Permisos (Ej: "Ver Mapa", "Borrar Usuarios")
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre legible: "Gestión de Usuarios"
            $table->string('slug')->unique(); // Nombre técnico: "gestion_usuarios"
            $table->timestamps();
        });

        // 2. Tabla Pivot: Une Roles con Permisos (Relación Muchos a Muchos)
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
