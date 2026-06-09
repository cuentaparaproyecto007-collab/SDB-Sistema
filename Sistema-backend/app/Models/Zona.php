<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model {
    protected $table = 'zonas';
    protected $fillable = ['nombre', 'distrito_id'];

    // Relación 3FN: Una zona pertenece a un distrito
    public function distrito() {
        return $this->belongsTo(Distrito::class);
    }
}
