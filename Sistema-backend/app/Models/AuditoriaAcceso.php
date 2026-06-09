<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAcceso extends Model {
    protected $table = 'auditoria_accesos';
    protected $fillable = ['user_id', 'accion', 'ip_origen', 'dispositivo_info'];
}
