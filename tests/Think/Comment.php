<?php

namespace Tests\Think;

class Comment extends Model
{
    protected static $guardableColumns = [
        'Tests\Think\Comment' => [
            'id', 'post_id', 'user_id', 'title', 'stars',
        ],
    ];

    protected $table = 'comments';

    public function author()
    {
        return $this->belongsTo(User::class);
    }
}
