<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 NUEVO: Importación para baja lógica

class Obrero extends Model
{
    use HasFactory, SoftDeletes; // 🔥 NUEVO: Trait activado

    protected $table = 'obreros';

    /**
     * Campos habilitados para asignación masiva.
     */
    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'genero',
        'direccion',
        'especialidad',
        'cuadrilla_id',
    ];

    /**
     * Relación: Un obrero pertenece a una cuadrilla.
     */
    public function cuadrilla()
    {
        return $this->belongsTo(Cuadrilla::class, 'cuadrilla_id');
    }
}