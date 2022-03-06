<?php

namespace Tests\Think;

class Comment extends Model
{
    protected static $guardableColumns = [
        'Tests\Think\Comment' => [
            'id', 'post_id', 'user_id', 'title', 'stars', 'dislikes',
        ],
    ];

    protected $name = 'comments';

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
