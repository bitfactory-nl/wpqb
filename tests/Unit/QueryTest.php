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
