<?php

namespace Tests;

use Hoa\Compiler\Exception\UnexpectedToken;
use Illusionist\Searcher\CompiledParser;
use PHPUnit\Framework\TestCase;

class CompiledParserTest extends TestCase
{
    /**
     * The CompiledParser instance.
     *
     * @var \Illusionist\Searcher\CompiledParser
     */
    protected $parser;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->parser = new CompiledParser();
    }

    /**
     * Provide data for success test.
     *
     * @return array
     */
    public function success()
    {
        return [
            // Assignments.
            ['foo:bar', ['foo' => ['=', 'bar']]],
            ['foo: bar', ['foo' => ['=', 'bar']]],
            ['foo :bar', ['foo' => ['=', 'bar']]],
            ['foo : bar', ['foo' => ['=', 'bar']]],
            ['foo=10', ['foo' => ['=', 10]]],
            ['foo="bar baz"', ['foo' => ['=', 'bar baz']]],

            // Comparisons.
            ['amount>0', ['amount' => ['>', 0]]],
            ['amount> 0', ['amount' => ['>', 0]]],
            ['amount >0', ['amount' => ['>', 0]]],
            ['amount > 0', ['amount' => ['>', 0]]],
            ['amount >= 0', ['amount' => ['>=', 0]]],
            ['amount < 0', ['amount' => ['<', 0]]],
            ['amount <= 0', ['amount' => ['<=', 0]]],
            ['users_todos <= 10', ['users_todos' => ['<=', 10]]],
            ['date > "2018-05-14 00:41:10"', ['date' => ['>', '2018-05-14 00:41:10']]],

            // Solo.
            ['lonely', ['lonely']],
            [' lonely ', ['lonely']],
            ['"lonely"', ['lonely']],
            [' "lonely" ', ['lonely']],
            ['"so lonely"', ['so lonely']],

            // Not.
            ['not A', ['not' => 'A']],
            ['not (not A)', ['not' => ['not' => 'A']]],
            ['not not A', ['not' => ['not' => 'A']]],

            // And.
            ['A and B and C', ['A', 'B', 'C']],
            ['(A AND B) and C', [['A', 'B'], 'C']],
            ['A AND (B AND C)', ['A', ['B', 'C']]],
            ['foo:bar amount>0', [['foo' => ['=', 'bar']], ['amount' => ['>', 0]]]],
            ['amount > 10 and amount <= 100', [['amount' => ['>', 10]], ['amount' => ['<=', 100]]]],

            // Or.
            ['A or B or C', ['or' => ['A', 'B', 'C']]],
            ['(A OR B) or C', ['or' => [['or' => ['A', 'B']], 'C']]],
            ['A OR (B OR C)', ['or' => ['A', ['or' => ['B', 'C']]]]],
            ['foo:bar or amount>0', ['or' => [['foo' => ['=', 'bar']], ['amount' => ['>', 0]]]]],
            ['amount > 10 or amount < 3', ['or' => [['amount' => ['>', 10]], ['amount' => ['<', 3]]]]],

            // Or precedes And.
            ['A or B and C or D', ['or' => ['A', ['B', 'C'], 'D']]],
            ['(A or B) and C', [['or' => ['A', 'B']], 'C']],
            ['A B or C D', ['or' => [['A', 'B'], ['C', 'D']]]],
            ['(A or B) and (C or D)', [['or' => ['A', 'B']], ['or' => ['C', 'D']]]],
            ['(foo:bar or amount>10) and (foo:baz or amount < 10)', [['or' => [['foo' => ['=', 'bar']], ['amount' => ['>', 10]]]], ['or' => [['foo' => ['=', 'baz']], ['amount' => ['<', 10]]]]]],

            // Lists.
            ['foo:1,2,3', ['foo' => [1, 2, 3]]],
            ['foo: 1,2,3', ['foo' => [1, 2, 3]]],
            ['foo :1,2,3', ['foo' => [1, 2, 3]]],
            ['foo : 1,2,3', ['foo' => [1, 2, 3]]],
            ['foo : 1 , 2 , 3', ['foo' => [1, 2, 3]]],
            ['foo = "A B C",baz,"bar"', ['foo' => ['A B C', 'baz', 'bar']]],
            ['foo in(1,2,3)', ['foo' => [1, 2, 3]]],
            ['foo in (1,2,3)', ['foo' => [1, 2, 3]]],
            [' foo in ( 1 , 2 , 3 ) ', ['foo' => [1, 2, 3]]],

            // Between.
            ['foo:3~5', ['foo' => ['between', [3, 5]]]],
            ['foo: 3~5', ['foo' => ['between', [3, 5]]]],
            ['foo :3~5', ['foo' => ['between', [3, 5]]]],
            ['foo : 3~5', ['foo' => ['between', [3, 5]]]],
            ['foo : 3 ~ 5', ['foo' => ['between', [3, 5]]]],
            ['foo = "3" ~ "5"', ['foo' => ['between', [3, 5]]]],
            ['foo between(3,5)', ['foo' => ['between', [3, 5]]]],
            ['foo between (3,5)', ['foo' => ['between', [3, 5]]]],
            [' foo between ( 3 , 5 )', ['foo' => ['between', [3, 5]]]],

            // Relationships.
            ['comments.author = "John Doe"', ['comments' => ['author' => ['=', 'John Doe']]]],
            ['comments.author.tags > 3', ['comments' => ['author' => ['tags' => ['>', 3]]]]],
            ['comments.author', ['comments' => 'author']],
            ['comments.author.tags', ['comments' => ['author' => 'tags']]],
            ['not comments.author', ['not' => ['comments' => 'author']]],
            ['not comments.author = "John Doe"', ['not' => ['comments' => ['author' => ['=', 'John Doe']]]]],

            // Nested relationships.
            ['comments: (author: John or votes > 10)', ['comments' => ['or' => [['author' => ['=', 'John']], ['votes' => ['>', 10]]]]]],
            ['comments: (author: John) = 20', ['comments' => [['=', 20], 'author' => ['=', 'John']]]],
            ['comments: (author: John) <= 10', ['comments' => [['<=', 10], 'author' => ['=', 'John']]]],
            ['comments: ("This is great")', ['comments' => ['This is great']]],
            ['comments.author: (name: "John Doe" age > 18) > 3', ['comments' => [['>', 3], 'author' => [['name' => ['=', 'John Doe']], ['age' => ['>', 18]]]]]],
            ['comments: (achievements: (Laravel) >= 2) > 10', ['comments' => [['>', 10], 'achievements' => [['>=', 2], 'Laravel']]]],
            ['comments: (not achievements: (Laravel))', ['comments' => ['not' => ['achievements' => ['Laravel']]]]],
            ['not comments: (achievements: (Laravel))', ['not' => ['comments' => ['achievements' => ['Laravel']]]]],
        ];
    }

    /**
     * Provide data for failure test.
     *
     * @return array
     */
    public function failure()
    {
        return [
            // Unfinished.
            ['not ', 'EOF'],
            ['foo = ', 'T_ASSIGNMENT'],
            ['foo <= ', 'T_COMPARATOR'],
            ['foo in ', 'T_IN'],
            ['foo:3~', 'T_ASSIGNMENT'],
            ['foo between', 'T_BETWEEN'],
            ['(', 'EOF'],

            // Strings as key.
            ['"string as key":foo', 'T_ASSIGNMENT'],
            ['foo and bar and "string as key" > 3', 'T_COMPARATOR'],
            ['not "string as key" in (1,2,3)', 'T_IN'],

            // Lonely operators.
            ['and', 'T_AND'],
            ['or', 'T_OR'],
            ['in', 'T_IN'],
            ['=', 'T_ASSIGNMENT'],
            [':', 'T_ASSIGNMENT'],
            ['<', 'T_COMPARATOR'],
            ['<=', 'T_COMPARATOR'],
            ['>', 'T_COMPARATOR'],
            ['>=', 'T_COMPARATOR'],

            // Invalid operators.
            ['foo<>3', 'T_COMPARATOR'],
            ['foo=>3', 'T_ASSIGNMENT'],
            ['foo=<3', 'T_ASSIGNMENT'],
            ['foo < in 3', 'T_COMPARATOR'],
            ['foo in = 1,2,3', 'T_IN'],
            ['foo == 1,2,3', 'T_ASSIGNMENT'],
            ['foo := 1,2,3', 'T_ASSIGNMENT'],
            ['foo:1:2:3:4', 'T_ASSIGNMENT'],
        ];
    }

    /**
     * Test the parser with success data.
     *
     * @param  string  $input
     * @param  array  $expected
     * @return void
     * @dataProvider success
     */
    public function testParserWithSuccess($input, $expected)
    {
        $actual = $this->parser->parse($input);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the parser with failure data.
     *
     * @param  string  $input
     * @param  string  $token
     * @return void
     * @dataProvider failure
     */
    public function testParserWithFailure($input, $token)
    {
        try {
            $this->parser->parse($input);
        } catch (UnexpectedToken $e) {
            /**@var \Hoa\Exception\Exception $e */
            $this->assertEquals($token, $e->getArguments()[1]);
        }
    }
}
