<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\NoResultsException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;

class MysqlGrammar extends Grammar
{
    protected object $wpdb;

    public function __construct(object $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @return array<mixed>
     * @throws NoResultsException
     * @throws UnsupportedQueryTypeException
     */
    public function getResults(QueryBuilder $query): array
    {
        global $wpdb;

        try {
            $sql = $this->generateSql($query);
        } catch (NoQueryException) {
            throw new NoResultsException();
        }

        $results = $wpdb->get_results($sql);

        if (empty($results)) {
            throw new NoResultsException();
        }

        return $results;
    }

    /**
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

        $sqlWithPlaceholders = implode(' ', $sqlParts);

        $bindings    = $this->generateBindings($query);
        $preparedSql = $wpdb->prepare($sqlWithPlaceholders, ...$bindings);

        if (empty($preparedSql)) {
            throw new NoQueryException();
        }

        return $preparedSql;
    }

    /**
     * @param array<string> $columns
     */
    protected function columnsToSql(array $columns): string
    {
        return implode(', ', $columns);
    }

    /**
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
     * @param array<array<int|string>> $having
     * @return array{string, array<int|string>}
     */
    protected function havingToSql(array $having): array
    {
        $havingSql = [];
        $bindings  = [];

        foreach ($having as $have) {
            $havingSql[] = $have['column'] . ' ' . $have['operator'] . ' %s';
            $bindings[]  = $have['value'];
        }

        return ['HAVING ' . implode(' AND ', $havingSql), $bindings];
    }

    /**
     * @param array<array<string>> $orders
     */
    protected function ordersToSql(array $orders): string
    {
        return 'ORDER BY ' . implode(', ', array_map(function ($order) {
            return "{$order['column']} {$order['direction']}";
        }, $orders));
    }

    /**
     * @return array<mixed>
     */
    public function generateBindings(QueryBuilder $query): array
    {
        $bindings = [];

        $wheres = $query->getWheres();
        if (!empty($wheres)) {
            $bindings = $this->wheresToSql($wheres)[1];
        }

        $havings = $query->getHavings();
        if (!empty($havings)) {
            $bindings = array_merge($bindings, $this->havingToSql($havings)[1]);
        }

        return $bindings;
    }

    /**
     * @param array<array<?string>> $joins
     */
    protected function joinsToSql(array $joins): string
    {
        return implode(' ', array_map(function ($join) {
            return "{$join['type']} {$join['table']} ON {$join['firstColumn']} {$join['operator']} {$join['secondColumn']}";
        }, $joins));
    }
}
