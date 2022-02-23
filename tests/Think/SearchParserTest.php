<?php

namespace Tests\Think;

use Illusionist\Searcher\Think\SearchParser;
use ReflectionClass;
use Tests\SearchParserTestCase as TestCase;
use think\Db;
use think\model\relation\BelongsToMany;
use think\model\relation\HasManyThrough;

class SearchParserTest extends TestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        Db::setConfig(['type' => 'sqlite', 'database' => ':memory:']);

        parent::setUp();
    }

    /**
     * Get a new search parser for the tester.
     *
     * @return \Illusionist\Searcher\SearchParser
     */
    protected function newParser()
    {
        return new SearchParser((new Post())->db());
    }

    /**
     * Get the SQL for the given query builder.
     *
     * @param  \think\db\Query  $builder
     * @return string
     */
    protected function getSql($builder)
    {
        $reflection = new ReflectionClass($builder);

        ($parseOptions = $reflection->getMethod('parseOptions'))->setAccessible(true);

        $parseOptions->invoke($builder);

        $sql = preg_replace(
            [
                '/\(?(\w+\.\w+)\s*=\s*(\w+\.\w+)\)?/',
                '/where \((\(select count\(\*\) from .+?\) [><=!]+ \d+)\)/',
                '/<>/',
                '/\(select count\(\*\) as tp_count from (\w+) where \((\w+) =(\w+)\.(\w+)\) limit 1\)/',
                '/\(select count\(\*\) as tp_count from (\w+) inner join (\w+) pivot on pivot\.(\w+) = (\w+\.\w+) where pivot\.(\w+) = (\w+\.\w+) limit 1\)/',
                '/limit 0 /',
                '/(\w+) (think_reserved_\d+) /',
                '/think_reserved_\d+/',
            ],
            ['$1 = $2', 'where $1', '!=', '(select count(*) from $1 where $3.$4 = $1.$2)', '(select count(*) from $1 inner join $2 on $4 = $2.$3 where $6 = $2.$5)', '', '$1 as $2 ', 'laravel_reserved_0'],
            preg_replace_callback('/([A-Z]{2,}|\(\s+|\s+\)|,|\s{2,})/', static function ($matches) {

                switch ($matches[1][0]) {
                    case ',':
                        return ', ';
                    case '(':
                        return '(';
                    case ' ':
                        return $matches[1][1];
                    default:
                        return strtolower($matches[1]);
                }
            }, $builder->getConnection()->getBuilder()->select($builder))
        );

        foreach ($builder->getBind(false) as $key => $bind) {
            $value = is_array($bind) ? $bind[0] : $bind;

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_string($value)) {
                $value = "'$value'";
            }

            $sql = str_replace(':' . $key, $value, $sql);
        }

        return trim($sql);
    }

    /**
     * Asserts if a builder has a given eager loads.
     *
     * @param  \think\db\Query  $builder
     * @param  array  $eagerLoads
     * @return $this
     */
    protected function assertEagerLoads($builder, $eagerLoads)
    {
        foreach ($eagerLoads as $relation => $expected) {
            $this->assertArrayHasKey($relation, $builder->getOptions('with'));

            $callable = $builder->getOptions('with')[$relation];

            $this->assertInternalType('callable', $callable);

            $instance = $builder->getModel()->{$relation}();

            if ($instance instanceof BelongsToMany || $instance instanceof HasManyThrough) {
                $expected = mb_substr($expected, 0, mb_strpos($expected, 'inner join') - 1);
            }

            $query = $instance->getModel()->db();

            $callable($query);

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
