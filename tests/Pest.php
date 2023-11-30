<?php

uses()->beforeEach(function () {
    $wpdb = Mockery::mock('stdObject'); // Mocking a standard object

    $wpdb->shouldReceive('prepare')
        ->andReturnUsing(function ($query, ...$args) {
            foreach ($args as $key => $value) {
                $query = str_replace('%s', "'{$value}'", $query);
            }
            return $query;
        });

    $wpdb->shouldReceive('get_results')
        ->andReturnUsing(
            function ($query) {
                return [
                    (object) [
                        'ID' => 1,
                        'post_title' => 'Hello World',
                    ],
                    (object) [
                        'ID' => 2,
                        'post_title' => 'Hello World 2',
                    ],
                ];
            }
        );

    $wpdb->shouldReceive('query')
        ->andReturnUsing(
            function ($query) {
                return 2;
            }
        );

    $GLOBALS['wpdb'] = $wpdb;
})->in(__DIR__);

uses()->afterEach(function () {
    Mockery::close();
})->in(__DIR__);
