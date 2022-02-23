<?php

namespace Illusionist\Searcher\Think;

use Closure;
use Illusionist\Searcher\SearchParser as Parser;
use ReflectionClass;
use RuntimeException;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;

class SearchParser extends Parser
{
    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * @param  object  $object
     * @param  string  $name
     * @return mixed
     */
    public static function getObjectPropertyValue($object, $name)
    {
        $reflection = new ReflectionClass($object);

        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Set the columns to be selected.
     *
     * @param  \think\db\Query  $builder
     * @param  array  $columns
     * @return \think\db\Query
     */
    protected function select($builder, $columns)
    {
        return $builder->field($columns);
    }

    /**
     * Add an "order by" clause to the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $column
     * @param  string  $direction
     * @return \think\db\Query
     */
    protected function orderBy($builder, $column, $direction = 'asc')
    {
        return $builder->order($column, $direction);
    }

    /**
     * Set the "limit" value of the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  int  $value
     * @return \think\db\Query
     */
    protected function limit($builder, $value)
    {
        $limit = $builder->getOptions('limit');

        if (($index = mb_strpos($limit, ',')) !== false) {
            return $builder->limit(mb_substr($limit, 0, $index), $value);
        }

        return $builder->limit($value);
    }

    /**
     * Set the "offset" value of the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  int  $value
     * @return \think\db\Query
     */
    protected function offset($builder, $value)
    {
        $length = $builder->getOptions('limit');

        if (($index = mb_strpos($length, ',')) !== false) {
            $length = mb_substr($length, $index + 1);
        } else {
            $length = 0;
        }

        return $builder->setOption('limit', $value . ',' . $length);
    }

    /**
     * Add a relationship count / exists condition to the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \think\db\Query
     */
    protected function has($builder, $relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $method = $this->canUseExistsForExistenceCheck($operator, $count)
            ? 'getRelationExistenceQuery'
            : 'getRelationExistenceCountQuery';

        $hasQuery = $this->{$method}($builder, $relation);

        if ($callback) {
            $callback($hasQuery);
        }

        return $this->addHasWhere(
            $builder,
            $relation,
            $hasQuery,
            $operator,
            $count,
            $boolean
        );
    }

    /**
     * Check if we can run an "exists" query to optimize performance.
     *
     * @param  string  $operator
     * @param  int  $count
     * @return bool
     */
    protected function canUseExistsForExistenceCheck($operator, $count)
    {
        return ($operator === '>=' || $operator === '<') && $count === 1;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $relation
     * @param  array|mixed  $columns
     * @return \think\db\Query
     */
    protected function getRelationExistenceQuery($builder, $relation, $columns = ['*'])
    {
        $parent = $builder->getModel();
        $relation = $parent->{$relation}();

        if ($relation instanceof MorphOne || $relation instanceof MorphMany) {
            throw new RuntimeException('Unsupported MorphOne and MorphMany relationships.');
        }

        /**@var \think\Model $related */
        $related = $relation->getModel();
        $relatedTable = $this->fixRelationTable($parent, $query = $related->db());

        if ($relation instanceof HasManyThrough) {
            /**@var \think\Model $through */
            $through = static::getObjectPropertyValue($relation, 'through');
            $through = new $through;

            $throughQuery = $through->db();
            $throughTable = $this->fixRelationTable($parent, $throughQuery);

            $query->join($throughQuery->getTable(), sprintf(
                '%s.%s = %s.%s',
                $throughTable,
                $through->getPk(),
                $relatedTable,
                static::getObjectPropertyValue($relation, 'throughKey')
            ))->whereColumn(
                $parent->qualifyColumn(static::getObjectPropertyValue($relation, 'localKey')),
                '=',
                $throughTable . '.' . static::getObjectPropertyValue($relation, 'foreignKey')
            );
        } elseif ($relation instanceof BelongsToMany) {
            /**@var \think\model\Pivot $pivot */
            $pivot = static::getObjectPropertyValue($relation, 'pivot');
            $table = $pivot->getTable();

            $query->join($table, sprintf(
                '%s.%s = %s.%s',
                $relatedTable,
                $related->getPk(),
                $table,
                static::getObjectPropertyValue($relation, 'foreignKey')
            ))->whereColumn(
                $parent->qualifyColumn($parent->getPk()),
                '=',
                $table . '.' . static::getObjectPropertyValue($relation, 'localKey')
            );
        } elseif ($relation instanceof BelongsTo) {
            $query->whereColumn(
                $parent->qualifyColumn(static::getObjectPropertyValue($relation, 'foreignKey')),
                '=',
                $relatedTable . '.' . static::getObjectPropertyValue($relation, 'localKey')
            );
        } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
            $query->whereColumn(
                $parent->qualifyColumn(static::getObjectPropertyValue($relation, 'localKey')),
                '=',
                $relatedTable . '.' . static::getObjectPropertyValue($relation, 'foreignKey')
            );
        }

        return $query->field($columns);
    }

    /**
     * Fix the table name of the a given relation model.
     *
     * @param  \think\db\Query  $parent
     * @param  \think\db\Query  $related
     * @return string
     */
    protected function fixRelationTable($parent, $related)
    {
        $table = $related->getTable();

        if ($table !== $parent->getTable()) {
            return $table;
        }

        $alias = $this->getRelationCountHash();

        $related->setOption('table', $table . ' ' . $alias);

        return $alias;
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    protected function getRelationCountHash()
    {
        return 'think_reserved_' . static::$selfJoinCount++;
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $relation
     * @return \think\db\Query
     */
    protected function getRelationExistenceCountQuery($builder, $relation)
    {
        return $this->getRelationExistenceQuery($builder, $relation, ['count(*)']);
    }

    /**
     * Add the "has" condition where clause to the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $relation
     * @param  \think\db\Query  $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return \think\db\Query
     */
    protected function addHasWhere($builder, $relation, $query, $operator, $count, $boolean)
    {
        return $this->canUseExistsForExistenceCheck($operator, $count)
            ? $this->addWhereExistsQuery($builder, $query, $boolean, $operator === '<' && $count === 1)
            : $this->addWhereCountQuery($builder, $query, $operator, $count, $boolean);
    }

    /**
     * Add an exists clause to the builder.
     *
     * @param  \think\db\Query $builder
     * @param  \think\db\Query $query
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    protected function addWhereExistsQuery($builder, $query, $boolean = 'and', $not = false)
    {
        return $builder->{$not ? 'whereNotExists' : 'whereExists'}(
            $query->buildSql(false),
            $boolean
        );
    }

    /**
     * Add a sub-query count clause to the builder.
     *
     * @param  \think\db\Query $builder
     * @param  \think\db\Query $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return \think\db\Query
     */
    protected function addWhereCountQuery($builder, $query, $operator, $count, $boolean)
    {
        return $builder->whereRaw(
            sprintf('%s %s %s', $query->buildSql(true), $operator, $count),
            [],
            $boolean
        );
    }

    /**
     * Add a basic where clause to the builder.
     *
     * @param  \think\db\Query  $builder
     * @param  string|\Closure  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \think\db\Query
     */
    protected function where($builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure) {
            $column = static function ($query) use ($builder, $column) {
                $query->model($builder->getModel())->table($builder->getTable());
                return $column($query);
            };
        }

        if ($operator === '!=') {
            $operator = '<>';
        }

        return $builder->{$boolean === 'or' ? 'whereOr' : 'where'}($column, $operator, $value);
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool  $not
     * @return \think\db\Query
     */
    public function whereNull($builder, $column, $boolean = 'and', $not = false)
    {
        return $builder->{$not ? 'whereNotNull' : 'whereNull'}($column, $boolean);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return \think\db\Query
     */
    public function whereBetween($builder, $column, $values, $boolean = 'and', $not = false)
    {
        return $builder->{$not ? 'whereNotBetween' : 'whereBetween'}($column, $values, $boolean);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  \think\db\Query  $builder
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return \think\db\Query
     */
    public function whereIn($builder, $column, $values, $boolean = 'and', $not = false)
    {
        return $builder->{$not ? 'whereNotIn' : 'whereIn'}($column, $values, $boolean);
    }
}
