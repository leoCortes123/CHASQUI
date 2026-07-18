<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application;

final readonly class ConfirmacionPendiente
{
    public function __construct(
        public string $mensaje,
        /** @var array<int, string> */
        public array $opciones,
    ) {}
}
