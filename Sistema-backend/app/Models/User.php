<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 NUEVO: Importación para eliminación lógica

class User extends Authenticatable
{
    // 🔥 NUEVO: Añadimos SoftDeletes a los traits del modelo
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes; 

    /**
     * Atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',               
        'nombres',            
        'apellido_paterno',   
        'apellido_materno',   
        'ci',                 
        'direccion',          
        'celular',
        'email',
        'password',
        'otp_code',        
        'otp_expires_at',  
        'role_id',         
        'face_photo',      
    ];

    /**
     * Atributos que deben ocultarse en las respuestas JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
            'role_id' => 'integer',
        ];
    }

    /**
     * RELACIONES (3ra Forma Normal)
     */

    /**
     * Un usuario pertenece a un solo Rol.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Un usuario puede registrar muchos baches.
     */
    public function baches()
    {
        return $this->hasMany(Bache::class, 'user_id');
    }
}