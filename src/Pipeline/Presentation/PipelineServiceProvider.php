<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Presentation;

use Chasqui\LlmGateway\Infrastructure\OpenAiGateway;
use Chasqui\Pipeline\Infrastructure\BlockExecutorRegistry;
use Chasqui\Pipeline\Infrastructure\Blocks\ConfirmacionExecutor;
use Chasqui\Pipeline\Infrastructure\Blocks\EntregaExecutor;
use Chasqui\Pipeline\Infrastructure\Blocks\PromptSimpleExecutor;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

final class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenAiGateway::class, fn () => new OpenAiGateway(
            new Client,
            (string) config('services.openai.api_key'),
        ));

        $this->app->singleton(BlockExecutorRegistry::class, fn ($app) => new BlockExecutorRegistry([
            'prompt_simple' => $app->make(PromptSimpleExecutor::class),
            'entrega' => $app->make(EntregaExecutor::class),
            'confirmacion' => $app->make(ConfirmacionExecutor::class),
        ]));
    }
}
