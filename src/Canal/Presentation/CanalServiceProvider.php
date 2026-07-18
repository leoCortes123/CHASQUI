<?php

declare(strict_types=1);

namespace Chasqui\Canal\Presentation;

use Chasqui\Canal\Infrastructure\Telegram\TelegramApiClient;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

final class CanalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramApiClient::class, fn () => new TelegramApiClient(
            new Client,
            (string) config('telegram.bot_token'),
        ));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}
