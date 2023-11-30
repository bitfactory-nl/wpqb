<?php

use Expedition\Wpqb\Grammar\MysqlGrammar;
use Expedition\Wpqb\QueryBuilder;

it('gets results', function () {
    $query = new QueryBuilder();
    $query->select('ID', 'post_title')
        ->from('wp_posts')
        ->where('post_type', '=', 'post')
        ->where('post_status', '=', 'publish')
        ->groupBy('ID')
        ->having('ID', '>', 1)
        ->limit(10)
        ->offset(5);

    $grammar = new MysqlGrammar($GLOBALS['wpdb']);
    $results = $grammar->getResults($query);

    expect($results)->toBeArray();
    expect($results)->not->toBeEmpty();
});

it('can update', function () {
    $query = new QueryBuilder();
    $query->update('wp_posts')
        ->set('post_title', 'Test')
        ->where('ID', '=', 1)
        ->orderBy('ID', 'DESC')
        ->limit(1);

    $grammar = new MysqlGrammar($GLOBALS['wpdb']);
    $results = $grammar->execute($query);

    expect($results)->toBeInt();
    expect($results)->toBeGreaterThan(0);
});

it('can delete', function () {
    $query = new QueryBuilder();
    $query->delete()
        ->from('wp_posts')
        ->where('ID', '=', 1)
        ->orderBy('ID', 'DESC')
        ->limit(1);

    $grammar = new MysqlGrammar($GLOBALS['wpdb']);
    $results = $grammar->execute($query);

    expect($results)->toBeInt();
    expect($results)->toBeGreaterThan(0);
});