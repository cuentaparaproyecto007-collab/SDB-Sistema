<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    /**
     * Relación: Un rol tiene muchos usuarios.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * NUEVA RELACIÓN: Un rol tiene muchos permisos.
     * * Usamos 'permisos_roles' como segundo parámetro para que Laravel 
     * busque esa tabla específica y no la que nos daba error en PostgreSQL.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permisos_roles');
    }
}