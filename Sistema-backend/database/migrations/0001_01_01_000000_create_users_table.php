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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // --- NUEVOS CAMPOS DE IDENTIDAD (REQUERIMIENTO TUTOR) ---
            $table->string('nombres'); 
            $table->string('apellido_paterno');
            $table->string('apellido_materno');
            $table->string('ci')->unique(); // Carnet de Identidad único
            $table->string('direccion')->nullable(); // Dirección de domicilio
            $table->string('celular')->nullable();
            
            // Mantenemos 'name' para almacenar el nombre completo concatenado
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // --- CAMPOS DE INGENIERÍA Y 3FN ---
            // 1. Relación con Roles
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('restrict');

            // 2. Seguridad 2FA
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // 3. Biometría (Base64)
            $table->longText('face_photo')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};