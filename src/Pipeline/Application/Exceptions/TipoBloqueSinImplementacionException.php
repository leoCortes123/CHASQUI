<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application\Exceptions;

class TipoBloqueSinImplementacionException extends \Exception
{
    public function __construct(string $tipoBloque)
    {
        parent::__construct("No hay BlockExecutor registrado para el tipo de bloque '{$tipoBloque}'");
    }
}
