<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buscamos el rol de administrador creado previamente en RoleSeeder
        $adminRole = Role::where('nombre', 'Administrador')->first();

        User::create([
            'nombres'          => 'Jonathan',
            'apellido_paterno' => 'Callisaya',
            'apellido_materno' => 'Choque',
            'ci'               => '1234567', // Tu CI o uno de prueba (debe ser único)
            'name'             => 'Jonathan Callisaya Choque', // Nombre concatenado como pide tu tutor
            'email'            => 'cuentaparaproyecto007@gmail.com',
            'password'         => Hash::make('007Jonathan_Callisaya'),
            'role_id'          => $adminRole ? $adminRole->id : 1,
            'direccion'        => 'La Paz, Bolivia',
            'celular'          => '70000000',
            'face_photo'       => null,
        ]);
    }
}