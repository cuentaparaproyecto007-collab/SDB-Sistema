<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuadrillas', function (Blueprint $table) {
            $table->softDeletes(); // Inyecta la columna 'deleted_at' nullable
        });
    }

    public function down(): void
    {
        Schema::table('cuadrillas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
