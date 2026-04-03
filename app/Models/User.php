<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'api_token'
    ];

    protected $hidden = [
        'password',
        'api_token'
    ];
}
