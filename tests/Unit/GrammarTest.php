<?php

use Expedition\Wpqb\Grammar\MysqlGrammar;
use Expedition\Wpqb\QueryBuilder;

it('gets results', function () {
    $query = new QueryBuilder();
    $query->select('ID', 'post_title')
        ->from('wp_posts')
        ->where('post_type', '=', 'post')
        ->where('post_status', '=', 'publish');

    $grammar = new MysqlGrammar($GLOBALS['wpdb']);
    $results = $grammar->getResults($query);

    expect($results)->toBeArray();
    expect($results)->not->toBeEmpty();
});
