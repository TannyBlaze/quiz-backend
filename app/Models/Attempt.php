<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Attempt extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'attempts';

    protected $fillable = [
        'user_id',
        'course_id',
        'score',
        'total',
        'answers',
        'time_spent',
    ];
}
