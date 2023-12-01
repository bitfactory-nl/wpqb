<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;
use Expedition\Wpqb\QueryType;

abstract class Grammar
{
    /**
     * @throws NoQueryException
     * @throws UnsupportedQueryTypeException
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
     * @return array<mixed>
     */
    abstract public function getResults(QueryBuilder $query, string $output = 'OBJECT'): array;

    /**
     * Execute a query. Returns the number of rows affected.
     *
     * @param QueryBuilder $query
     * @return int
     */
    abstract public function execute(QueryBuilder $query): int;

    /**
     * Generate the SQL for a SELECT query.
     *
     * @param QueryBuilder $query
     * @return string
     */
    abstract public function generateSelectSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for an UPDATE query.
     *
     * @param QueryBuilder $query
     * @return string
     */
    abstract public function generateUpdateSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for an INSERT query.
     *
     * @param QueryBuilder $query
     * @return string
     */
    abstract public function generateInsertSql(QueryBuilder $query): string;

    /**
     * Generate the SQL for a DELETE query.
     *
     * @param QueryBuilder $query
     * @return string
     */
    abstract public function generateDeleteSql(QueryBuilder $query): string;
}
