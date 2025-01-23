<?php

use Expedition\Wpqb\Exceptions\InvalidQueryException;
use Expedition\Wpqb\Query;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;

it('should throw an exception when no query is set', function () {
    $this->expectException(InvalidQueryException::class);

    Query::toSql();
});

it('should throw an exception when trying to execute an unsupported query type', function () {
    $this->expectException(UnsupportedQueryTypeException::class);

    $queryBuilder = new QueryBuilder();
    $queryBuilder->groupBy('id')->toSql();
});

it('should throw an exception when trying to get results without a select query', function () {
    $this->expectException(InvalidQueryException::class);

    $queryBuilder = new QueryBuilder();
    $queryBuilder->from('wp_posts')->get();
});

it('should throw an exception when trying to execute update without a table', function () {
    $this->expectException(InvalidQueryException::class);

    $queryBuilder = new QueryBuilder();
    $queryBuilder->update('')->set(['column' => 'value'])->toSql();
});

it('should throw an exception when trying to insert without values', function () {
    $this->expectException(InvalidQueryException::class);

    $queryBuilder = new QueryBuilder();
    $queryBuilder->insert()->into('wp_posts')->toSql();
});

it('should throw an exception when trying to delete without a table', function () {
    $this->expectException(InvalidQueryException::class);

    $queryBuilder = new QueryBuilder();
    $queryBuilder->delete()->toSql();
});