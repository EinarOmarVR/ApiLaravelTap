<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Permisos extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'permisos';

  
}