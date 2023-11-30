<?php

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Query;

it('should throw an exception when no query is set', function () {
    $this->expectException(NoQueryException::class);

    Query::toSql();
});