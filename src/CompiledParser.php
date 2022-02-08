<?php

namespace Illusionist\Searcher;

use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\Rule\Choice;
use Hoa\Compiler\Llk\Rule\Concatenation;
use Hoa\Compiler\Llk\Rule\Repetition;
use Hoa\Compiler\Llk\Rule\Token;

/**
 * Parse search string
 *
 * Refined from lorisleiva/laravel-search-string
 *
 * @mixin \Hoa\Compiler\Llk\Parser
 */
class CompiledParser
{
    /**
     * The base parser instance.
     *
     * @var \Hoa\Compiler\Llk\Parser
     */
    protected $parser;

    /**
     * Create a new CompiledParser instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->parser = new Parser(
            [
                'default' => [
                    'skip' => '\s',
                    'T_ASSIGNMENT' => ':|=',
                    'T_COMPARATOR' => '>=?|<=?',
                    'T_IN' => '(in|IN)(?![^\(\)\s])',
                    'T_BETWEEN' => '(between|BETWEEN)(?![^\(\)\s])',
                    'T_AND' => '(and|AND)(?![^\(\)\s])',
                    'T_OR' => '(or|OR)(?![^\(\)\s])',
                    'T_NOT' => '(not|NOT)(?![^\(\)\s])',
                    'T_LPARENTHESIS' => '\(',
                    'T_RPARENTHESIS' => '\)',
                    'T_DOT' => '\.',
                    'T_COMMA' => ',',
                    'T_TILDE' => '~',
                    'T_NULL' => '(null|NULL)(?![^\(\)\s])',
                    'T_INTEGER' => '(\d+)(?![^\(\)\s,~])',
                    'T_DECIMAL' => '(\d+\.\d+)(?![^\(\)\s,~])',
                    'T_SINGLE_LQUOTE:single_quote_string' => '\'',
                    'T_DOUBLE_LQUOTE:double_quote_string' => '"',
                    'T_TERM' => '[^\s:><="\\\'\(\)\.,~]+',
                ],
                'single_quote_string' => [
                    'T_STRING' => '[^\']+',
                    'T_SINGLE_RQUOTE:default' => '\'',
                ],
                'double_quote_string' => [
                    'T_STRING' => '[^"]+',
                    'T_DOUBLE_RQUOTE:default' => '"',
                ],
            ],
            [
                // OrNode()
                'Expr' =>  new Concatenation('Expr', ['OrNode'], null),
                1 => new Token(1, 'T_ASSIGNMENT', null, -1, true),
                2 => new Token(2, 'T_COMPARATOR', null, -1, true),
                3 => new Token(3, 'T_IN', null, -1, true),
                4 => new Token(4, 'T_BETWEEN', null, -1, true),
                5 => new Token(5, 'T_AND', null, -1, false),
                6 => new Token(6, 'T_OR', null, -1, false),
                7 => new Token(7, 'T_NOT', null, -1, false),
                8 => new Token(8, 'T_LPARENTHESIS', null, -1, false),
                9 => new Token(9, 'T_RPARENTHESIS', null, -1, false),
                10 => new Token(10, 'T_DOT', null, -1, false),
                11 => new Token(11, 'T_COMMA', null, -1, false),
                12 => new Token(12, 'T_TILDE', null, -1, false),
                13 => new Token(13, 'T_NULL', null, -1, true),
                14 => new Token(14, 'T_INTEGER', null, -1, true),
                15 => new Token(15, 'T_DECIMAL', null, -1, true),
                16 => new Token(16, 'T_STRING', null, -1, true),
                17 => new Token(17, 'T_SINGLE_LQUOTE', null, -1, false),
                18 => new Token(18, 'T_SINGLE_RQUOTE', null, -1, false),
                19 => new Token(19, 'T_DOUBLE_LQUOTE', null, -1, false),
                20 => new Token(20, 'T_DOUBLE_RQUOTE', null, -1, false),
                21 => new Token(21, 'T_TERM', null, -1, true),
                22 => new Concatenation(22, [10, 21], '#NestedTerms'),
                23 => new Repetition(23, 1, -1, 22, null),
                // <T_TERM> (::T_DOT:: <T_TERM>)+
                'NestedTerms' => new Concatenation('NestedTerms', [21, 23], '#NestedTerms'),
                // <T_INTEGER> | <T_DECIMAL>
                'Number' => new Choice('Number', [14, 15], null),
                26 => new Repetition(26, 0, 1, 16, null),
                27 => new Concatenation(27, [17, 26, 18], null),
                28 => new Concatenation(28, [19, 26, 20], null),
                // ::T_SINGLE_LQUOTE:: <T_STRING>? ::T_SINGLE_RQUOTE:: | ::T_DOUBLE_LQUOTE:: <T_STRING>? ::T_DOUBLE_RQUOTE::
                'String' => new Choice('String', [27, 28], null),
                // String() | Number() | <T_TERM>
                'Scalar' => new Choice('Scalar', ['String', 'Number', 21], null),
                // Scalar() | <T_NULL>
                'NullableScalar' => new Choice('NullableScalar', ['Scalar', 13], null),
                32 => new Concatenation(32, [11, 'Scalar'], '#ScalarList'),
                33 => new Repetition(33, 0, -1, 32, null),
                // Scalar() ( ::T_COMMA:: Scalar() )*
                'ScalarList' => new Concatenation('ScalarList', ['Scalar', 33], '#ScalarList'),
                // <T_ASSIGNMENT> | <T_COMPARATOR>
                'Operator' => new Choice('Operator', [1, 2], null),
                // Scalar()
                'SoloNode' => new Concatenation('SoloNode', ['Scalar'], '#SoloNode'),
                // <T_TERM> Operator() NullableScalar()
                'QueryNode' => new Concatenation('QueryNode', [21, 'Operator', 'NullableScalar'], '#QueryNode'),
                38 => new Concatenation(38, [21, 3, 8, 'ScalarList', 9], '#ListNode'),
                39 => new Concatenation(39, [21, 1, 'ScalarList'], '#ListNode'),
                // <T_TERM> ::T_IN:: ::T_LPARENTHESIS:: ScalarList() ::T_RPARENTHESIS:: | <T_TERM> ::T_ASSIGNMENT:: ScalarList()
                'ListNode' => new Choice('ListNode', [38, 39], null),
                41 => new Concatenation(41, [21, 4, 8, 'Scalar', 11, 'Scalar', 9], '#BetweenNode'),
                42 => new Concatenation(42, [21, 1, 'Scalar', 12, 'Scalar'], '#BetweenNode'),
                // <T_TERM> ::T_BETWEEN:: ::T_LPARENTHESIS:: Scalar() ::T_COMMA:: Scalar() ::T_RPARENTHESIS:: | <T_TERM> ::T_ASSIGNMENT:: Scalar() ::T_TILDE:: Scalar()
                'BetweenNode' => new Choice('BetweenNode', [41, 42], null),
                44 => new Concatenation(44, ['Operator', 'NullableScalar'], '#RelationshipNode'),
                45 => new Repetition(45, 0, 1, 44, null),
                // NestedTerms() (Operator() NullableScalar())?
                'RelationshipNode' => new Concatenation('RelationshipNode', ['NestedTerms', 45], '#RelationshipNode'),
                47 => new Choice(47, [21, 'NestedTerms'], null),
                48 => new Concatenation(48, ['Operator', 14], null),
                49 => new Repetition(49, 0, 1, 48, null),
                // (<T_TERM> | NestedTerms()) ::T_ASSIGNMENT:: ::T_LPARENTHESIS:: Expr() ::T_RPARENTHESIS:: (Operator() <T_INTEGER>)?
                'NestedRelationshipNode' => new Concatenation('NestedRelationshipNode', [47, 1, 8, 'Expr', 9, 49], '#NestedRelationshipNode'),
                51 => new Repetition(51, 0, 1, 5, null),
                52 => new Concatenation(52, [51, 'TerminalNode'], '#AndNode'),
                53 => new Repetition(53, 0, -1, 52, null),
                // TerminalNode() ( ::T_AND::? TerminalNode() #AndNode )*
                'AndNode' => new Concatenation('AndNode', ['TerminalNode', 53], null),
                55 => new Concatenation(55, [6, 'AndNode'], '#OrNode'),
                56 => new Repetition(56, 0, -1, 55, null),
                // AndNode() ( ::T_OR:: AndNode() #OrNode)*
                'OrNode' => new Concatenation('OrNode', ['AndNode', 56]),
                // ::T_NOT:: TerminalNode()
                'NotNode' => new Concatenation('NotNode', [7, 'TerminalNode'], '#NotNode'),
                // ::T_LPARENTHESIS:: Expr() ::T_RPARENTHESIS::
                'NestedExpr' => new Concatenation('NestedExpr', [8, 'Expr', 9], null),
                // NestedExpr() | NotNode() | RelationshipNode() | NestedRelationshipNode() | QueryNode() | ListNode() | BetweenNode() | SoloNode()
                'TerminalNode' => new Choice('TerminalNode', ['NestedExpr', 'NotNode', 'RelationshipNode', 'NestedRelationshipNode', 'QueryNode', 'ListNode', 'BetweenNode', 'SoloNode'], null),
            ]
        );
    }

    /**
     * Get the underlying parser instance.
     *
     * @return \Hoa\Compiler\Llk\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Parse the text of a search term's.
     *
     * @param  string  $text
     * @param  string|null  $rule
     * @param  null  $reserved
     * @return array
     *
     * @throws \Hoa\Compiler\Exception\UnexpectedToken
     */
    public function parse($text)
    {
        $element = $this->parser->parse($text, 'Expr', true);

        return (array) $this->parseElement($element);
    }

