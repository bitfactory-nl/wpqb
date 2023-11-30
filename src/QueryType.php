<?php

namespace Expedition\Wpqb;

enum QueryType: string
{
    case SELECT = 'select';
    case INSERT = 'insert';
    case UPDATE = 'update';
    case DELETE = 'delete';
}