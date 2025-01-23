<?php

use Expedition\Wpqb\Query;

afterEach(function () {
    $reflection = new ReflectionClass(Query::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null);
});

it('always returns a single instance of QueryBuilder', function () {
    $reflection = new ReflectionClass(Query::class);
    $getInstanceMethod = $reflection->getMethod('getInstance');
    $getInstanceMethod->setAccessible(true);

    $instance1 = $getInstanceMethod->invoke(null);
    $instance2 = $getInstanceMethod->invoke(null);

    expect($instance1)->toBe($instance2);
});

it('can forward static method calls to QueryBuilder', function () {
    $result = Query::select('id', 'name')
        ->from('wp_posts')
        ->where('post_status', '=', 'publish')
        ->toSql();

    expect($result)->toBe('SELECT id, name FROM wp_posts WHERE post_status = \'publish\'');
});

it('throws an exception for non-existent methods', function () {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Method nonExistentMethod is not callable on Expedition\Wpqb\QueryBuilder');

    Query::nonExistentMethod();
});

it('can create a complex query using the facade', function () {
    $result = Query::select('wp_posts.ID', 'wp_posts.post_title')
        ->from('wp_posts')
        ->leftJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')
        ->where('post_status', '=', 'publish')
        ->andWhere('post_type', '=', 'post')
        ->groupBy('wp_posts.ID')
        ->orderBy('wp_posts.post_date', 'DESC')
        ->limit(10)
        ->toSql();

    expect($result)->toBe(
        'SELECT wp_posts.ID, wp_posts.post_title ' .
        'FROM wp_posts ' .
        'LEFT JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id ' .
        'WHERE post_status = \'publish\' ' .
        'AND post_type = \'post\' ' .
        'GROUP BY wp_posts.ID ' .
        'ORDER BY wp_posts.post_date DESC ' .
        'LIMIT 10'
    );
});

it('maintains singleton instance across multiple calls', function () {
    Query::select('id')->from('wp_posts');
    $result = Query::where('post_status', '=', 'publish')->toSql();

    expect($result)->toBe('SELECT id FROM wp_posts WHERE post_status = \'publish\'');
});

it('can execute an insert query through the facade', function () {
    $result = Query::insert()
        ->into('wp_posts')
        ->values([
            'post_title' => 'Test Post',
            'post_content' => 'Test Content'
        ])
        ->toSql();

    expect($result)->toBe(
        'INSERT INTO wp_posts (post_title, post_content) ' .
        'VALUES (\'Test Post\', \'Test Content\')'
    );
});
