<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    // Forzamos el nombre de la tabla en español
    protected $table = 'sensores';

    // Campos habilitados para asignación masiva
    protected $fillable = [
        'codigo',
        'modelo',
        'estado'
    ];

    /**
     * Relación 1 a 1: Un sensor físico pertenece a un vehículo activo
     * (El foreign key 'sensor_id' reside en la tabla 'vehiculos')
     */
    public function vehiculo()
    {
        return $this->hasOne(Vehiculo::class, 'sensor_id');
    }
}
