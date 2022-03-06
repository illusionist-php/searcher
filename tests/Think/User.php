<?php

namespace Tests\Think;

class User extends Model
{
    protected static $guardableColumns = [
        'Tests\Think\User' => [
            'id', 'name', 'phone',
        ],
    ];

    protected $table = 'users';
}
