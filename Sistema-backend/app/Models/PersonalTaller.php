<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 Importante para la papelera

class PersonalTaller extends Model
{
    use HasFactory, SoftDeletes;

    // Indicamos explícitamente el nombre de la tabla en PostgreSQL
    protected $table = 'personal_taller';

    // 🔥 Autorizamos los campos para que Laravel permita guardarlos desde el formulario
    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'genero',
        'celular',
        'direccion',
        'rubro', // 'Mecánica' o 'Electrónica'
        'estado' // Iniciará como 'Disponible' por defecto
    ];

    // Le indicamos a Laravel que maneje 'deleted_at' como un formato de fecha
    protected $dates = ['deleted_at'];

    /**
     * 🔥 RELACIÓN: Un técnico de soporte puede tener asignados muchos mantenimientos en la flota
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'personal_taller_id');
    }
}
