<?php

namespace Expedition\Wpqb\Exceptions;

use Exception;

class UnsupportedQueryTypeException extends Exception
{
    public function __construct(string $message = "Unsupported query type.", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
