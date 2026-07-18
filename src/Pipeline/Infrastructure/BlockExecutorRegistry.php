<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure;

use Chasqui\Pipeline\Application\Contracts\BlockExecutor;
use Chasqui\Pipeline\Application\Exceptions\TipoBloqueSinImplementacionException;

final class BlockExecutorRegistry
{
    /** @param array<string, BlockExecutor> $executors */
    public function __construct(private readonly array $executors) {}

    public function resolver(string $tipoBloque): BlockExecutor
    {
        return $this->executors[$tipoBloque]
            ?? throw new TipoBloqueSinImplementacionException($tipoBloque);
    }
}
