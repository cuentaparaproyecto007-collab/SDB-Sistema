<?php

namespace Database\Seeders;

use App\Models\Role; // Asegúrate de tener el modelo Role creado
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrador
        Role::updateOrCreate(
            ['nombre' => 'Administrador'], // Condición de búsqueda
            ['descripcion' => 'Control total del sistema, gestión de usuarios y reportes financieros.'] // Campos a registrar/actualizar
        );

        // 2. Técnico de Campo
        Role::updateOrCreate(
            ['nombre' => 'Técnico'],
            ['descripcion' => 'Operador de vehículos y responsable de la verificación física de baches.']
        );

        // 3. Jefe de Cuadrilla
        Role::updateOrCreate(
            ['nombre' => 'Jefe de Cuadrilla'],
            ['descripcion' => 'Responsable de la ejecución de reparaciones y reporte de materiales.']
        );

        // 4. Analista Vial
        Role::updateOrCreate(
            ['nombre' => 'Analista Vial'],
            ['descripcion' => 'Visualización de estadísticas y mapa de calor para planificación urbana.']
        );

        // 🔥 5. Técnico de Flota e IoT (NUEVO - Se agregará de forma segura)
        Role::updateOrCreate(
            ['nombre' => 'Técnico de Flota e IoT'],
            ['descripcion' => 'Monitoreo de telemetría de suspensión, gestión de hardware de sensores MPU6050 y control de mantenimiento.']
        );

        // 🔥 6. Encargado de almacén (NUEVO - Se agregará de forma segura)
        Role::updateOrCreate(
            ['nombre' => 'Encargado de almacén'],
            ['descripcion' => 'Control de inventario de materiales de bacheo, stock de asfalto e insumos logísticos de campo.']
        );
    }
}
