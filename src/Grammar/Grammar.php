<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\QueryBuilder;

abstract class Grammar
{
    /**
     * @return array<mixed>
     */
    abstract public function getResults(QueryBuilder $query): array;

    abstract public function generateSql(QueryBuilder $query): string;
}
