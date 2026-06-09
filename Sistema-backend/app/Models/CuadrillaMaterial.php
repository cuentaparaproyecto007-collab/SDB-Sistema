<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // borrado lógico

class CuadrillaMaterial extends Model
{
    use HasFactory, SoftDeletes;

    // Forzamos el nombre exacto de la tabla de tu pgAdmin
    protected $table = 'cuadrilla_material';

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'cuadrilla_id',
        'material_id',
        'cantidad_despachada',
        'cantidad_actual',
        'fecha',
        'estado',
    ];

    /**
     * Conversión de tipos de atributos (Casts).
     */
    protected $casts = [
        'cuadrilla_id' => 'integer',
        'material_id' => 'integer',
        'cantidad_despachada' => 'float',
        'cantidad_actual' => 'float',
        'fecha' => 'date',
    ];

    /**
     * RELACIONES RELACIONALES
     */

    // Una asignación pertenece a una Cuadrilla
    public function cuadrilla()
    {
        return $this->belongsTo(Cuadrilla::class, 'cuadrilla_id');
    }

    // Una asignación pertenece a un tipo de Material (Asfalto)
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
