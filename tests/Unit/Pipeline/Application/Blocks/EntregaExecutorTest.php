<?php

declare(strict_types=1);

use Chasqui\Canal\Infrastructure\Telegram\TelegramApiClient;
use Chasqui\Pipeline\Application\Exceptions\BloqueException;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Infrastructure\Blocks\EntregaExecutor;

it('envía el contenido del alias configurado por Telegram', function () {
    $telegram = Mockery::mock(TelegramApiClient::class);
    $telegram->expects('enviarMensaje')
        ->withArgs(fn ($chatId, $texto) => $chatId === '123456789' && $texto === 'Respuesta final')
        ->once();

    $executor = new EntregaExecutor($telegram);

    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'telegram',
        mensajeOriginal: 'hola',
        metadatos: ['telegram_chat_id' => 123456789],
    );
    $ctx->guardar('respuesta_ia', 'Respuesta final');

    $resultado = $executor->ejecutar([
        'alias_contenido' => 'respuesta_ia',
        'tipo_mensaje' => 'texto',
        'max_caracteres_telegram' => 4096,
        'dividir_si_largo' => true,
    ], $ctx);

    expect($resultado)->toBeTrue();
});

it('lanza BloqueException si el canal no es telegram', function () {
    $telegram = Mockery::mock(TelegramApiClient::class);
    $executor = new EntregaExecutor($telegram);

    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'whatsapp',
        mensajeOriginal: 'hola',
    );
    $ctx->guardar('respuesta_ia', 'Respuesta final');

    $executor->ejecutar(['alias_contenido' => 'respuesta_ia'], $ctx);
})->throws(BloqueException::class);
