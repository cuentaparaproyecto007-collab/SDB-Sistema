<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Importante para la tarea

class Service extends Model
{
    use SoftDeletes; // <-- Esto activa el uso de 'deleted_at' automáticamente

    protected $fillable = [
        'user_id',
        'foto_persona',
    ];
}