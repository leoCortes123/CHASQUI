<?php

declare(strict_types=1);

namespace Chasqui\LlmGateway\Infrastructure;

final readonly class PromptConfig
{
    public function __construct(
        public string $proveedor,
        public string $modelo,
        public string $promptSistema,
        public string $promptUsuario,
        public float $temperatura = 0.7,
        public int $maxTokens = 1000,
        public string $formatoRespuesta = 'texto',
    ) {}
}
