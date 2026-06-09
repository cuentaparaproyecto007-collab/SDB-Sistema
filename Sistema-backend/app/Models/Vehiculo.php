<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model {
    
    protected $table = 'vehiculos';
    
    protected $fillable = [
        'placa', 
        'marca', 
        'modelo', 
        'tipo', 
        'sensor_id'
    ];

    /**
     * Relación Inversa 1 a 1
     * Un vehículo pertenece o tiene asignado un sensor físico.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }

    /**
     * 🔥 NUEVO: Relación 1 a Muchos
     * Un vehículo patrulla puede registrar múltiples baches a lo largo del tiempo.
     */
    public function baches()
    {
        return $this->hasMany(Bache::class, 'vehiculo_id');
    }

    /**
     * Relación: Un vehículo puede registrar un historial de muchos mantenimientos
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'vehiculo_id');
    }
}