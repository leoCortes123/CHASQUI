<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application\Exceptions;

class EjecucionFallidaException extends \Exception
{
    public function __construct(string $ejecucionId, string $alias, ?\Throwable $previous = null)
    {
        parent::__construct("La ejecución {$ejecucionId} falló en el bloque '{$alias}'", 0, $previous);
    }
}
