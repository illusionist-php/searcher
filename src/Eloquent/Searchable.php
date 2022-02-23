<?php

namespace Illusionist\Searcher\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illusionist\Searcher\CompiledParser;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable as LaravelScoutSearchable;

trait Searchable
{
    /**
     * The attributes that are mass searchable.
     *
     * @var array
     */
    protected $searchable = ['*'];

    /**
     * The actual columns that exist on the database and can be guarded.
     *
     * @var array
     */
    protected static $guardableColumns = [];

    /**
     * Perform a search against the model's indexed data.
     *
     * @param  string|array  $query
     * @param  \Closure  $callback
     * @return \Illuminate\Database\Eloquent\Builder|\Laravel\Scout\Builder
     */
    public static function search($query, $callback = null)
    {
        $builder = in_array(LaravelScoutSearchable::class, class_uses_recursive(static::class))
            ? app(Builder::class, [
                'model' => new static,
                'query' => '',
                'callback' => $callback,
                'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false)
            ])
            : (new static)->newQuery();

        return $builder->search($query);
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);

        // For select
        $model->setAppends($this->appends);

        return $model;
    }

    /**
     * Scope a query to only include model's of a given search terms.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Laravel\Scout\Builder  $builder
     * @param  string|array  $terms
     * @return \Illuminate\Database\Eloquent\Builder|\Laravel\Scout\Builder
     */
    public function scopeSearch($builder, $terms)
    {
        if (is_string($terms)) {
            $terms = app(CompiledParser::class)->parse($terms);
        }

        return app(SearchParser::class, compact('builder'))->import($terms);
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        if (mb_strpos($column, '.') !== false) {
            return $column;
        }

        return $this->getTable() . '.' . $column;
    }

    /**
     * Get the actual columns that exist on the database and can be guarded.
     *
     * @return array
     */
    public function getGuardableColumns()
    {
        if (!isset(static::$guardableColumns[static::class])) {
            static::$guardableColumns[static::class] = $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->getTable());
        }

        return static::$guardableColumns[static::class];
    }

    /**
     * Get the columns of the query phrase.
     *
     * @param  string  $phrase
     * @return array
     */
    public function getQueryPhraseColumns($phrase)
    {
        return [];
    }

    /**
     * Get the real name of the given search column.
     *
     * @param  string  $key
     * @return string|array
     */
    public function getRelaSearchName($key)
    {
        switch ($key) {
            case 'columns':
                return 'select';
            case 'sort':
                return 'order_by';
            case 'from':
                return 'offset';
            default:
                return $key;
        }
    }

    /**
     * Get the local key and foreign key for the relationship.
     *
     * @param  string  $relation
     * @param  boolean  $joined
     * @return array|false
     */
    public function getRelationKeyNames($relation, &$joined = false)
    {
        if (!$this->isRelationAttribute($relation)) {
            return false;
        }

        $instance = $this->{$relation}();
        $joined = $instance instanceof BelongsToMany || $instance instanceof HasManyThrough;

        if ($instance instanceof MorphOneOrMany) {
            return [
                $instance->getLocalKeyName(),
                [$instance->getMorphType(), $instance->getForeignKeyName()]
            ];
        }

        if ($instance instanceof HasOneOrMany || $instance instanceof HasManyThrough) {
            $localKeyName = explode('.', $instance->getQualifiedParentKeyName());
            $foreignKeyName = explode('.', $instance->getQualifiedForeignKeyName());
            return [end($localKeyName), end($foreignKeyName)];
        }

        if ($instance instanceof BelongsTo) {
            return [$instance->getForeignKey(), $instance->getOwnerKey()];
        }

        if ($instance instanceof BelongsToMany) {
            $parentKeyName = explode('.', $instance->getQualifiedParentKeyName());
            return [end($parentKeyName), null];
        }

        return false;
    }

    /**
     * Determine if the given attribute is visible.
     *
     * @param  string  $key
     * @return bool
     */
    public function isVisible($key)
    {
        if (in_array($key, $this->hidden, true)) {
            return false;
        }

        return empty($this->visible) || in_array($key, $this->visible, true);
    }

    /**
     * Determine if the given attribute may be search.
     *
     * @param  string  $key
     * @return boolean
     */
    public function isSearchable($key)
    {
        if ($this->searchable === ['*']) {
            return $this->isRelationAttribute($key) ||
                in_array($key, $this->getGuardableColumns(), true);
        }

        return in_array($key, $this->searchable, true);
    }

    /**
     * Determine if the given attribute is a relationship.
     *
     * @param  string  $key
     * @return boolean
     */
    public function isRelationAttribute($key)
    {
        return method_exists($this, $key) && !method_exists(Model::class, $key);
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param  string  $key
     * @return bool
     */
    public function isDateAttribute($key)
    {
        return $Key === 'created_at';
    }

    /**
     * Determine if the given attribute is a boolean.
     *
     * @param  string  $key
     * @return bool
     */
    public function isBooleanAttribute($key)
    {
        return $this->hasCast($key, ['bool', 'boolean']);
    }
}
