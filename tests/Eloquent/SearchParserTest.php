<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illusionist\Searcher\Eloquent\SearchParser;
use Tests\SearchParserTestCase as TestCase;

class SearchParserTest extends TestCase
{
    /**
     * Get a new search parser for the tester.
     *
     * @return \Illusionist\Searcher\SearchParser
     */
    protected function newParser()
    {
        return new SearchParser(Post::query());
    }

    /**
     * Get the SQL for the given query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getSql($builder)
    {
        $sql = str_replace('?', '%s', $builder->toSql());

        $bindings = [];

        foreach ($builder->getBindings() as $binding) {
            if (is_bool($binding)) {
                $bindings[] = $binding ? 'true' : 'false';
            } elseif (is_string($binding)) {
                $bindings[] = "'$binding'";
            } else {
                $bindings[] = $binding;
            }
        }

        return str_replace(['`', '"'], '', vsprintf($sql, $bindings));
    }

    /**
     * Asserts if a builder has a given eager loads.
     *
     * @param  mixed  $builder
     * @param  array  $eagerLoads
     * @return $this
     */
    protected function assertEagerLoads($builder, $eagerLoads)
    {
        foreach ($eagerLoads as $relation => $expected) {
            $this->assertArrayHasKey($relation, $builder->getEagerLoads());

            $callable = $builder->getEagerLoads()[$relation];

            $this->assertInternalType('callable', $callable);

            $query = Relation::noConstraints(function () use ($builder, $relation, $callable) {
                $query = $builder->getModel()->{$relation}()->getQuery();

                $callable($query);

                return $query;
            });

            if (is_array($expected)) {
                $this->assertEquals(array_shift($expected), $this->getSql($query));
                $this->assertEagerLoads($query, $expected);
            } else {
                $this->assertEquals($expected, $this->getSql($query));
            }
        }

        return $this;
    }
}
