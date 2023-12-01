<?php

namespace Expedition\Wpqb;

/**
 * @method static QueryBuilder select(...$columns)
 * @method static QueryBuilder distinct()
 * @method static QueryBuilder from($table)
 * @method static QueryBuilder update($table)
 * @method static QueryBuilder insert()
 * @method static QueryBuilder into($table)
 * @method static QueryBuilder values(...$values)
 * @method static QueryBuilder delete()
 * @method static QueryBuilder set($args)
 * @method static QueryBuilder join($table, $firstColumn, $operator, $secondColumn)
 * @method static QueryBuilder innerJoin($table, $firstColumn, $operator, $secondColumn)
 * @method static QueryBuilder leftJoin($table, $firstColumn, $operator, $secondColumn)
 * @method static QueryBuilder rightJoin($table, $firstColumn, $operator, $secondColumn)
 * @method static QueryBuilder crossJoin($table, $firstColumn = null, $operator = null, $secondColumn = null)
 * @method static QueryBuilder naturalJoin($table, $firstColumn = null, $operator = null, $secondColumn = null)
 * @method static QueryBuilder where(...$args)
 * @method static QueryBuilder andWhere(...$args)
 * @method static QueryBuilder orWhere(...$args)
 * @method static QueryBuilder groupBy($column)
 * @method static QueryBuilder having(...$args)
 * @method static QueryBuilder andHaving(...$args)
 * @method static QueryBuilder orHaving(...$args)
 * @method static QueryBuilder orderBy($column, $direction = 'ASC')
 * @method static QueryBuilder orderByDesc($column)
 * @method static QueryBuilder limit($limit)
 * @method static QueryBuilder offset($offset)
 * @method static string toSql()
 * @method static array get($output = 'OBJECT')
 * @method static int execute()
 */
abstract class Query
{
    protected static ?QueryBuilder $instance = null;

    /**
     * Magic method to forward static method calls to QueryBuilder.
     *
     * @param string       $name      The name of the method being called.
     * @param array<mixed> $arguments The arguments being passed to the method.
     * @return mixed The return value of the forwarded method call.
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        $instance = static::getInstance();
        $callable = [$instance, $name];

        // Check if the callable is valid
        if (!is_callable($callable)) {
            throw new \Exception("Method {$name} is not callable on " . get_class($instance));
        }

        return call_user_func_array($callable, $arguments);
    }

    /**
     * Returns an instance of QueryBuilder.
     *
     * @return QueryBuilder An instance of QueryBuilder.
     */
    protected static function getInstance(): QueryBuilder
    {
        if (null === static::$instance) {
            static::$instance = new QueryBuilder();
        }

        return static::$instance;
    }
}