    /**
     * Parse an element.
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return mixed
     */
    protected function parseElement($element)
    {
        switch ($element->getId()) {
            case '#OrNode':
                return $this->parseOrNode($element);
            case '#AndNode':
                return $this->parseAndNode($element);
            case '#NotNode':
                return $this->parseNotNode($element);
            case '#NestedRelationshipNode':
                return $this->parseNestedRelationshipNode($element);
            case '#RelationshipNode':
                return $this->parseRelationshipNode($element);
            case '#SoloNode':
                return $this->parseSoloNode($element);
            case '#QueryNode':
                return $this->parseQueryNode($element);
            case '#ListNode':
                return $this->parseListNode($element);
            case '#BetweenNode':
                return $this->parseBetweenNode($element);
            case '#ScalarList':
                return $this->parseScalarList($element);
            case '#NestedTerms':
                return $this->parseNestedTerms($element);
            case 'token':
                return $this->parseToken($element);
        }
    }

    /**
     * Parse the children of a given element.
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseChildren($element)
    {
        $children = [];

        foreach ($element->getChildren() as $child) {
            $children[] = $this->parseElement($child);
        }

        return $children;
    }

    /**
     * Parse the element of an 'OrNode'.
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseOrNode($element)
    {
        return ['or' => $this->parseChildren($element)];
    }

    /**
     * Parse the element of an "AndNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseAndNode($element)
    {
        return $this->parseChildren($element);
    }

    /**
     * Parse the element of a "NotNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseNotNode($element)
    {
        return ['not' => $this->parseChildren($element)[0]];
    }

    /**
     * Parse the element of a "NestedRelationshipNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseNestedRelationshipNode($element)
    {
        $term = $this->parseChildren($element);

        if (! is_array($term[0])) {
            $term[0] = [$term[0]];
        }

        $relation = array_shift($term[0]);

        $expression = $this->parseRelationshipExpression($term[0], $term[2]);

        if (count($term) > 4) {
            array_unshift($expression, [$term[3], $term[4]]);
        }

        return [$relation => $expression];
    }

    /**
     * Parse the element of a "RelationshipNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseRelationshipNode($element)
    {
        $term = $this->parseChildren($element);

        if (! is_array($term[0])) {
            $term[0] = [$term[0]];
        }

        $relation = array_shift($term[0]);

        $expression = $this->parseRelationshipExpression($term[0], array_slice($term, 1, 2));

        return [$relation => $expression];
    }

    /**
     * Parse the expression of a relationship term.
     *
     * @param  array  $term
     * @param  mixed  $expression
     * @return array
     */
    protected function parseRelationshipExpression($term, $expression)
    {
        if (empty($term)) {
            return (array) $expression;
        }

        if (is_array($expression) && count($term) === 1) {
            return count($expression) > 1
                ? [$term[0] => $expression]
                : $term[0];
        }

        $relation = array_shift($term);

        return [$relation => $this->parseRelationshipExpression($term, $expression)];
    }

