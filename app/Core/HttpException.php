<?php

namespace App\Core;

use Exception;

class HttpException extends Exception
{
    protected int $statusCode;

    public function __construct(int $statusCode, string $message = '', \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
