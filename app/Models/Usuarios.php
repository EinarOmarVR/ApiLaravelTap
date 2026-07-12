<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Usuarios extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'usuarios';
}