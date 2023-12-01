<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\NoResultsException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;
use Exception;

class MysqlGrammar extends Grammar
{
    /**
     * The WordPress database object. Used for querying the database and
     * escaping values native to WordPress. This variable is needed for testing
     * purposes.
     *
     * @var object $wpdb
     */
    protected object $wpdb;

    /**
     * Create a new instance of the MySQL grammar.
     *
     * @param object $wpdb
     * @return void
     */
    public function __construct(object $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Get results from a query. If no results are found, throw an exception.
     *
     * @param QueryBuilder $query
     * @param string       $output
     * @return array<mixed>
     * @throws NoResultsException
     * @throws UnsupportedQueryTypeException
     */
    public function getResults(QueryBuilder $query, $output = 'OBJECT'): array
    {
        global $wpdb;

        try {
            $sql = $this->generateSql($query);
        } catch (NoQueryException) {
            throw new NoResultsException();
        }

        $results = $wpdb->get_results($sql, $output);

        if (empty($results)) {
            throw new NoResultsException();
        }

        return $results;
    }

    /**
     * Execute a query. Returns the number of rows affected.
     *
     * @param QueryBuilder $query
     * @return int
     */
    public function execute(QueryBuilder $query): int
    {
        global $wpdb;

        try {
            $sql = $this->generateSql($query);
        } catch (Exception) {
            return 0;
        }

        return $wpdb->query($sql);
    }

    /**
     * Generate the SQL for a query.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NoQueryException
     */
    public function generateSelectSql(QueryBuilder $query): string
    {
        global $wpdb;

        $sqlParts = [];

        $sqlParts[] = 'SELECT ' . ($query->getDistinct() ? 'DISTINCT ' : '') . $this->columnsToSql($query->getColumns());
        $sqlParts[] = 'FROM ' . $query->getTable();

        $joins = $query->getJoins();
        if (!empty($joins)) {
            $sqlParts[] = $this->joinsToSql($joins);
        }

        $wheres = $query->getWheres();
        if (!empty($wheres)) {
            $sqlParts[] = $this->wheresToSql($wheres)[0];
        }

        $groups = $query->getGroupBy();
        if (!empty($groups)) {
            $sqlParts[] = 'GROUP BY ' . implode(', ', $groups);
        }

        $havings = $query->getHavings();
        if (!empty($havings)) {
            $sqlParts[] = $this->havingToSql($havings)[0];
        }

        $orders = $query->getOrders();
        if (!empty($orders)) {
            $sqlParts[] = $this->ordersToSql($orders);
        }

        if (!empty($query->getLimit())) {
            $sqlParts[] = 'LIMIT ' . $query->getLimit();
        }

        if (!empty($query->getOffset())) {
            $sqlParts[] = 'OFFSET ' . $query->getOffset();
        }

        $sqlWithPlaceholders = implode(' ', $sqlParts);

        $bindings = $this->generateBindings($query);
        $preparedSql = $wpdb->prepare($sqlWithPlaceholders, ...$bindings);

        if (empty($preparedSql)) {
            throw new NoQueryException();
        }

        return $preparedSql;
    }

    /**
     * Generate the SQL for an UPDATE query.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NoQueryException
     */
    public function generateUpdateSql(QueryBuilder $query): string
    {
        global $wpdb;

        $sqlParts = [];

        $sqlParts[] = 'UPDATE ' . $query->getTable();

        $sets = $query->getSets();
        if (!empty($sets)) {
            $sqlParts[] = 'SET ' . $this->setsToSql($sets)[0];
        }

        $wheres = $query->getWheres();
        if (!empty($wheres)) {
            $sqlParts[] = $this->wheresToSql($wheres)[0];
        }

        $orders = $query->getOrders();
        if (!empty($orders)) {
            $sqlParts[] = $this->ordersToSql($orders);
        }

        if (!empty($query->getLimit())) {
            $sqlParts[] = 'LIMIT ' . $query->getLimit();
        }

        $sqlWithPlaceholders = implode(' ', $sqlParts);

        $bindings = $this->generateBindings($query);
        $preparedSql = $wpdb->prepare($sqlWithPlaceholders, ...$bindings);

        if (empty($preparedSql)) {
            throw new NoQueryException();
        }

        return $preparedSql;
    }

    /**
     * Generate the SQL for an INSERT query.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NoQueryException
     */
    public function generateInsertSql(QueryBuilder $query): string
    {
        global $wpdb;

        $sqlParts = [];

        $sqlParts[] = 'INSERT INTO ' . $query->getTable();

        $values = $query->getValues();

        $sqlParts[] = '(' . implode(', ', array_keys($values)) . ')';

        if (!empty($values)) {
            $sqlParts[] = 'VALUES (' . implode(', ', array_fill(0, count($values), '%s')) . ')';
        }

        $sqlWithPlaceholders = implode(' ', $sqlParts);

        $bindings = $this->generateBindings($query);
        $preparedSql = $wpdb->prepare($sqlWithPlaceholders, ...$bindings);

        if (empty($preparedSql)) {
            throw new NoQueryException();
        }

        return $preparedSql;
    }

    /**
     * Generate the SQL for a DELETE query.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NoQueryException
     */
    public function generateDeleteSql(QueryBuilder $query): string
    {
        global $wpdb;

        $sqlParts = [];

        $sqlParts[] = 'DELETE FROM ' . $query->getTable();

        $wheres = $query->getWheres();
        if (!empty($wheres)) {
            $sqlParts[] = $this->wheresToSql($wheres)[0];
        }

        $orders = $query->getOrders();
        if (!empty($orders)) {
            $sqlParts[] = $this->ordersToSql($orders);
        }

        if (!empty($query->getLimit())) {
            $sqlParts[] = 'LIMIT ' . $query->getLimit();
        }

        $sqlWithPlaceholders = implode(' ', $sqlParts);

        $bindings = $this->generateBindings($query);
        $preparedSql = $wpdb->prepare($sqlWithPlaceholders, ...$bindings);

        if (empty($preparedSql)) {
            throw new NoQueryException();
        }

        return $preparedSql;
    }

    /**
     * Return the SQL for the columns to select.
     *
     * @param array<string> $columns
     * @return string
     */
    protected function columnsToSql(array $columns): string
    {
        return implode(', ', $columns);
    }

    /**
     * Return an array of the SQL and bindings for the WHERE part of a query.
     *
     * @param array<array<int|string>> $wheres
     * @return array{string, array<int|string>}
     */
    protected function wheresToSql(array $wheres): array
    {
        $whereSql = [];
        $bindings = [];

        foreach ($wheres as $where) {
            $whereSql[] = $where['column'] . ' ' . $where['operator'] . ' %s';
            $bindings[] = $where['value'];
        }

        return ['WHERE ' . implode(' AND ', $whereSql), $bindings];
    }

    /**
     * Return an array of the SQL and bindings for the HAVING part of a query.
     *
     * @param array<array<int|string>> $having
     * @return array{string, array<int|string>}
     */
    protected function havingToSql(array $having): array
    {
        $havingSql = [];
        $bindings = [];

        foreach ($having as $have) {
            $havingSql[] = $have['column'] . ' ' . $have['operator'] . ' %s';
            $bindings[] = $have['value'];
        }

        return ['HAVING ' . implode(' AND ', $havingSql), $bindings];
    }

    /**
     * Return the SQL for ordering the results.
     *
     * @param array<array<string>> $orders
     * @return string
     */
    protected function ordersToSql(array $orders): string
    {
        return 'ORDER BY ' . implode(', ', array_map(function ($order) {
                return "{$order['column']} {$order['direction']}";
            }, $orders));
    }

    /**
     * Return an array of the SQL and bindings for the SET part of an UPDATE
     * query.
     *
     * @param array<string, int|string> $sets
     * @return array{string, array<int|string>}
     */
    protected function setsToSql(array $sets): array
    {
        $setSql = [];
        $bindings = [];

        foreach ($sets as $column => $value) {
            $setSql[] = $column . ' = %s';
            $bindings[] = $value;
        }

        return [implode(', ', $setSql), $bindings];
    }

    /**
     * Generate the bindings for a query. This is used for preparing the SQL
     * statement. The bindings are used to replace the placeholders in the SQL
     * statement with the actual values.
     *
     * The order of the bindings is important. The order of the bindings must
     * match the order of the placeholders in the SQL statement.
     *
     * @param QueryBuilder $query
     * @return array<mixed>
     */
    public function generateBindings(QueryBuilder $query): array
    {
        $bindings = [];

        $sets = $query->getSets();
        if (!empty($sets)) {
            $bindings = array_merge($bindings, $this->setsToSql($sets)[1]);
        }

        $values = $query->getValues();
        if (!empty($values)) {
            $bindings = array_merge($bindings, array_values($values));
        }

        $wheres = $query->getWheres();
        if (!empty($wheres)) {
            $bindings = array_merge($bindings, $this->wheresToSql($wheres)[1]);
        }

        $havings = $query->getHavings();
        if (!empty($havings)) {
            $bindings = array_merge($bindings, $this->havingToSql($havings)[1]);
        }

        return $bindings;
    }

    /**
     * Return the SQL for joining tables.
     *
     * @param array<array<?string>> $joins
     * @return string
     */
    protected function joinsToSql(array $joins): string
    {
        return implode(' ', array_map(function ($join) {
            return "{$join['type']} {$join['table']} ON {$join['firstColumn']} {$join['operator']} {$join['secondColumn']}";
        }, $joins));
    }
}
