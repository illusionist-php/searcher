<?php

namespace Illusionist\Searcher\Think;

use Illusionist\Searcher\CompiledParser;
use think\Db;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;

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
     * Create a new instance of the given model.
     *
     * @param  array  $data
     * @param  bool  $isUpdate
     * @param  mixed  $where
     * @return static
     */
    public function newInstance($data = [], $isUpdate = false, $where = null)
    {
        $model = parent::newInstance($data, $isUpdate, $where);

        // For select
        $model->append($this->appends);

        return $model;
    }

    /**
     * Scope a query to only include model's of a given search terms.
     *
     * @param  \think\db\Query  $builder
     * @param  string|array  $terms
     * @return \think\db\Query
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
            static::$guardableColumns[static::class] = $this->getTableFields();
        }

        return static::$guardableColumns[static::class];
    }

    /**
     * Get the term of the query phrase.
     *
     * @param  string  $phrase
     * @return array
     */
    public function getQueryPhraseTerm($phrase)
    {
        $term = [];

        foreach ($this->getQueryPhraseColumns($phrase) as $column => $operator) {
            if (is_int($column)) {
                $term[$operator] = ['like', "%${phrase}%"];
            } else {
                $term[$column] = [$operator, $phrase];
            }
        }

        return $term;
    }

    /**
     * Get the columns of the query phrase.
     *
     * @param  string  $phrase
     * @return array
     */
    protected function getQueryPhraseColumns($phrase)
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
            case 'keyword':
                return '__KEYWORD__';
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

        if ($instance instanceof MorphOne || $instance instanceof MorphMany) {
            return [
                $this->getPk(),
                [
                    SearchParser::getObjectPropertyValue($instance, 'morphKey'),
                    SearchParser::getObjectPropertyValue($instance, 'morphType'),
                ]
            ];
        }

        if ($instance instanceof HasManyThrough) {
            return [
                SearchParser::getObjectPropertyValue($instance, 'localKey'),
                SearchParser::getObjectPropertyValue($instance, 'throughKey'),
            ];
        }

        if ($instance instanceof BelongsToMany) {
            return [$this->getPk(), null];
        }


        if ($instance instanceof HasOne || $instance instanceof HasMany) {
            return [
                SearchParser::getObjectPropertyValue($instance, 'localKey'),
                SearchParser::getObjectPropertyValue($instance, 'foreignKey'),
            ];
        }

        if ($instance instanceof BelongsTo) {
            return [
                SearchParser::getObjectPropertyValue($instance, 'foreignKey'),
                SearchParser::getObjectPropertyValue($instance, 'localKey'),
            ];
        }

        return false;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . Db::parseName($key, 1) . 'Attr');
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
        return $this->isRelationAttr($key);
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param  string  $key
     * @return bool
     */
    public function isDateAttribute($key)
    {
        if (
            $this->autoWriteTimestamp &&
            ($key === $this->createTime || $key === $this->updateTime)
        ) {
            return true;
        }

        return array_key_exists($key, $this->type) &&
            in_array(explode(':', $this->type[$key], 2)[0], ['datetime', 'timestamp'], true);
    }

    /**
     * Determine if the given attribute is a boolean.
     *
     * @param  string  $key
     * @return bool
     */
    public function isBooleanAttribute($key)
    {
        return array_key_exists($key, $this->type) &&
            in_array($this->type[$key], ['bool', 'boolean'], true);
    }
}
