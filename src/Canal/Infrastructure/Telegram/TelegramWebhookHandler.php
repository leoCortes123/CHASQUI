<?php

declare(strict_types=1);

namespace Chasqui\Canal\Infrastructure\Telegram;

use Chasqui\Canal\Infrastructure\Exceptions\WebhookInvalidoException;
use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Cliente\Infrastructure\Persistence\Suscripcion;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Application\PipelineRunner;
use Chasqui\Pipeline\Infrastructure\Persistence\Ejecucion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramWebhookHandler
{
    public function __construct(
        private readonly PipelineRunner $runner,
        private readonly TelegramApiClient $telegram,
    ) {}

    public function recibir(Request $request): JsonResponse
    {
        $this->verificarFirma($request);

        $mensaje = $request->input('message');
        if (! is_array($mensaje) || ! isset($mensaje['text'], $mensaje['chat']['id'])) {
            return response()->json(['status' => 'ignorado']);
        }

        $chatId = (int) $mensaje['chat']['id'];
        $texto = (string) $mensaje['text'];
        $mensajeIdCanal = isset($mensaje['message_id']) ? (string) $mensaje['message_id'] : null;

        $cliente = Cliente::firstOrCreate(
            ['telegram_chat_id' => $chatId],
            ['telefono' => 'tg-'.$chatId, 'canal_principal' => 'telegram'],
        );

        $ejecucionPausada = Ejecucion::query()
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'esperando_confirmacion')
            ->latest()
            ->first();

        if ($ejecucionPausada !== null) {
            $this->reanudarPipeline($ejecucionPausada, $chatId, $texto);

            return response()->json(['status' => 'ok']);
        }

        $suscripcion = Suscripcion::with('servicio.bloques')
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'activa')
            ->first();

        if ($suscripcion === null || $suscripcion->servicio === null) {
            $this->telegram->enviarMensaje((string) $chatId, 'Todavía no tienes un servicio activo.');

            return response()->json(['status' => 'sin_suscripcion']);
        }

        $servicio = $suscripcion->servicio;

        $ejecucion = Ejecucion::create([
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'suscripcion_id' => $suscripcion->id,
            'estado' => 'iniciada',
            'canal' => 'telegram',
            'mensaje_id_canal' => $mensajeIdCanal,
        ]);

        $contexto = new ExecutionContext(
            ejecucionId: $ejecucion->id,
            clienteId: $cliente->id,
            servicioId: $servicio->id,
            canal: 'telegram',
            mensajeOriginal: $texto,
            metadatos: ['telegram_chat_id' => $chatId],
        );
        $contexto->guardar('mensaje_usuario', $texto);

        $this->runner->ejecutar($ejecucion, $servicio->bloques, $contexto);

        return response()->json(['status' => 'ok']);
    }

    private function reanudarPipeline(Ejecucion $ejecucion, int $chatId, string $textoRespuesta): void
    {
        $servicio = $ejecucion->servicio()->with('bloques')->first();

        $contexto = new ExecutionContext(
            ejecucionId: $ejecucion->id,
            clienteId: $ejecucion->cliente_id,
            servicioId: $ejecucion->servicio_id,
            canal: 'telegram',
            mensajeOriginal: $textoRespuesta,
            metadatos: ['telegram_chat_id' => $chatId],
            resultadosIniciales: $ejecucion->contexto_json ?? [],
        );
        $contexto->guardar('confirmacion', str_starts_with(mb_strtolower(trim($textoRespuesta))[0] ?? '', 'si') ? 'si' : 'no');

        $bloquesRestantes = $servicio->bloques->where('orden', '>', $ejecucion->bloque_actual ?? 0)->values();

        $this->runner->ejecutar($ejecucion, $bloquesRestantes, $contexto);
    }

    private function verificarFirma(Request $request): void
    {
        $secretRecibido = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        if (! hash_equals((string) config('telegram.webhook_secret'), $secretRecibido)) {
            throw new WebhookInvalidoException('Firma de Telegram inválida');
        }
    }
}
