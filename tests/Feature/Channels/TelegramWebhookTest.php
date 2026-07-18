<?php

declare(strict_types=1);

use Chasqui\Canal\Infrastructure\Telegram\TelegramApiClient;
use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Cliente\Infrastructure\Persistence\Suscripcion;
use Chasqui\LlmGateway\Infrastructure\OpenAiGateway;
use Chasqui\Pipeline\Infrastructure\Persistence\BloqueServicio;
use Chasqui\Pipeline\Infrastructure\Persistence\Ejecucion;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;

beforeEach(function () {
    config(['telegram.webhook_secret' => 'secret-de-prueba']);
});

it('procesa un mensaje de Telegram y completa la ejecución', function () {
    $cliente = Cliente::factory()->create(['telegram_chat_id' => 123456789]);
    $servicio = Servicio::factory()->create();
    BloqueServicio::factory()->promptSimple()->create(['servicio_id' => $servicio->id, 'orden' => 1]);
    BloqueServicio::factory()->entrega()->create(['servicio_id' => $servicio->id, 'orden' => 2]);
    Suscripcion::factory()->activa()->create(['cliente_id' => $cliente->id, 'servicio_id' => $servicio->id]);

    $this->mock(OpenAiGateway::class)
        ->shouldReceive('completar')
        ->once()
        ->andReturn('Respuesta generada de prueba');

    $this->mock(TelegramApiClient::class)
        ->shouldReceive('enviarMensaje')
        ->once()
        ->with('123456789', 'Respuesta generada de prueba');

    $response = $this->postJson('/webhook/telegram', [
        'message' => [
            'chat' => ['id' => 123456789],
            'text' => 'Necesito ayuda',
            'message_id' => 1,
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'secret-de-prueba']);

    $response->assertOk();
    expect(Ejecucion::count())->toBe(1);
    expect(Ejecucion::first()->estado)->toBe('completada');
});

it('rechaza el webhook si la firma de Telegram no coincide', function () {
    $response = $this->postJson('/webhook/telegram', [
        'message' => ['chat' => ['id' => 1], 'text' => 'hola', 'message_id' => 1],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'firma-invalida']);

    $response->assertStatus(403);
    expect(Ejecucion::count())->toBe(0);
});

it('crea el cliente automáticamente si no existe (registro implícito)', function () {
    $servicio = Servicio::factory()->create();
    BloqueServicio::factory()->promptSimple()->create(['servicio_id' => $servicio->id, 'orden' => 1]);
    BloqueServicio::factory()->entrega()->create(['servicio_id' => $servicio->id, 'orden' => 2]);

    $this->mock(TelegramApiClient::class)
        ->shouldReceive('enviarMensaje')
        ->once();

    $response = $this->postJson('/webhook/telegram', [
        'message' => [
            'chat' => ['id' => 555555555],
            'text' => 'hola',
            'message_id' => 1,
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'secret-de-prueba']);

    $response->assertOk();
    expect(Cliente::where('telegram_chat_id', 555555555)->exists())->toBeTrue();
});
