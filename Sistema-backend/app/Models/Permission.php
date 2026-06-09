<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    // Se añade 'description' a la lista
    protected $fillable = ['nombre', 'slug'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permisos_roles');
    }
}