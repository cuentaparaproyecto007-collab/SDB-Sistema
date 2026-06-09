<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 NUEVO: Importación para baja lógica

class Cuadrilla extends Model
{
    use HasFactory, SoftDeletes; // 🔥 NUEVO: Inyección del trait SoftDeletes

    // Definimos explícitamente la tabla
    protected $table = 'cuadrillas';

    // Campos habilitados para asignación masiva
    protected $fillable = [
        'nombre',
        'jefe_id',
    ];

    /**
     * Relación: Una cuadrilla tiene un Jefe de Cuadrilla.
     * Vincula con la tabla 'users', ya que el jefe sí tiene acceso al sistema.
     */
    public function jefe()
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    /**
     * Relación: Una cuadrilla está compuesta por muchos obreros.
     * El personal de campo que el Técnico registrará.
     */
    public function obreros()
    {
        return $this->hasMany(Obrero::class, 'cuadrilla_id');
    }

    /**
     * Relación: Una cuadrilla tiene baches asignados para su reparación.
     */
    public function baches()
    {
        return $this->hasMany(Bache::class, 'cuadrilla_id');
    }

    /**
     * SCOPE DE INGENIERÍA: Balanceo de Carga.
     * Este método permitirá que el sistema le sugiera al Técnico 
     * qué cuadrilla tiene menos personal en ese momento.
     */
    public function scopeConMenosPersonal($query)
    {
        return $query->withCount('obreros')->orderBy('obreros_count', 'asc');
    }
}