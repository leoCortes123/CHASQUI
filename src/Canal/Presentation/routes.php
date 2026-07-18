<?php

declare(strict_types=1);

use Chasqui\Canal\Infrastructure\Telegram\TelegramWebhookHandler;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/telegram', [TelegramWebhookHandler::class, 'recibir']);
