<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obreros', function (Blueprint $table) {
            // Creamos los nuevos campos con nombres completos
            $table->string('nombres')->after('id');
            $table->string('apellido_paterno')->after('nombres');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');
            
            // Eliminamos el campo anterior que era una sola cadena
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('obreros', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->dropColumn(['nombres', 'apellido_paterno', 'apellido_materno']);
        });
    }
};