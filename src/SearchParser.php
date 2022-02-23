<?php

namespace Illusionist\Searcher;

use DateTime;
use Exception;
use InvalidArgumentException;

abstract class SearchParser
{

    /**
     * The format of date value.
     *
     * @var string
     */
    protected static $dateFormat = 'Y-m-d H:i:s';

    /**
     * The builder instance.
     *
     * @var mixed
     */
    protected $builder;

    /**
     * Create a new SearchParser instance.
     *
     * @param  mixed  $builder
     * @return void
     */
    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    protected static function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }

    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    protected static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Set the default format used when type juggling a DateTime instance to a string.
     *
     * @param  string  $format
     * @return void
     */
    public static function setDateFormat($format)
    {
        static::$dateFormat = $format;
    }

    /**
     * Parse a date string.
     *
     * @param  string  $datetime
     * @param  string|null  &$format
     * @return \DateTime
     */
    protected static function parseDate($datetime, &$format = null)
    {
        $pattern = '/^
                ((?:jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*|[0-9]{1,5})                               # year | month | day
                (?:[-\/ ]((?:jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*|[0-9]{1,5}))?                    # year | month | day
                (?:[-\/ ]([0-9]{1,5}))?                                                                                   # year | day
                (?:[ ]([01]?[0-9]|2[0-3]))?                                                                               # hour
                (?::([0-5][0-9]))?                                                                                        # minute
                (?::([0-5][0-9]))?                                                                                        # second
                (?:\.([0-9]{1,6}))?                                                                                       # microsecond
                (am|pm)?
            $/ix';

        if (!preg_match($pattern, $datetime, $matches)) {
            return new DateTime($datetime);
        }

        if (!is_numeric($matches[1])) {
            if (
                !isset($matches[2]) ||
                (isset($matches[3]) && !preg_match('/^(3[01]|[12][0-9]|0?[1-9])$/', $matches[2])) ||
                (!isset($matches[3]) && !preg_match('/^([1-9]?[0-9]{4}|[0-9]{2})$/', $matches[2]))
            ) {
                throw new Exception(sprintf('Failed to parse time string "%s"', $datetime));
            } elseif (isset($matches[3])) {
                $format = strlen($matches[3]) > 2 ? 'M j Y' : 'M j y';
            } else {
                $format = strlen($matches[2]) > 2 ? 'M Y' : 'M y';
            }
        } elseif (isset($matches[3]) && $matches[3] !== '') {
            if (strlen($matches[3]) > 2) {
                $format = 'j n Y';
            } elseif (!is_numeric($matches[2])) {
                $format = strlen($matches[1]) > 2 ? 'Y M j' : 'y M j';
            } elseif (!preg_match('/^(3[01]|[12][0-9]|0?[1-9])$/', $matches[3])) {
                throw new Exception(sprintf('Failed to parse time string "%s"', $datetime));
            } else {
                $format = strlen($matches[1]) > 2 ? 'Y n j' : 'y n j';
            }
        } elseif (isset($matches[2]) && $matches[2] !== '') {
            if (!is_numeric($matches[2])) {
                $format = strlen($matches[1]) > 2 ? 'Y M' : 'y M';
            } elseif (strlen($matches[2]) > 2) {
                $format = 'n Y';
            } elseif (strlen($matches[1]) > 2) {
                $format = 'Y n';
            } else {
                $format = count($matches) > 3 ? 'y n' : 'n y';
            }
        } else {
            $format = strlen($matches[1]) > 2 ? 'Y' : 'y';
        }

        if (isset($matches[4]) && $matches[4] !== '') {
            $format .= isset($matches[8]) && $matches[8] !== '' ? ' g' : ' G';
        }

        if (isset($matches[5]) && $matches[5] !== '') {
            $format .= ':i';
        }

        if (isset($matches[6]) && $matches[6] !== '') {
            $format .= ':s';
        }

        if (isset($matches[7]) && $matches[7] !== '') {
            $format .= '.u';
        }

        if (isset($matches[8]) && $matches[8] !== '') {
            $format .= 'a';
        }

        return DateTime::createFromFormat($format, str_replace(['-', '/'], ' ', $datetime));
    }

    /**
     * Get the minimum precision for the given date format.
     *
     * @param  string  $format
     * @return string
     */
    protected static function getDateFormatPrecision($format)
    {
        $precisions = [
            'micro'  => '/u/',
            'second' => '/s/',
            'minute' => '/i/',
            'hour'   => '/[hHgG]/',
            'day'    => '/[dj]/',
            'month'  => '/[mnMF]/',
            'year'   => '/[yY]/',
        ];

        foreach ($precisions as $precision => $pattern) {
            if (preg_match($pattern, $format)) {
                return $precision;
            }
        }

        return 'micro';
    }

    /**
     * Get the minimum precision of the given date.
     *
     * @param  \DateTime  $datetime
     * @return string
     */
    protected static function getDatePrecision($datetime)
    {
        $precisions = [
            'micro'  => 'u',
            'second' => 's',
            'minute' => 'i',
            'hour'   => 'G',
            'day'    => 'j',
            'month'  => 'n',
            'year'   => 'Y',
        ];

        foreach ($precisions as $precision => $format) {
            if ((int) $datetime->format($format) !== 0) {
                return $precision;
            }
        }

        return 'micro';
    }

    /**
     * Modify to end or start of datetime given precision.
     *
     * @param  \DateTime  $datetime
     * @param  string  $precision
     * @param  boolean  $end
     * @return \DateTime
     */
    protected static function adjustDateTime($datetime, $precision, $end = false)
    {
        switch ($precision) {
            case 'year':
                return $end
                    ? $datetime->setDate($datetime->format('Y'), 12, 31)->setTime(23, 59, 59, 999999)
                    : $datetime->setDate($datetime->format('Y'), 1, 1)->setTime(0, 0, 0, 0);
            case 'month':
                return $end
                    ? $datetime->setDate($datetime->format('Y'), $datetime->format('n'), $datetime->format('t'))
                    ->setTime(23, 59, 59, 999999)
                    : $datetime->setDate($datetime->format('Y'), $datetime->format('n'), 1)
                    ->setTime(0, 0, 0, 0);
            case 'day':
                return $end
                    ? $datetime->setTime(23, 59, 59, 999999)
                    : $datetime->setTime(0, 0, 0, 0);
            case 'hour':
                return $end
                    ? $datetime->setTime($datetime->format('G'), 59, 59, 999999)
                    : $datetime->setTime($datetime->format('G'), 0, 0, 0);
            case 'minute':
                return $end
                    ? $datetime->setTime($datetime->format('G'), $datetime->format('i'), 59, 999999)
                    : $datetime->setTime($datetime->format('G'), $datetime->format('i'), 0, 0);
            default:
                return $datetime;
        }
    }

    /**
     * Get the builder instance.
     *
     * @return mixed
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Import search terms to builder.
     *
     * @param  array  $terms
     * @return mixed
     */
    public function import($terms)
    {
        $this->builder(function ($builder) use ($terms) {
            $this->addTerms($builder, $terms);
        });

        return $this->builder;
    }

    /**
     * Execute a callback with a builder.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    protected function builder($callback)
    {
        $callback($this->builder);

        return $this;
    }

    /**
     * Add a list of terms to the builder.
     *
     * @param  mixed  $builder
     * @param  array  $terms
     * @param  string  $boolean
     * @param  boolean  $not
     * @return $this
     */
    protected function addTerms($builder, $terms, $boolean = 'and', $not = false)
    {
        $searchable = $this->getSearchable($builder);

        foreach ($terms as $key => $value) {
            if (is_int($key)) {
                $this->addSoloTerm($builder, $value, $boolean, $not);
                continue;
            }

            $column = $searchable->getRelaSearchName($key);

            if (is_array($column)) {
                $this->addBooleanTerm(
                    $builder,
                    'or',
                    array_fill_keys($column, $value),
                    $boolean,
                    $not
                );
            } else {
                switch (static::studly($column)) {
                    case 'Select':
                        $this->addSelectTerm($builder, $value, [], $not);
                        break;
                    case 'OrderBy':
                        $this->addOrderByTerm($builder, $value);
                        break;
                    case 'Limit':
                        $this->limit($builder, (int) $value);
                        break;
                    case 'Offset':
                        $this->offset($builder, (int) $value);
                        break;
                    case 'And':
                        $this->addBooleanTerm($builder, 'and', (array) $value, $boolean, $not);
                        break;
                    case 'AndOr':
                        $this->addBooleanTerm($builder, 'and', ['or' => $value], $boolean, $not);
                        break;
                    case 'Or':
                    case 'OrAnd':
                        $this->addBooleanTerm($builder, 'or', (array) $value, $boolean, $not);
                        break;
                    case 'Not':
                    case 'NotAnd':
                        $this->addTerms($builder, ['or' => $value], $boolean, true);
                        break;
                    case 'NotOr':
                        $this->addTerms($builder, ['and' => $value], $boolean, true);
                        break;
                    default:
                        $this->addQueryTerm($builder, $column, $value, $boolean, $not);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Add a solo term to the builder.
     *
     * @param  mixed  $builder
     * @param  mixed  $term
     * @param  string  $boolean
     * @param  boolean  $not
     * @return $this
     */
    protected function addSoloTerm($builder, $term, $boolean = 'and', $not = false)
    {
        if (is_array($term)) {
            return $this->addBooleanTerm(
                $builder,
                count($term) < 2 ? $boolean : 'and',
                $term,
                $boolean,
                $not
            );
        }

        $searchable = $this->getSearchable($builder);

        if (!$searchable->isSearchable($term)) {
            if ($columns = $searchable->getQueryPhraseColumns($term)) {
                $this->addBooleanTerm(
                    $builder,
                    'or',
                    $this->createPhraseTerm($columns, $term),
                    $boolean,
                    $not
                );
            }
        } elseif ($searchable->isRelationAttribute($term)) {
            $this->has($builder, $term, $not ? '<' : '>=', 1, $boolean);
        } elseif ($searchable->isBooleanAttribute($term)) {
            $this->where($builder, $term, '=', !$not);
        } else {
            $this->whereNull($builder, $term, $boolean, !$not);
        }

        return $this;
    }

    /**
     * Create a phrase term for the query.
     *
     * @param  array  $columns
     * @param  string  $value
     * @return array
     */
    protected function createPhraseTerm($columns, $value)
    {
        $term = [];

        foreach ($columns as $column => $operator) {
            if (is_int($column)) {
                $term[$operator] = ['like', "%{$value}%"];
            } else {
                $term[$column] = [$operator, $value];
            }
        }

        return $term;
    }

    /**
     * Add a boolean term to the builder.
     *
     * @param  mixed  $builder
     * @param  string  $operator
     * @param  array  $term
     * @param  string  $boolean
     * @param  boolean  $not
     * @return $this
     */
    protected function addBooleanTerm($builder, $operator, $term, $boolean = 'and', $not = false)
    {
        if ($operator === $boolean || count($term) === 1) {
            return $this->addTerms($builder, $term, $boolean, $not);
        }

        $this->where($builder, function ($builder) use ($operator, $term, $not) {
            $this->addTerms($builder, $term, $operator, $not);
        }, null, null, $boolean);

        return $this;
    }

    /**
     * Add a select term to the builder.
     *
     * @param  mixed  $builder
     * @param  string|array  $columns
     * @param  array  $localKeys
     * @param  boolean  $not
     * @param  boolean  $qualified
     * @return $this
     */
    protected function addSelectTerm($builder, $columns, $localKeys = [], $not = false, $qualified = false)
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $searchable = $this->getSearchable($builder);
        $parsed = $this->parseColumns($searchable, $columns, $localKeys, $not);

        if (!empty($parsed[0])) {
            if ($qualified) {
                $parsed[0] = array_map([$searchable, 'qualifyColumn'], $parsed[0]);
            }
            $this->select($builder, $parsed[0]);
        }

        if (!empty($parsed[1])) {
            $searchable->append($builder, $parsed[1]);
        }

        if (!empty($parsed[2])) {
            $this->with($builder, $parsed[2]);
        }

        if (!empty($parsed[3])) {
            $this->withCount($builder, $parsed[3]);
        }

        return $this;
    }

    /**
     * Parse a list of columns into individuals.
     *
     * @param  \Illusionist\Searcher\Contracts\Searchable  $searchable
     * @param  array  $columns
     * @param  array  $localKeys
     * @param  boolean  $not
     * @return array
     */
    protected function parseColumns($searchable, $columns, $localKeys = [], $not = false)
    {
        $results = [[], [], [], []];

        foreach ($columns as $column) {
            if ($column === '*') {
                $results[0] = ['*'];
            } elseif (
                mb_substr($column, -6) === '_count' &&
                $searchable->isRelationAttribute(
                    $relation = static::camel(mb_substr($column, 0, -6))
                )
            ) {
                if ($searchable->isVisible($relation)) {
                    $results[3][] = $relation;
                }
            } elseif (mb_strpos($column, '.') !== false) {
                list($relation, $name) = explode('.', $column, 2);

                if (
                    $searchable->isVisible($relation) &&
                    $searchable->isRelationAttribute($relation)
                ) {
                    $results[2][$relation][] = $name;
                }
            } elseif (!$searchable->isVisible($column)) {
                // Skip hidden attribute.
            } else {
                if ($searchable->hasGetMutator($column)) {
                    $results[1][] = $column;
                }

                if ($results[0] !== ['*']) {
                    $results[0][] = $column;
                }
            }
        }

        $results[2] = $this->createWithRelations($searchable, $results[2], $localKeys, $not);

        if ($results[0] !== ['*']) {
            $fn = $not ? 'array_diff' : 'array_intersect';
            $results[0] = $fn(
                $searchable->getGuardableColumns(),
                array_merge($results[0], $localKeys)
            );

            if ($not && !empty($localKeys)) {
                array_push($results[0], ...$localKeys);
            }
        }

        return $results;
    }

    /**
     * Create column constraints for the relations.
     *
     * @param  \Illusionist\Searcher\Contracts\Searchable  $searchable
     * @param  array  $relations
     * @param  string  $localKeys
     * @param  boolean  $not
     * @return array
     */
    protected function createWithRelations($searchable, $relations, &$localKeys = [], $not = false)
    {
        foreach ($relations as $method => $columns) {
            list($localKey, $foreignKey) = $searchable->getRelationKeyNames($method, $joined);

            if (!empty($localKey)) {
                $localKeys[] = $localKey;
            }

            $relations[$method] = function ($builder) use ($columns, $foreignKey, $not, $joined) {
                $this->addSelectTerm($builder, $columns, (array) $foreignKey, $not, $joined);
            };
        }

        return $relations;
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
        return $builder->select($columns);
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param  mixed  $builder
     * @param  mixed  $relations
     * @return mixed
     */
    protected function with($builder, $relations)
    {
        return $builder->with($relations);
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed  $builder
     * @param  mixed  $relations
     * @return mixed
     */
    protected function withCount($builder, $relations)
    {
        return $builder->withCount($relations);
    }

    /**
     * Add an "order by" term to the builder.
     *
     * @param  mixed  $builder
     * @param  string|array  $orders
     * @return $this
     */
    protected function addOrderByTerm($builder, $orders)
    {
        if (is_string($orders)) {
            $orders = explode(',', $orders);
        }

        foreach ($orders as $order) {
            if (mb_substr($order, 0, 1) === '-') {
                $this->orderBy($builder, mb_substr($order, 1), 'desc');
            } else {
                $this->orderBy($builder, $order, 'asc');
            }
        }

        return $this;
    }

    /**
     * Add an "order by" clause to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $column
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function orderBy($builder, $column, $direction = 'asc')
    {
        return $builder->orderBy($column, $direction);
    }

    /**
     * Set the "limit" value of the builder.
     *
     * @param  mixed  $builder
     * @param  int  $value
     * @return mixed
     */
    protected function limit($builder, $value)
    {
        return $builder->limit($value);
    }

    /**
     * Set the "offset" value of the builder.
     *
     * @param  mixed  $builder
     * @param  int  $value
     * @return mixed
     */
    protected function offset($builder, $value)
    {
        return $builder->offset($value);
    }

    /**
     * Add a query term to the builder.
     *
     * @param  mixed  $builder
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @param  boolean  $not
     * @return $this
     */
    protected function addQueryTerm($builder, $column, $value, $boolean = 'and', $not = false)
    {
        list($key, $val) = $this->normalizeQueryTerm($column, $value);
        $searchable = $this->getSearchable($builder);

        if (!$searchable->isSearchable($key)) {
            return $this;
        }

        if ($searchable->isRelationAttribute($key)) {
            list($operator, $val, $callback) = $this->parseRelationValue($val, $not);
            $this->has($builder, $key, $operator, $val, $boolean, $callback);
        } elseif (is_null($val)) {
            $this->whereNull($builder, $key, $boolean, $not);
        } else {
            list($operator, $val) = $searchable->isDateAttribute($key)
                ? $this->parseDateValue($val, $not)
                : $this->parseValue($val, $not);

            if ($this->isRangeOperator($operator)) {
                $this->{'where' . ucfirst($operator)}($builder, $key, $val, $boolean, $not);
            } else {
                $this->where($builder, $key, $operator, $val, $boolean);
            }
        }

        return $this;
    }

    /**
     * Normalize a given term of the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return array
     */
    protected function normalizeQueryTerm($column, $value)
    {
        if (mb_strpos($column, '.') === false) {
            return [$column, $value];
        }

        $segments = explode('.', trim($column, '.'));
        $length = count($segments);

        if ($length < 2) {
            return [$column, $value];
        }

        $val = $value;

        for ($i = $length - 1; $i > 0; $i--) {
            $val = [$segments[$i] => $val];
        }

        return [$segments[0], $val];
    }

    /**
     * Get the searchable instance of the builder.
     *
     * @param  mixed  $builder
     * @return \Illusionist\Searcher\Contracts\Searchable
     */
    protected function getSearchable($builder)
    {
        return $builder->getModel();
    }

    /**
     * Parse the value of the relationship term.
     *
     * @param  mixed  $value
     * @param  boolean  $not
     * @return array
     */
    protected function parseRelationValue($value, $not = false)
    {
        if (is_numeric($value)) {
            $expression = ['=', $value];
        } elseif ($this->isOperatorExpression($value)) {
            $expression = $value;
        } elseif (is_array($value) && isset($value[0]) && $this->isOperatorExpression($value[0])) {
            $expression = array_shift($value);
            $constraints = $value;
        } else {
            $expression = ['>=', 1];
            $constraints = (array) $value;
        }

        $result = $this->normalizeRelationExpression(
            $this->whenReverseOperator($not, $expression[0]),
            $expression[1]
        );

        $result[] = empty($constraints) ? null : function ($builder) use ($constraints) {
            $this->addTerms($builder, $constraints);
        };

        return $result;
    }

    /**
     * Determine if the given expression is an operator expression.
     *
     * @param  mixed  $value
     * @return boolean
     */
    protected function isOperatorExpression($expression)
    {
        if (
            !is_array($expression) ||
            count($expression) !== 2 ||
            !isset($expression[0], $expression[1])
        ) {
            return false;
        }

        list($operator, $count) = $expression;

        return $this->isBasicOperator($operator) && is_numeric($count);
    }

    /**
     * Normalize a given expression for the relation.
     *
     * @param  string  $operator
     * @param  int  $count
     * @return array
     */
    protected function normalizeRelationExpression($operator, $count)
    {
        switch ($operator . $count) {
            case '>0':
            case '!=0':
            case '>=1':
                return ['>=', 1];
            case '<=0':
            case '=0':
            case '<1':
                return ['<', 1];
            default:
                return [$operator, $count];
        }
    }

    /**
     * Parse the value of the date term.
     *
     * @param  mixed  $value
     * @param  boolean  $not
     * @return array
     */
    protected function parseDateValue($value, $not = false)
    {
        list($operator, $val) = $this->parseValue($value, $not);

        try {
            if ($this->isRangeOperator($operator)) {
                return [
                    $operator,
                    array_map(static function ($datetime) {
                        return static::parseDate($datetime)->format(static::$dateFormat);
                    }, $val)
                ];
            }

            $date = static::parseDate($val, $format);
            $precision = $format
                ? static::getDateFormatPrecision($format)
                : static::getDatePrecision($date);

            if (in_array($precision, ['second', 'micro'], true)) {
                return [$operator, $date->format(static::$dateFormat)];
            }

            if (in_array($operator, ['<', '>='], true)) {
                return [
                    $operator,
                    static::adjustDateTime($date, $precision)->format(static::$dateFormat)
                ];
            }

            if (in_array($operator, ['>', '<='], true)) {
                return [
                    $operator,
                    static::adjustDateTime($date, $precision, true)->format(static::$dateFormat)
                ];
            }

            return [
                'between',
                [
                    static::adjustDateTime($date, $precision)->format(static::$dateFormat),
                    static::adjustDateTime($date, $precision, true)->format(static::$dateFormat)
                ]
            ];
        } catch (Exception $e) {
            return [$operator, $val];
        }
    }

    /**
     * Parse the value of the term.
     *
     * @param  mixed  $value
     * @param  boolean  $not
     * @return array
     */
    protected function parseValue($value, $not = false)
    {
        if (!is_array($value)) {
            return [$not ? '!=' : '=', $value];
        }

        $operator = reset($value);

        if ($this->isBasicOperator($operator) || $this->isLikeExpression($value)) {
            return [$this->whenReverseOperator($not, $operator), array_pop($value)];
        }

        if ($this->isRangeOperator($operator)) {
            return [$operator, count($value) === 2 ? array_pop($value) : array_slice($value, 1)];
        }

        return ['in', $value];
    }

    /**
     * Determine if the given expression is a like expression.
     *
     * @param  mixed  $expression
     * @return boolean
     */
    protected function isLikeExpression($expression)
    {
        if (
            !is_array($expression) ||
            count($expression) !== 2 ||
            !isset($expression[0], $expression[0])
        ) {
            return false;
        }

        list($operator, $value) = $expression;

        return $operator === 'like' &&
            mb_substr($value, 0, 1) === '%' &&
            mb_substr($value, -1) === '%';
    }

    /**
     * Determine if the given operator is a basic operator.
     *
     * @param  string  $operator
     * @return boolean
     */
    protected function isBasicOperator($operator)
    {
        return in_array($operator, ['=', '!=', '>', '>=', '<', '<='], true);
    }

    /**
     * Determine if the given operator is a range operator.
     *
     * @param  string  $operator
     * @return boolean
     */
    protected function isRangeOperator($operator)
    {
        return in_array($operator, ['in', 'between'], true);
    }

    /**
     * Reverse the operator if the given "condition" is true.
     *
     * @param  boolean  $condition
     * @param  string  $operator
     * @return string
     */
    protected function whenReverseOperator($condition, $operator)
    {
        if (!$condition) {
            return $operator;
        }

        switch ($operator) {
            case '=':
                return '!=';
            case '!=':
                return '=';
            case '>':
                return '<=';
            case '>=':
                return '<';
            case '<':
                return '>=';
            case '<=':
                return '>';
            case 'like':
                return 'not like';
            default:
                throw new InvalidArgumentException(sprintf('Unknown operator "%s".', $operator));
        }
    }

    /**
     * Add a relationship count / exists condition to the builder.
     *
     * @param  mixed  $builder
     * @param  string  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return mixed
     */
    protected function has($builder, $relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        return $builder->has($relation, $operator, $count, $boolean, $callback);
    }

    /**
     * Add a basic where clause to the builder.
     *
     * @param  mixed  $builder
     * @param  string|\Closure  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return mixed
     */
    protected function where($builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        return $builder->where($column, $operator, $value, $boolean);
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param  mixed  $builder
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool  $not
     * @return mixed
     */
    protected function whereNull($builder, $column, $boolean = 'and', $not = false)
    {
        return $builder->whereNull($column, $boolean, $not);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  mixed  $builder
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return mixed
     */
    protected function whereBetween($builder, $column, $values, $boolean = 'and', $not = false)
    {
        return $builder->whereBetween($column, $values, $boolean, $not);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  mixed  $builder
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return mixed
     */
    protected function whereIn($builder, $column, $values, $boolean = 'and', $not = false)
    {
        return $builder->whereIn($column, $values, $boolean, $not);
    }
}
