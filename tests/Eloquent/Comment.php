<?php

namespace Tests\Eloquent;

class Comment extends Model
{
    protected static $guardableColumns = [
        'Tests\Eloquent\Comment' => [
            'id', 'post_id', 'user_id', 'title', 'stars',
        ],
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

