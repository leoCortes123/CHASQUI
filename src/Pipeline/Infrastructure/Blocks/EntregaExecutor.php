<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Blocks;

use Chasqui\Canal\Infrastructure\Telegram\TelegramApiClient;
use Chasqui\Pipeline\Application\Contracts\BlockExecutor;
use Chasqui\Pipeline\Application\Exceptions\BloqueException;
use Chasqui\Pipeline\Application\ExecutionContext;

final class EntregaExecutor implements BlockExecutor
{
    public function __construct(private readonly TelegramApiClient $telegram) {}

    public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
    {
        if ($contexto->canal !== 'telegram') {
            throw new BloqueException("Canal '{$contexto->canal}' no soportado en Fase 1", 'CANAL_NO_SOPORTADO');
        }

        $chatId = $contexto->metadatos['telegram_chat_id'] ?? null;
        if ($chatId === null) {
            throw new BloqueException('Falta telegram_chat_id en el contexto de ejecución', 'CHAT_ID_FALTANTE');
        }

        $contenido = $contexto->obtener($configuracion['alias_contenido']);
        $texto = is_array($contenido) ? json_encode($contenido, JSON_UNESCAPED_UNICODE) : (string) $contenido;

        $maxCaracteres = $configuracion['max_caracteres_telegram'] ?? 4096;
        $dividirSiLargo = $configuracion['dividir_si_largo'] ?? true;

        $fragmentos = $dividirSiLargo
            ? (mb_str_split($texto, $maxCaracteres) ?: [$texto])
            : [$texto];

        foreach ($fragmentos as $fragmento) {
            $this->telegram->enviarMensaje((string) $chatId, $fragmento);
        }

        return true;
    }
}
