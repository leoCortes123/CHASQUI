<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Blocks;

use Chasqui\Pipeline\Application\ConfirmacionPendiente;
use Chasqui\Pipeline\Application\Contracts\BlockExecutor;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Infrastructure\Blocks\Concerns\ReemplazaAliasesEnTexto;

final class ConfirmacionExecutor implements BlockExecutor
{
    use ReemplazaAliasesEnTexto;

    public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
    {
        $mensaje = $this->reemplazarAliases($configuracion['mensaje_template'], $contexto);

        return new ConfirmacionPendiente($mensaje, $configuracion['opciones'] ?? []);
    }
}
