<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 🔥 UN SOLO BLOQUE: Ejecuta secuencialmente cada tarea una sola vez
        $this->call([
            RoleSeeder::class,     // 1. Crea los roles operativos
            UserSeeder::class,     // 2. Crea tu cuenta de administrador
            MaterialSeeder::class, // 3. Abastece el almacén de asfalto y hormigón
        ]);
    }
}