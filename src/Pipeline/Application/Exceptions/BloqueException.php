<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application\Exceptions;

class BloqueException extends \Exception
{
    public function __construct(string $message, private readonly string $codigo, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function codigo(): string
    {
        return $this->codigo;
    }
}
