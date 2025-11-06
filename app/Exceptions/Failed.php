<?php

namespace App\Exceptions;

use Exception;

class Failed extends Exception
{
    public function __construct($message = "An error occurred", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
