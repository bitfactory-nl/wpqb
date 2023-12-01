<?php

use Expedition\Wpqb\Exceptions\InvalidQueryException;
use Expedition\Wpqb\Query;

it('should throw an exception when no query is set', function () {
    $this->expectException(InvalidQueryException::class);

    Query::toSql();
});