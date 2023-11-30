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
    abstract public function getResults(QueryBuilder $query): array;

    /**
     * @throws NoQueryException
     * @throws UnsupportedQueryTypeException
     */
    public function generateSql(QueryBuilder $query): string
    {
        switch($query->getQueryType()) {
            case QueryType::SELECT:
                return $this->generateSelectSql($query);
            case null:
                throw new NoQueryException();
            default:
                throw new UnsupportedQueryTypeException();
        }
    }

    abstract public function generateSelectSql(QueryBuilder $query): string;
}
