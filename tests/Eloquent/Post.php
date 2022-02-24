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
        'created_at' => 'datetime',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function one()
    {
        return $this->hasOne(Comment::class);
    }

    public function oneSelf()
    {
        return $this->hasOne(static::class);
    }

    public function many()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function manySelf()
    {
        return $this->belongsToMany(static::class);
    }
    public function through()
    {
        return $this->hasManyThrough(User::class, Comment::class);
    }

    public function throughSelf()
    {
        return $this->hasManyThrough(User::class, static::class);
    }

    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }

    public function getViewsAttribute()
    {
        return 100;
    }
}
