<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Course extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'courses';

    protected $fillable = [
        'title',
        'questions',
        'created_by',
        'is_public',
        'timer',
        'question_count',
        'max_attempts',
    ];

    public $timestamps = true;
}
