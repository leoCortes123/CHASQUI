---
name: convenciones-chasqui
description: Contrato de ejecución y convenciones del pipeline de Chasqui. Úsala al tocar cualquier cosa bajo src/ — bloques, tipo_bloque, BlockExecutor, PipelineRunner, ExecutionContext, alias, Ejecucion, BloqueException, ConfirmacionPendiente, webhook de Telegram, modelos Eloquent en español, o al añadir tests en tests/.
---

# Convenciones de Chasqui

Servicio de asistentes conversacionales sobre Telegram. Un mensaje entrante dispara un **pipeline de
bloques configurado en base de datos, no en código**: cada `Servicio` es una lista ordenada de
`BloqueServicio`, y cada `tipo_bloque` se resuelve a un ejecutor PHP. Añadir capacidades significa
añadir ejecutores genéricos, nunca ramas por caso de uso.

## El contrato de ejecución

El **`alias` del bloque es la pieza central**. `PipelineRunner::ejecutar()`
(`src/Pipeline/Application/PipelineRunner.php:23`) recorre los bloques por `orden`, resuelve el
ejecutor vía `BlockExecutorRegistry` y guarda lo que devuelve en el `ExecutionContext` **bajo el
alias del bloque**. Ese alias es lo único que enlaza un bloque con los siguientes: las plantillas
escriben `{{alias}}` y el trait `Blocks\Concerns\ReemplazaAliasesEnTexto` los sustituye desde el
contexto. Cambiar un alias rompe silenciosamente los bloques que dependen de él.

`ExecutionContext` (`src/Pipeline/Application/ExecutionContext.php`) es un objeto plano con
`guardar()`, `obtener()`, `tiene()` y `todos()`, más datos readonly de la ejecución (`ejecucionId`,
`clienteId`, `canal`, `mensajeOriginal`, `metadatos`). Sin Eloquent: vive en `Application/`.

## Las dos salidas no felices

El runner solo entiende dos señales, y confundirlas es el error más caro:

- **`BloqueException`** (con código en mayúsculas: `LLM_TIMEOUT`, `CANAL_NO_SOPORTADO`, …) → el
  runner marca la `Ejecucion` como `fallida`, guarda `error_mensaje` y relanza envuelta en
  `EjecucionFallidaException`. Cualquier otra excepción se escapa sin ese tratamiento.
- **devolver `ConfirmacionPendiente`** → el runner persiste `contexto_json`, deja la ejecución en
  `esperando_confirmacion` y **retorna**. El pipeline queda pausado, no fallido.

Estados de `Ejecucion`: `iniciada` → `en_progreso` → `completada` | `fallida` |
`esperando_confirmacion`.

## Reanudación

`TelegramWebhookHandler::recibir()` (`src/Canal/Infrastructure/Telegram/`) verifica la firma
(`X-Telegram-Bot-Api-Secret-Token` contra `config('telegram.webhook_secret')`), hace `firstOrCreate`
del `Cliente` por `telegram_chat_id` y bifurca:

- ¿hay una `Ejecucion` del cliente en `esperando_confirmacion`? → reconstruye el `ExecutionContext`
  desde `contexto_json`, guarda la respuesta del usuario bajo el alias **`confirmacion`**, y corre
  solo los bloques con `orden > bloque_actual`.
- si no → busca la `Suscripcion` activa y arranca una `Ejecucion` nueva con todos los bloques.

## Añadir un tipo de bloque

1. Implementa `BlockExecutor` en `src/Pipeline/Infrastructure/Blocks/`. Recibe el `config_json`
   crudo y el contexto.
2. **Regístralo** en el mapa de `PipelineServiceProvider::register()` con su clave `tipo_bloque`. Sin
   eso, `BlockExecutorRegistry` lanza `TipoBloqueSinImplementacionException` en runtime.
3. Envuelve los fallos esperables en `BloqueException` con código.

Existentes: `prompt_simple` (llama al LLM), `entrega` (envía por Telegram, parte el texto en trozos
de 4096), `confirmacion` (pausa el pipeline).

## Estilo

- **Todo en español**: clases, métodos, propiedades, columnas, comentarios y mensajes de excepción.
  Solo los contratos que impone Laravel siguen en inglés (`register`, `boot`, `BlockExecutor`).
- `declare(strict_types=1);` en todos los archivos. Clases `final` salvo razón para extender; los
  modelos Eloquent son la excepción.
- Modelos: `HasUuids`, atributo `#[Fillable([...])]` (**no** la propiedad `$fillable`), `$table`
  explícito por la pluralización española (`Ejecucion` → `ejecuciones`), y docblocks `@property`
  completos porque phpstan nivel 5 depende de ellos.
- `*/Domain/` no puede depender de `Illuminate`. Lo vigila
  `tests/Architecture/DomainArchitectureTest.php`.

## Entorno

- **No hay PHP ni composer en el host.** Todo comando PHP va por `bash bin/oc-php`, que lo ejecuta
  dentro del contenedor `app`: `bash bin/oc-php ci`, `bash bin/oc-php test --filter="reanuda"`,
  `bash bin/oc-php php artisan …`. Atajos: `ci`, `lint`, `fmt`, `stan`, `test`.
- **`composer ci` y `vendor/bin/<tool>` no funcionan aquí.** El disco es NTFS y ningún fichero tiene
  bit de ejecución, así que los binarios de `vendor/bin/` dan `Permission denied`; hay que invocar
  `php vendor/bin/<tool>`. Y como NTFS presenta todo como uid 0, dentro del contenedor hay que ser
  root o los tests revientan al escribir `storage/logs/laravel.log`. El wrapper ya hace ambas cosas.
- Los tests corren sobre **SQLite en memoria** (`phpunit.xml`), no sobre Postgres. Docker levanta
  postgres:5433, redis:6380 y nginx:8080 para el runtime real.
- **Fase 1 = solo Telegram y solo OpenAI**: `EntregaExecutor` rechaza cualquier otro canal, y
  `PromptConfig->proveedor` se lee pero `OpenAiGateway` es la única implementación cableada.
