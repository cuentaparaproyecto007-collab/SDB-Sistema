<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminamos la regla antigua que bloqueaba el estado 'Asignado'
        DB::statement('ALTER TABLE baches DROP CONSTRAINT IF EXISTS baches_estado_check;');

        // 2. Creamos la nueva regla incluyendo 'Asignado' en la lista oficial
        DB::statement("ALTER TABLE baches ADD CONSTRAINT baches_estado_check CHECK (estado IN ('Pendiente', 'Asignado', 'En proceso', 'Reparado'));");
    }

    public function down(): void
    {
        // Si regresamos la migración, vuelve a la regla original
        DB::statement('ALTER TABLE baches DROP CONSTRAINT IF EXISTS baches_estado_check;');
        DB::statement("ALTER TABLE baches ADD CONSTRAINT baches_estado_check CHECK (estado IN ('Pendiente', 'En proceso', 'Reparado'));");
    }
};
