<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Material; // 🔥 Importamos tu nuevo modelo

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        Material::create([
            'nombre' => 'Asfalto Caliente RC-250 (Alcaldía Central)',
            'stock_actual' => 150.0000, 
            'unidad_medida' => 'm³'
        ]);

        Material::create([
            'nombre' => 'Hormigón Premezclado H-30',
            'stock_actual' => 85.5000, 
            'unidad_medida' => 'm³'
        ]);
    }
}