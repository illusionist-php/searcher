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

    public function one()
    {
        return $this->hasOne(Comment::class);
    }

    public function many()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function oneThrough()
    {
        return $this->hasOneThrough(User::class, Comment::class);
    }

    public function manyThrough()
    {
        return $this->hasManyThrough(User::class, Comment::class);
    }

    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }
}
