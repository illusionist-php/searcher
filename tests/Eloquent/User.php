<?php

namespace Tests\Eloquent;

class User extends Model
{
    protected static $guardableColumns = [
        'Tests\Eloquent\User' => [
            'id', 'name',
        ],
    ];
}
