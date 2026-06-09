<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    // 🔥 LE DECIMOS A LARAVEL EL NOMBRE EXACTO EN ESPAÑOL DE TU TABLA
    protected $table = 'materiales';

    protected $fillable = [
        'nombre',
        'stock_actual',
        'unidad_medida'
    ];
}