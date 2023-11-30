<?php

namespace Expedition\Wpqb\Exceptions;

use Exception;

class NoResultsException extends Exception
{
    public function __construct(string $message = "No results were found.", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