    /**
     * Parse the element of a "SoloNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return string
     */
    protected function parseSoloNode($element)
    {
        return $this->parseChildren($element)[0];
    }

    /**
     * Parse the element of a "QueryNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseQueryNode($element)
    {
        $term = $this->parseChildren($element);

        return [$term[0] => [$term[1], count($term) > 2 ? $term[2] : '']];
    }

    /**
     * Parse the element of a "ListNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseListNode($element)
    {
        $term = $this->parseChildren($element);

        return [$term[0] => $term[2]];
    }

    /**
     * Parse the element of a "BetweenNode".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseBetweenNode($element)
    {
        $term = $this->parseChildren($element);

        return [$term[0] => ['between', array_slice($term, 2, 2)]];
    }

    /**
     * Parse the element of a "ScalarList".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseScalarList($element)
    {
        return $this->parseChildren($element);
    }

    /**
     * Parse the element of a "NestedTerms".
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return array
     */
    protected function parseNestedTerms($element)
    {
        return $this->parseChildren($element);
    }

    /**
     * Parse the element of a "Token" node.
     *
     * @param  \Hoa\Compiler\Llk\TreeNode  $element
     * @return string|null
     */
    protected function parseToken($element)
    {
        switch ($element->getValueToken()) {
            case 'T_ASSIGNMENT':
                return '=';
            case 'T_NULL':
                return null;
            default:
                return $element->getValueValue();
        }
    }

    /**
     * Handle dynamic method calls into the parser.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->parser->{$method}(...$parameters);
    }
}
