<?php

namespace Tests\Eloquent;

class Post extends Model
{
    protected static $guardableColumns = [
        'Tests\Eloquent\Post' => [
            'id', 'title', 'stars', 'likes', 'forks',
            'watches', 'published', 'status', 'created_at', 'updated_at',
        ],
    ];

    protected $casts = [
        'published' => 'bool',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }
}
