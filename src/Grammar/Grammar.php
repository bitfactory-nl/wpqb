<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;
use Expedition\Wpqb\QueryType;

abstract class Grammar
{
    /**
     * @return string
     * @throws UnsupportedQueryTypeException
     *
     * @throws NoQueryException
     */
    public function generateSql(QueryBuilder $query): string
    {
        switch ($query->getQueryType()) {
            case QueryType::SELECT:
                return $this->generateSelectSql($query);
            case QueryType::UPDATE:
                return $this->generateUpdateSql($query);
            case QueryType::INSERT:
                return $this->generateInsertSql($query);
            case QueryType::DELETE:
                return $this->generateDeleteSql($query);
            case null:
                throw new NoQueryException();
            default:
                throw new UnsupportedQueryTypeException();
        }
    }

    /**
     * Get the results of a query.
     *
     * @param QueryBuilder $query
     * @param string       $output
     *
     * @return array<mixed>
     */
    abstract public function getResults(QueryBuilder $query, string $output = 'OBJECT'): array;

    /**
     * Execute a query. Returns the number of rows affected.
     *
     * @param QueryBuilder $query
     *
     * @return int
     */
    abstract public function execute(QueryBuilder $query): int;

    /**
     * Generate the SQL for a SELECT query.
     *
     * @param QueryBuilder $query
     *
     * @return string
     */
    abstract public function generateSelectSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for an UPDATE query.
     *
     * @param QueryBuilder $query
     *
     * @return string
     */
    abstract public function generateUpdateSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for an INSERT query.
     *
     * @param QueryBuilder $query
     *
     * @return string
     */
    abstract public function generateInsertSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for a DELETE query.
     *
     * @param QueryBuilder $query
     *
     * @return string
     */
    abstract public function generateDeleteSql(QueryBuilder $query): string;

    public function compileQuery(Query $query): string
    {
        $sql = [];

        foreach ($query->getParts() as $type => $part) {
            switch ($type) {
                // ... existing cases ...
                case QueryType::GROUP_BY:
                    $sql[] = $this->compileGroupBy($part);
                    break;
                case QueryType::HAVING:
                    $sql[] = $this->compileHaving($part);
                    break;
                case QueryType::ORDER_BY:
                    $sql[] = $this->compileOrderBy($part);
                    break;
                case QueryType::LIMIT:
                    $sql[] = $this->compileLimit($part);
                    break;
                case QueryType::OFFSET:
                    $sql[] = $this->compileOffset($part);
                    break;
            }
        }

        return implode(' ', array_filter($sql));
    }

    abstract protected function compileGroupBy(array $groups): string;
    abstract protected function compileHaving(array $having): string;
    abstract protected function compileOrderBy(array $order): string;
    abstract protected function compileLimit(int $limit): string;
    abstract protected function compileOffset(int $offset): string;
}
