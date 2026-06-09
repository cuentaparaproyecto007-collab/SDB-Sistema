<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiamos el tipo de dato de VARCHAR(100) a TEXT en PostgreSQL
        DB::statement('ALTER TABLE auditoria_accesos ALTER COLUMN accion TYPE TEXT;');
    }

    public function down(): void
    {
        // Si se revierte, regresa a un límite amplio de 255 caracteres
        DB::statement('ALTER TABLE auditoria_accesos ALTER COLUMN accion TYPE VARCHAR(255);');
    }
};
