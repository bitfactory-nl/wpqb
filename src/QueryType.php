<?php

namespace Expedition\Wpqb;

enum QueryType: string
{
    case SELECT = 'select';
    case INSERT = 'insert';
    case UPDATE = 'update';
    case DELETE = 'delete';
}

class QueryType
{
    public const GROUP_BY = 'GROUP BY';
    public const HAVING = 'HAVING';
    public const ORDER_BY = 'ORDER BY';
    public const LIMIT = 'LIMIT';
    public const OFFSET = 'OFFSET';
}
