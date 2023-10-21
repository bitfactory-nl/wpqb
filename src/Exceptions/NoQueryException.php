<?php

namespace Bowero\Wpqb\Exceptions;

use Exception;

class NoQueryException extends Exception
{
    public function __construct(string $message = "No query has been specified.", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
