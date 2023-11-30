<?php

namespace Expedition\Wpqb\Exceptions;

use Exception;

class NoQueryException extends Exception
{
    public function __construct(string $message = "No query has been set", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
