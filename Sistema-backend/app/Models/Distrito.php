<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model {
    protected $table = 'distritos'; // Conexión con la tabla en español
    protected $fillable = ['nombre'];

    // Relación 3FN: Un distrito tiene muchas zonas
    public function zonas() {
        return $this->hasMany(Zona::class);
    }
}
