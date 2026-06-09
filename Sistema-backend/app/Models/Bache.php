<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bache extends Model {
    protected $table = 'baches';

    // 🔥 MODIFICADO: Añadimos los campos geométricos y logísticos al fillable
    protected $fillable = [
        'descripcion', 
        'latitud', 
        'longitud', 
        'severidad', 
        'estado', 
        'vehiculo_id', 
        'zona_id',
        'user_id',
        'cuadrilla_id',
        'eje_x',          // 📐 Medida horizontal (m)
        'eje_y',          // 📐 Medida vertical (m)
        'profundidad_cm', // 📐 Profundidad obligatoria fija en centímetros (cm)
        'volumen_m3',     // 📐 Resultado del cálculo cúbico final (m³)
        'material_id'     // 📦 Llave foránea amarrada al almacén de materiales
    ];

    // Relación: El bache pertenece a una zona geográfica
    public function zona() {
        return $this->belongsTo(Zona::class);
    }

    // Relación: Identifica qué unidad móvil realizó la detección
    public function vehiculo() {
        return $this->belongsTo(Vehiculo::class);
    }

    // Relación: Conecta el bache con el usuario (Técnico/Admin) que lo registró
    public function usuario() {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Identifica qué equipo de trabajo (Cuadrilla) 
     * tiene asignada la reparación de este bache.
     */
    public function cuadrilla() {
        return $this->belongsTo(Cuadrilla::class, 'cuadrilla_id');
    }

    /**
     * 🔥 NUEVA RELACIÓN LOGÍSTICA: Conecta el bache con el tipo de asfalto 
     * u hormigón consumido desde el almacén de la alcaldía al ser reparado.
     */
    public function material() {
        return $this->belongsTo(Material::class, 'material_id');
    }
}