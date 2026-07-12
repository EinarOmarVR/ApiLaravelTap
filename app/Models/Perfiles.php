<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Perfiles extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'perfiles';
}