<?php

namespace Expedition\Wpqb\Exceptions;

use Exception;

class InvalidQueryException extends Exception
{
    public function __construct(string $message = "Invalid query", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
