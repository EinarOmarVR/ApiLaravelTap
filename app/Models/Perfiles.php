<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Permiso extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'perfiles';
}