<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Blocks;

use Chasqui\LlmGateway\Infrastructure\Exceptions\LlmException;
use Chasqui\LlmGateway\Infrastructure\OpenAiGateway;
use Chasqui\LlmGateway\Infrastructure\PromptConfig;
use Chasqui\Pipeline\Application\Contracts\BlockExecutor;
use Chasqui\Pipeline\Application\Exceptions\BloqueException;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Infrastructure\Blocks\Concerns\ReemplazaAliasesEnTexto;

final class PromptSimpleExecutor implements BlockExecutor
{
    use ReemplazaAliasesEnTexto;

    public function __construct(private readonly OpenAiGateway $gateway) {}

    public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
    {
        $promptUsuario = $this->reemplazarAliases($configuracion['prompt_usuario_template'], $contexto);

        $config = new PromptConfig(
            proveedor: $configuracion['proveedor'],
            modelo: $configuracion['modelo'],
            promptSistema: $configuracion['prompt_sistema'],
            promptUsuario: $promptUsuario,
            temperatura: $configuracion['temperatura'] ?? 0.7,
            maxTokens: $configuracion['max_tokens'] ?? 1000,
            formatoRespuesta: $configuracion['formato_respuesta'] ?? 'texto',
        );

        try {
            return $this->gateway->completar($config);
        } catch (LlmException $e) {
            throw new BloqueException($e->getMessage(), $e->codigo(), $e);
        }
    }
}
