<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Usuarios extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;

    protected $connection = 'mongodb';

    protected $collection = 'usuarios';

    protected $hidden = [
        'SPassword'
    ];

    /**
     * Campo de contraseña
     */
    public function getAuthPassword()
    {
        return $this->SPassword;
    }

    /**
     * Campo usuario
     */
    public function getAuthIdentifierName()
    {
        return '_id';
    }

    /**
     * JWT
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}