<?php

use Bowero\Wpqb\QueryBuilder;


it('can select all columns', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->select('*');

    expect($queryBuilder->getColumns())->toBe(['*']);
});

it('can select multiple columns with multiple arguments', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->select('id', 'name');

    expect($queryBuilder->getColumns())->toBe(['id', 'name']);
});

it('can select multiple columns with an array', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->select(['id', 'name']);

    expect($queryBuilder->getColumns())->toBe(['id', 'name']);
});

it('can select distinctly', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->distinct();

    expect($queryBuilder->getDistinct())->toBeTrue();
});

it('can set a limit', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->limit(10);

    expect($queryBuilder->getLimit())->toBe(10);
});

it('can set a table', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->from('wp_posts');

    expect($queryBuilder->getTable())->toBe('wp_posts');
});

it('can set a where clause', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->where('id', '=', 1);

    expect($queryBuilder->getWheres())->toBe([
        [
            'column' => 'id',
            'operator' => '=',
            'value' => 1,
        ],
    ]);
});

it('can set multiple where clauses', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->where('id', '=', 1);
    $queryBuilder->where('name', '=', 'John');

    expect($queryBuilder->getWheres())->toBe([
        [
            'column' => 'id',
            'operator' => '=',
            'value' => 1,
        ],
        [
            'column' => 'name',
            'operator' => '=',
            'value' => 'John',
        ],
    ]);
});

it('can set an order', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->orderBy('id', 'DESC');

    expect($queryBuilder->getOrders())->toBe([
        [
            'column' => 'id',
            'direction' => 'DESC',
        ],
    ]);
});

it('can set multiple orders', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->orderBy('id', 'DESC');
    $queryBuilder->orderBy('name', 'ASC');

    expect($queryBuilder->getOrders())->toBe([
        [
            'column' => 'id',
            'direction' => 'DESC',
        ],
        [
            'column' => 'name',
            'direction' => 'ASC',
        ],
    ]);
});

it('can set a join', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->join('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can set multiple joins', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->join('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');
    $queryBuilder->join('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
        [
            'type' => 'JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can set an inner join', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->innerJoin('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'INNER JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can set a left join', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->leftJoin('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'LEFT JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can set a right join', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->rightJoin('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'RIGHT JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can set a cross join', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->crossJoin('wp_postmeta');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'CROSS JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => null,
            'operator' => null,
            'secondColumn' => null,
        ],
    ]);
});

it('can set a cross join with a first column', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->crossJoin('wp_postmeta', 'wp_postmeta.post_id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'CROSS JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => null,
            'secondColumn' => null,
        ],
    ]);
});

it('can set a cross join with a first column and an operator', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->crossJoin('wp_postmeta', 'wp_postmeta.post_id', '=');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'CROSS JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => null,
        ],
    ]);
});

it('can set a cross join with a first column, an operator and a second column', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->crossJoin('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id');

    expect($queryBuilder->getJoins())->toBe([
        [
            'type' => 'CROSS JOIN',
            'table' => 'wp_postmeta',
            'firstColumn' => 'wp_postmeta.post_id',
            'operator' => '=',
            'secondColumn' => 'wp_posts.id',
        ],
    ]);
});

it('can return the SQL for a query', function () {
    $queryBuilder = new QueryBuilder();

    $sql = $queryBuilder->select('id', 'name')
        ->distinct()
        ->from('wp_posts')
        ->innerJoin('wp_postmeta', 'wp_postmeta.post_id', '=', 'wp_posts.id')
        ->where('post_date', '>', '2023-10-11')
        ->orderBy('post_date', 'DESC')
        ->limit(20)
        ->toSql();

    expect($sql)->toBe('SELECT DISTINCT id, name FROM wp_posts INNER JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.id WHERE post_date > \'2023-10-11\' ORDER BY post_date DESC LIMIT 20');
});
