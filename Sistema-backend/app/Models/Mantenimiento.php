<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mantenimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'vehiculo_id',
        'personal_taller_id',
        'descripcion',
        'tipo',
        'fecha_mantenimiento'
    ];

    /**
     * Relación: Un mantenimiento pertenece a un vehículo específico
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    /**
     * Relación: Un mantenimiento es ejecutado por un técnico del taller
     */
    public function tecnico()
    {
        return $this->belongsTo(PersonalTaller::class, 'personal_taller_id');
    }
}
