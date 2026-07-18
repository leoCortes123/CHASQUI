<?php

declare(strict_types=1);

namespace Chasqui\LlmGateway\Infrastructure\Exceptions;

class LlmException extends \Exception
{
    public function __construct(private readonly string $codigo, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function codigo(): string
    {
        return $this->codigo;
    }
}
