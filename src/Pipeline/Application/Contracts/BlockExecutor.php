<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application\Contracts;

use Chasqui\Pipeline\Application\ExecutionContext;

interface BlockExecutor
{
    /**
     * @param  array<string, mixed>  $configuracion  Config JSON del bloque
     * @return mixed Resultado a guardar en el contexto con el alias del bloque
     */
    public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed;
}
