<?php

namespace Ddys\Laravel\Exceptions;

use RuntimeException;
use Throwable;

class DdysException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 0,
        protected string $method = '',
        protected string $endpoint = '',
        protected mixed $payload = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function payload(): mixed
    {
        return $this->payload;
    }
}

