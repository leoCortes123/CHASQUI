<?php

declare(strict_types=1);

use Chasqui\LlmGateway\Infrastructure\OpenAiGateway;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Infrastructure\Blocks\PromptSimpleExecutor;

it('reemplaza aliases del contexto en el prompt de usuario', function () {
    $gateway = Mockery::mock(OpenAiGateway::class);
    $gateway->expects('completar')
        ->withArgs(fn ($config) => str_contains($config->promptUsuario, 'reseña negativa'))
        ->andReturn('Estimado cliente, lamentamos...');

    $executor = new PromptSimpleExecutor($gateway);

    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'telegram',
        mensajeOriginal: 'reseña negativa',
    );
    $ctx->guardar('datos_resena', 'reseña negativa');

    $resultado = $executor->ejecutar([
        'proveedor' => 'openai',
        'modelo' => 'gpt-4o-mini',
        'prompt_sistema' => 'Eres un asistente',
        'prompt_usuario_template' => 'Responde a esta: {{datos_resena}}',
        'temperatura' => 0.7,
        'max_tokens' => 500,
    ], $ctx);

    expect($resultado)->toBeString()->toContain('Estimado');
});
