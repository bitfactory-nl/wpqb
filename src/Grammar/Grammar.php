<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;
use Expedition\Wpqb\QueryType;

abstract class Grammar
{
    /**
     * @return array<mixed>
     */
    abstract public function getResults(QueryBuilder $query, string $output = 'OBJECT'): array;

    abstract public function execute(QueryBuilder $query): int;

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

    abstract public function generateSelectSql(QueryBuilder $query): string;

    abstract public function generateUpdateSql(QueryBuilder $query): string;

    abstract public function generateInsertSql(QueryBuilder $query): string;

    abstract public function generateDeleteSql(QueryBuilder $query): string;
}
