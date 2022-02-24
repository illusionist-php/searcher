<?php

namespace Tests;

use DateTime;
use PHPUnit\Framework\TestCase;

abstract class SearchParserTestCase extends TestCase
{
    /**
     * The SearchParser instance.
     *
     * @var \Illusionist\Searcher\SearchParser
     */
    protected $parser;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->parser = $this->newParser();
    }

    /**
     * Get a new search parser for the tester.
     *
     * @return \Illusionist\Searcher\SearchParser
     */
    abstract protected function newParser();

    /**
     * Get the SQL for the given query builder.
     *
     * @param  mixed  $builder
     * @return string
     */
    abstract protected function getSql($builder);

    /**
     * Provide data for success test.
     *
     * @return array
     */
    public function success()
    {
        return [
            // Assignments.
            [['title' => 'bar'], 'select * from posts where title = \'bar\''],
            [['title' => ['=', 'bar']], 'select * from posts where title = \'bar\''],
            [['title' => 10], 'select * from posts where title = 10'],
            [['published' => true], 'select * from posts where published = true'],

            // Comparisons.
            [['stars' => ['>', 0]], 'select * from posts where stars > 0'],
            [['stars' => ['>=', 0]], 'select * from posts where stars >= 0'],
            [['stars' => ['<', 0]], 'select * from posts where stars < 0'],
            [['stars' => ['<=', 0]], 'select * from posts where stars <= 0'],
            [['created_at' => ['>', '2018-05-14 00:41:10']], 'select * from posts where created_at > \'2018-05-14 00:41:10\''],

            // Solo.
            [['lonely'], 'select * from posts where title like \'%lonely%\''],
            [['3000'], 'select * from posts where (stars >= \'3000\' or exists (select * from comments where posts.id = comments.post_id and stars >= \'3000\'))'],

            // Boolean.
            [['published'], 'select * from posts where published = true'],
            [['not' => 'published'], 'select * from posts where published = false'],

            // Dates.
            [['created_at'], 'select * from posts where created_at is not null'],
            [['not' => 'created_at'], 'select * from posts where created_at is null'],

            // Dates year precision.
            [['created_at' => '2020'], "select * from posts where created_at between '2020-01-01 00:00:00' and '2020-12-31 23:59:59'"],
            [['created_at' => ['>', '2020']], "select * from posts where created_at > '2020-12-31 23:59:59'"],
            [['created_at' => ['<=', '2020']], "select * from posts where created_at <= '2020-12-31 23:59:59'"],
            [['created_at' => ['<', '2020']], "select * from posts where created_at < '2020-01-01 00:00:00'"],
            [['created_at' => ['>=', '2020']], "select * from posts where created_at >= '2020-01-01 00:00:00'"],
            [['not' => ['created_at' => '2020']], "select * from posts where created_at not between '2020-01-01 00:00:00' and '2020-12-31 23:59:59'"],

            // Dates month precision.
            [['created_at' => '2020/02'], "select * from posts where created_at between '2020-02-01 00:00:00' and '2020-02-29 23:59:59'"],
            [['created_at' => ['>', '2020/02']], "select * from posts where created_at > '2020-02-29 23:59:59'"],
            [['created_at' => ['<=', '2020/02']], "select * from posts where created_at <= '2020-02-29 23:59:59'"],
            [['created_at' => ['<', '2020/02']], "select * from posts where created_at < '2020-02-01 00:00:00'"],
            [['created_at' => ['>=', '2020/02']], "select * from posts where created_at >= '2020-02-01 00:00:00'"],
            [['created_at' => ['>', 'Dec 2020']], "select * from posts where created_at > '2020-12-31 23:59:59'"],

            // Dates day month precision.
            [['created_at' => '2020/02/01'], "select * from posts where created_at between '2020-02-01 00:00:00' and '2020-02-01 23:59:59'"],
            [['created_at' => ['>', '2020/02/01']], "select * from posts where created_at > '2020-02-01 23:59:59'"],
            [['created_at' => ['<=', '2020/02/01']], "select * from posts where created_at <= '2020-02-01 23:59:59'"],
            [['created_at' => ['<', '2020/02/01']], "select * from posts where created_at < '2020-02-01 00:00:00'"],
            [['created_at' => ['>=', '2020/02/01']], "select * from posts where created_at >= '2020-02-01 00:00:00'"],
            [['created_at' => ['>', 'Dec 31 2020']], "select * from posts where created_at > '2020-12-31 23:59:59'"],
            [['created_at' => ['<=', '31/12/2020']], "select * from posts where created_at <= '2020-12-31 23:59:59'"],

            // Dates hour precision.
            [['created_at' => '2020/02/01 10'], "select * from posts where created_at between '2020-02-01 10:00:00' and '2020-02-01 10:59:59'"],
            [['created_at' => ['>', '2020/02/01 10']], "select * from posts where created_at > '2020-02-01 10:59:59'"],
            [['created_at' => ['<=', '2020/02/01 10']], "select * from posts where created_at <= '2020-02-01 10:59:59'"],
            [['created_at' => ['<', '2020/02/01 10']], "select * from posts where created_at < '2020-02-01 10:00:00'"],
            [['created_at' => ['>=', '2020/02/01 10']], "select * from posts where created_at >= '2020-02-01 10:00:00'"],
            [['created_at' => '2020/02/01 10pm'], "select * from posts where created_at between '2020-02-01 22:00:00' and '2020-02-01 22:59:59'"],

            // Dates minute precision.
            [['created_at' => '2020/02/01 10:02'], "select * from posts where created_at between '2020-02-01 10:02:00' and '2020-02-01 10:02:59'"],
            [['created_at' => ['>', '2020/02/01 10:02']], "select * from posts where created_at > '2020-02-01 10:02:59'"],
            [['created_at' => ['<=', '2020/02/01 10:02']], "select * from posts where created_at <= '2020-02-01 10:02:59'"],
            [['created_at' => ['<', '2020/02/01 10:02']], "select * from posts where created_at < '2020-02-01 10:02:00'"],
            [['created_at' => ['>=', '2020/02/01 10:02']], "select * from posts where created_at >= '2020-02-01 10:02:00'"],

            // Dates exact precision.
            [['created_at' => '2020/02/01 10:02:01'], "select * from posts where created_at = '2020-02-01 10:02:01'"],
            [['created_at' => 'Dec 31 2020 5:15:10pm'], "select * from posts where created_at = '2020-12-31 17:15:10'"],

            // Relative dates.
            [['created_at' => ['>', 'yesterday']], sprintf("select * from posts where created_at > '%s'", (new DateTime('yesterday'))->format('Y-m-d 23:59:59'))],
            [['created_at' => ['<', 'yesterday']], sprintf("select * from posts where created_at < '%s'", (new DateTime('yesterday'))->format('Y-m-d 00:00:00'))],

            // Not.
            [['not' => 'lonely'], 'select * from posts where title not like \'%lonely%\''],
            [['not' => ['title' => 'bar']], 'select * from posts where title != \'bar\''],
            [['not' => ['title' => ['=', 'bar']]], 'select * from posts where title != \'bar\''],
            [['not' => ['title' => ['!=', 'bar']]], 'select * from posts where title = \'bar\''],
            [['not' => ['stars' => ['>', 0]]], 'select * from posts where stars <= 0'],
            [['not' => ['stars' => ['>=', 0]]], 'select * from posts where stars < 0'],
            [['not' => ['stars' => ['<', 0]]], 'select * from posts where stars >= 0'],
            [['not' => ['stars' => ['<=', 0]]], 'select * from posts where stars > 0'],

            // And.
            [['bar', 'baz'], 'select * from posts where title like \'%bar%\' and title like \'%baz%\''],
            ['and' => ['bar', 'baz'], 'select * from posts where title like \'%bar%\' and title like \'%baz%\''],
            [['title' => 'bar', 'stars' => 0], 'select * from posts where title = \'bar\' and stars = 0'],
            ['and' => ['title' => 'bar', 'stars' => 0], 'select * from posts where title = \'bar\' and stars = 0'],
            [['title' => ['=', 'bar'], 'stars' => ['>', 0]], 'select * from posts where title = \'bar\' and stars > 0'],
            ['and' => ['title' => ['=', 'bar'], 'stars' => ['>', 0]], 'select * from posts where title = \'bar\' and stars > 0'],
            [[['title' => ['=', 'bar']], ['stars' => ['>', 0]]], 'select * from posts where title = \'bar\' and stars > 0'],
            ['and' => [['title' => ['=', 'bar']], ['stars' => ['>', 0]]], 'select * from posts where title = \'bar\' and stars > 0'],

            // Or.
            [['or' => ['bar', 'baz']], 'select * from posts where (title like \'%bar%\' or title like \'%baz%\')'],
            [['or' => ['title' => 'bar', 'stars' => 0]], 'select * from posts where (title = \'bar\' or stars = 0)'],
            [['or' => ['title' => ['=', 'bar'], 'stars' => ['>', 0]]], 'select * from posts where (title = \'bar\' or stars > 0)'],
            [['or' => [['title' => ['=', 'bar']], ['stars' => ['>', 0]]]], 'select * from posts where (title = \'bar\' or stars > 0)'],
            [['or' => ['title' => ['=', 'bar'], 'or' => [['stars' => ['>', 0]], ['stars' => -1]]]], 'select * from posts where (title = \'bar\' or stars > 0 or stars = -1)'],

            // Or precedes And.
            [['or' => ['stars' => ['>', 10], ['likes' => ['>=', 5], 'forks' => ['<', 5]], 'watches' => ['<=', 10]]], 'select * from posts where (stars > 10 or (likes >= 5 and forks < 5) or watches <= 10)'],
            [[['or' => ['stars' => ['>', 10], 'likes' => ['>=', 10]]], 'forks' => ['<', 10]], 'select * from posts where (stars > 10 or likes >= 10) and forks < 10'],
            [['or' => [['stars' => ['>', 10], 'likes' => ['>=', 10]], ['forks' => ['<', 10], 'watches' => ['<=', 10]]]], 'select * from posts where ((stars > 10 and likes >= 10) or (forks < 10 and watches <= 10))'],
            [[['or' => ['stars' => ['>', 10], 'likes' => ['>=', 10]]], ['or' => ['forks' => ['<', 10], 'watches' => ['<=', 10]]]], 'select * from posts where (stars > 10 or likes >= 10) and (forks < 10 or watches <= 10)'],
            [['or' => [['stars' => ['>', 10]], [['likes' => ['>=', 5]], ['forks' => ['<', 5]]], ['watches' => ['<=', 10]]]], 'select * from posts where (stars > 10 or (likes >= 5 and forks < 5) or watches <= 10)'],
            [[['or' => [['stars' => ['>', 10]], ['likes' => ['>=', 10]]]], ['forks' => ['<', 10]]], 'select * from posts where (stars > 10 or likes >= 10) and forks < 10'],
            [['or' => [[['stars' => ['>', 10]], ['likes' => ['>=', 10]]], [['forks' => ['<', 10]], ['watches' => ['<=', 10]]]]], 'select * from posts where ((stars > 10 and likes >= 10) or (forks < 10 and watches <= 10))'],
            [[['or' => [['stars' => ['>', 10]], ['likes' => ['>=', 10]]]], ['or' => [['forks' => ['<', 10]], ['watches' => ['<=', 10]]]]], 'select * from posts where (stars > 10 or likes >= 10) and (forks < 10 or watches <= 10)'],

            // Lists.
            [['status' => ['Finished', 'Archived']], "select * from posts where status in ('Finished', 'Archived')"],
            [['status' => ['in', 'Finished', 'Archived']], "select * from posts where status in ('Finished', 'Archived')"],
            [['title' => ['in', 1, 2, 3]], 'select * from posts where title in (1, 2, 3)'],
            [['title' => ['in', [1, 2, 3]]], 'select * from posts where title in (1, 2, 3)'],

            // Between.
            [['created_at' => ['between', ['2021-01-01 00:00:00', '2021-12-31 23:59:59']]], "select * from posts where created_at between '2021-01-01 00:00:00' and '2021-12-31 23:59:59'"],
            [['created_at' => ['between', '2021-01-01 00:00:00', '2021-12-31 23:59:59']], "select * from posts where created_at between '2021-01-01 00:00:00' and '2021-12-31 23:59:59'"],

            // Relationships.
            [['comments'], 'select * from posts where exists (select * from comments where posts.id = comments.post_id)'],
            [['not' => 'comments'], 'select * from posts where not exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => 3], 'select * from posts where (select count(*) from comments where posts.id = comments.post_id) = 3'],
            [['comments' => ['>', 10]], 'select * from posts where (select count(*) from comments where posts.id = comments.post_id) > 10'],
            [['comments' => ['>', 0]], 'select * from posts where exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['!=', 0]], 'select * from posts where exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['>=', 1]], 'select * from posts where exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['<=', 0]], 'select * from posts where not exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['=', 0]], 'select * from posts where not exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['<', 1]], 'select * from posts where not exists (select * from comments where posts.id = comments.post_id)'],
            [['comments' => ['title' => ['=', 'bar']]], 'select * from posts where exists (select * from comments where posts.id = comments.post_id and title = \'bar\')'],
            [['comments.title' => 'bar'], 'select * from posts where exists (select * from comments where posts.id = comments.post_id and title = \'bar\')'],
            [['comments' => [['>', 10], 'title' => 'bar']], 'select * from posts where (select count(*) from comments where posts.id = comments.post_id and title = \'bar\') > 10'],
            [['one'], 'select * from posts where exists (select * from comments where posts.id = comments.post_id)'],
            [['many'], 'select * from posts where exists (select * from comments inner join comment_post on comments.id = comment_post.comment_id where posts.id = comment_post.post_id)'],
            [['through'], 'select * from posts where exists (select * from users inner join comments on comments.id = users.comment_id where posts.id = comments.post_id)'],
            [['oneSelf'], 'select * from posts where exists (select * from posts as laravel_reserved_0 where posts.id = laravel_reserved_0.post_id)'],
            [['manySelf'], 'select * from posts where exists (select * from posts as laravel_reserved_0 inner join post_post on laravel_reserved_0.id = post_post.post_id where posts.id = post_post.post_id)'],

            // Nested relationships.
            [['comments' => ['author' => ['name' => 'John']]], 'select * from posts where exists (select * from comments where posts.id = comments.post_id and exists (select * from users where comments.user_id = users.id and name = \'John\'))'],
            [['comments.author.name' => 'John'], 'select * from posts where exists (select * from comments where posts.id = comments.post_id and exists (select * from users where comments.user_id = users.id and name = \'John\'))'],

            // Limit and offset.
            [['limit' => 10], 'select * from posts limit 10'],
            [['from' => 10], 'select * from posts offset 10'],
            [['from' => 10, 'limit' => 10], 'select * from posts limit 10 offset 10'],

            // Sort
            [['sort' => 'stars,-created_at'], 'select * from posts order by stars asc, created_at desc'],
            [['sort' => ['stars', '-created_at']], 'select * from posts order by stars asc, created_at desc'],

            // Select
            [['columns' => ['title', 'comments_count']], 'select title, (select count(*) from comments where posts.id = comments.post_id) as comments_count from posts'],
            [['columns' => 'title,comments.title'], 'select id, title from posts', ['comments' => 'select post_id, title from comments']],
            [['columns' => ['title', 'comments']], 'select id, title from posts', ['comments' => 'select * from comments']],
            [['columns' => ['title', 'comments.title', 'comments.author.name']], 'select id, title from posts', ['comments' => ['select post_id, user_id, title from comments', 'author' => 'select id, name from users']]],
            [['columns' => ['title', 'views']], 'select title from posts', [], ['views']],
            [['not' => ['columns' => 'id,title']], 'select stars, likes, forks, watches, published, status, created_at, updated_at from posts'],
            [['not' => ['columns' => ['id', 'title', 'comments.title']]], 'select stars, likes, forks, watches, published, status, created_at, updated_at, id from posts', ['comments' => 'select id, user_id, stars, post_id from comments']],
            [['not' => ['columns' => ['id', 'title', 'views']]], 'select stars, likes, forks, watches, published, status, created_at, updated_at from posts', [], []],
            [['columns' => ['title', 'one_count']], 'select title, (select count(*) from comments where posts.id = comments.post_id) as one_count from posts'],
            [['columns' => ['title', 'one.title']], 'select id, title from posts', ['one' => 'select post_id, title from comments']],
            [['columns' => ['title', 'many_count']], 'select title, (select count(*) from comments inner join comment_post on comments.id = comment_post.comment_id where posts.id = comment_post.post_id) as many_count from posts'],
            [['columns' => ['title', 'many.title']], 'select id, title from posts', ['many' => 'select comments.title from comments inner join comment_post on comments.id = comment_post.comment_id']],
            [['columns' => ['title', 'through.name']], 'select id, title from posts', ['through' => 'select users.name from users inner join comments on comments.id = users.comment_id']],
            [['columns' => ['title', 'oneSelf.title']], 'select id, title from posts', ['oneSelf' => 'select title from posts']],
            [['columns' => ['title', 'manySelf.title']], 'select id, title from posts', ['manySelf' => 'select posts.title from posts inner join post_post on posts.id = post_post.post_id']],
        ];
    }

    /**
     * Test the parser with success data
     *
     * @param  array  $input
     * @param  array  $expected
     * @param  array  $eagerLoads
     * @param  array|null  $appends
     * @return void
     * @dataProvider success
     */
    public function testParserWithSuccess($input, $expected, $eagerLoads = [], $appends = null)
    {
        $builder = $this->parser->import($input);
        $actual = $this->getSql($builder);

        $this->assertEagerLoads($builder, $eagerLoads)
            ->assertEquals($expected, $actual);

        if ($appends !== null) {
            $this->assertAppend($builder, $appends);
        }
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
        return $this;
    }

    /**
     * Asserts if a builder has a given append attributes.
     *
     * @param  mixed  $builder
     * @param  array  $appends
     * @return $this
     */
    protected function assertAppend($builder, $appends)
    {
        $this->assertEquals($appends, $this->getBuilderAppends($builder));

        return $this;
    }

    /**
     * Get the appends attribute of the builder.
     *
     * @param  mixed  $builder
     * @return array
     */
    protected function getBuilderAppends($builder)
    {
        return [];
    }
}
