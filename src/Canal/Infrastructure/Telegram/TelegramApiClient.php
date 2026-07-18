<?php

declare(strict_types=1);

namespace Chasqui\Canal\Infrastructure\Telegram;

use GuzzleHttp\ClientInterface;

class TelegramApiClient
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $token,
    ) {}

    public function enviarMensaje(string $chatId, string $texto): void
    {
        $this->http->request('POST', "https://api.telegram.org/bot{$this->token}/sendMessage", [
            'json' => [
                'chat_id' => $chatId,
                'text' => $texto,
            ],
        ]);
    }
}
