<?php

namespace Expedition\Wpqb;

abstract class Query
{
    protected static ?QueryBuilder $instance = null;

    /**
     * @param string|array<string> ...$columns
     */
    public static function select(...$columns): QueryBuilder
    {
        return static::getInstance()->select(...$columns);
    }

    protected static function getInstance(): QueryBuilder
    {
        if (null === static::$instance) {
            static::$instance = new QueryBuilder();
        }

        return static::$instance;
    }

    public static function from(string $table): QueryBuilder
    {
        return static::getInstance()->from($table);
    }

    public static function where(string $column, string $operator, int|string $value): QueryBuilder
    {
        return static::getInstance()->where($column, $operator, $value);
    }

    public static function toSql(): string
    {
        return static::getInstance()->toSql();
    }

    /**
     * @return array<mixed>
     */
    public static function get(): array
    {
        return static::getInstance()->get();
    }

    public static function limit(int $limit): QueryBuilder
    {
        return static::getInstance()->limit($limit);
    }

    public static function join(string $table, string $firstColumn, string $operator, string $secondColumn): QueryBuilder
    {
        return static::getInstance()->join($table, $firstColumn, $operator, $secondColumn);
    }

    public static function innerJoin(string $table, string $firstColumn, string $operator, string $secondColumn): QueryBuilder
    {
        return static::getInstance()->innerJoin($table, $firstColumn, $operator, $secondColumn);
    }

    public static function leftJoin(string $table, string $firstColumn, string $operator, string $secondColumn): QueryBuilder
    {
        return static::getInstance()->leftJoin($table, $firstColumn, $operator, $secondColumn);
    }

    public static function rightJoin(string $table, string $firstColumn, string $operator, string $secondColumn): QueryBuilder
    {
        return static::getInstance()->rightJoin($table, $firstColumn, $operator, $secondColumn);
    }

    public static function crossJoin(string $table, ?string $firstColumn = null, ?string $operator = null, ?string $secondColumn = null): QueryBuilder
    {
        return static::getInstance()->crossJoin($table, $firstColumn, $operator, $secondColumn);
    }

    public static function distinct(): QueryBuilder
    {
        return static::getInstance()->distinct();
    }

    public static function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return static::getInstance()->orderBy($column, $direction);
    }

    public static function groupBy(string $column): QueryBuilder
    {
        return static::getInstance()->groupBy($column);
    }
}
