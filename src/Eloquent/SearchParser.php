<?php

namespace Illusionist\Searcher\Eloquent;

use Illusionist\Searcher\Contracts\Searchable;
use Illusionist\Searcher\SearchParser as Parser;
use Kalnoy\Nestedset\QueryBuilder as NestedsetBuilder;
use Laravel\Scout\Builder as LaravelScoutBuilder;

class SearchParser extends Parser
{
    /**
     * Execute a callback with a builder.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    protected function builder($callback)
    {
        if ($this->isLaravelScoutBuilder($this->builder)) {
            $this->builder->query($callback);
        } else {
            $callback($this->builder);
        }

        return $this;
    }

    /**
     * Get the searchable instance of the builder.
     *
     * @param  mixed  $builder
     * @return \Illusionist\Searcher\Contracts\Searchable
     */
    protected function getSearchable($builder): Searchable
    {
        if ($this->isLaravelScoutBuilder($builder)) {
            return $builder->model;
        }

        return $builder->getModel();
    }

    /**
     * Determine if the given builder is "laravel/scout" builder.
     *
     * @param  mixed  $builder
     * @return boolean
     */
    protected function isLaravelScoutBuilder($builder)
    {
        return $builder instanceof LaravelScoutBuilder;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  mixed  $builder
     * @param  array  $columns
     * @return mixed
     */
    protected function select($builder, $columns)
    {
        if ($columns !== ['*'] && $builder instanceof NestedsetBuilder) {
            $model = $builder->getModel();

            array_push(
                $columns,
                $model->getParentIdName(),
                $model->getLftName(),
                $model->getRgtName()
            );

            $columns = array_unique($columns);
        }

        return $builder->select($columns);
    }

    /**
     * Add a basic where clause to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string|\Closure  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return mixed
     */
    protected function where($builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($operator === 'like' &&
            $this->isLaravelScoutBuilder($this->builder) &&
            $builder->getModel() === $this->builder->model
        ) {
            $this->builder->query = mb_substr($value, 1, -1);
            return $builder;
        }

        return $builder->where($column, $operator, $value, $boolean);
    }
}
