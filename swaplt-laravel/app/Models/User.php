<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'verification_code',
        'email_verified_at',
        'reset_token',  // Asegúrate de agregar este campo
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function markEmailAsVerified()
    {
        $this->email_verified_at = now();
        $this->verification_code = null;
        $this->save();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey(); // Esto devuelve el ID del usuario
    }

    public function getJWTCustomClaims()
    {
        return []; // Aquí puedes agregar cualquier dato adicional que desees incluir en el JWT
    }
}
