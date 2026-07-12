<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
class Productos extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'Productos';

    
}
