<?php

declare(strict_types=1);

namespace Chasqui\LlmGateway\Infrastructure;

use Chasqui\LlmGateway\Infrastructure\Exceptions\LlmException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class OpenAiGateway
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $apiKey,
    ) {}

    public function completar(PromptConfig $config): string
    {
        try {
            $response = $this->http->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $config->modelo,
                    'messages' => [
                        ['role' => 'system', 'content' => $config->promptSistema],
                        ['role' => 'user', 'content' => $config->promptUsuario],
                    ],
                    'temperature' => $config->temperatura,
                    'max_tokens' => $config->maxTokens,
                    'response_format' => [
                        'type' => $config->formatoRespuesta === 'json' ? 'json_object' : 'text',
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new LlmException('LLM_TIMEOUT', 'Fallo al llamar a OpenAI: '.$e->getMessage(), $e);
        }

        $body = json_decode((string) $response->getBody(), true);
        $contenido = $body['choices'][0]['message']['content'] ?? null;

        if (! is_string($contenido) || $contenido === '') {
            throw new LlmException('LLM_EMPTY_RESPONSE', 'OpenAI devolvió una respuesta vacía');
        }

        return $contenido;
    }
}
