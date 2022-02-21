<?php

namespace Illusionist\Searcher\Contracts;

interface Searchable
{
    /**
     * Append attributes to query when building a query.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function append($attributes);

    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumn($column);

    /**
     * Get the actual columns that exist on the database and can be guarded.
     *
     * @return array
     */
    public function getGuardableColumns();

    /**
     * Get the columns of the query phrase.
     *
     * @param  string  $phrase
     * @return array
     */
    public function getQueryPhraseColumns($phrase);

    /**
     * Get the real name of the given search column.
     *
     * @param  string  $key
     * @return string|array
     */
    public function getRelaSearchName($key);

    /**
     * Get the local key and foreign key for the relationship.
     *
     * @param  string  $relation
     * @param  boolean  $joined
     * @return array|false
     */
    public function getRelationKeyNames($relation, &$joined = false);

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key);

    /**
     * Determine if the given attribute is visible.
     *
     * @param  string  $key
     * @return bool
     */
    public function isVisible($key);

    /**
     * Determine if the given attribute may be search.
     *
     * @param  string  $key
     * @return boolean
     */
    public function isSearchable($key);

    /**
     * Determine if the given attribute is a relationship.
     *
     * @param  string  $key
     * @return boolean
     */
    public function isRelationAttribute($key);

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param  string  $key
     * @return bool
     */
    public function isDateAttribute($key);

    /**
     * Determine if the given attribute is a boolean.
     *
     * @param  string  $key
     * @return bool
     */
    public function isBooleanAttribute($key);
}
