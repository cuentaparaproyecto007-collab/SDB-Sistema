<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Forzamos la creación de la columna saltándonos los bloqueos de Laravel
        DB::statement('ALTER TABLE baches ADD COLUMN IF NOT EXISTS cuadrilla_id INTEGER NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE baches DROP COLUMN IF EXISTS cuadrilla_id;');
    }
};
