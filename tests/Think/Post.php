<?php

namespace Tests\Think;

class Post extends Model
{
    protected static $guardableColumns = [
        'Tests\Think\Post' => [
            'id', 'title', 'stars', 'likes', 'forks',
            'watches', 'published', 'status', 'created_at', 'updated_at',
        ],
    ];

    protected $name = 'posts';

    protected $type = [
        'published' => 'boolean',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function one()
    {
        return $this->hasOne(Comment::class, 'post_id');
    }

    public function oneSelf()
    {
        return $this->hasOne(static::class, 'post_id');
    }

    public function many()
    {
        return $this->belongsToMany(Comment::class, 'comment_post', '', 'post_id');
    }

    public function manySelf()
    {
        return $this->belongsToMany(static::class, 'post_post', '', 'post_id');
    }

    public function through()
    {
        return $this->hasManyThrough(User::class, Comment::class, 'post_id', 'comment_id');
    }

    public function throughSelf()
    {
        return $this->hasManyThrough(User::class, static::class, 'post_id', 'post_id');
    }

    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }

    public function getViewsAttr()
    {
        return 100;
    }
}
