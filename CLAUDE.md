# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Qué es Chasqui

Servicio de asistentes conversacionales sobre Telegram: un mensaje entrante dispara un **pipeline de bloques configurables por base de datos** (no por código). Cada `Servicio` es una lista ordenada de `BloqueServicio`, y cada bloque tiene un `tipo_bloque` que se resuelve a un ejecutor PHP.

Laravel 13 / PHP 8.3 / Filament 4 / Postgres + Redis. El README es el de Laravel por defecto — ignóralo.

## Comandos

```bash
composer setup          # install + .env + key + migrate + npm build (primera vez)
composer dev            # serve + queue:listen + pail + vite en paralelo
composer ci             # lint + stan + test  ← ejecuta esto antes de dar por terminado un cambio

composer test           # pest (todas las suites)
composer test:unit      # --testsuite=Unit  (también :feature, :integration)
composer test:coverage  # exige 80% mínimo
vendor/bin/pest tests/Unit/Pipeline/Application/PipelineRunnerTest.php   # un archivo
vendor/bin/pest --filter="reanuda"                                       # un test por nombre

composer lint           # pint --test (verifica)
composer fmt            # pint (aplica)
composer stan           # phpstan/larastan nivel 5 sobre src/ y app/

php artisan db:seed --class=AsistenteGeneralSeeder   # servicio + cliente + suscripción de prueba
docker compose up -d    # app + nginx:8080 + postgres:5433 + redis:6380
```

Los tests corren sobre SQLite en memoria (ver `phpunit.xml`), no sobre Postgres.

## Arquitectura

`src/` (namespace `Chasqui\`) contiene los bounded contexts; `app/` queda casi vacío y solo aloja lo que Laravel exige (User, providers). PSR-4 en `composer.json` mapea ambos.

Contextos: `Pipeline` (el núcleo), `Canal` (Telegram), `Cliente`, `LlmGateway`. `Billing`, `Sync` y `Shared` existen vacíos, reservados para Fase 2.

Cada contexto usa capas `Application/` (lógica y contratos, sin Eloquent), `Infrastructure/` (Eloquent, HTTP, ejecutores) y `Presentation/` (ServiceProvider + rutas). Los providers de cada contexto se registran en `bootstrap/providers.php`, y `CanalServiceProvider::boot()` carga `src/Canal/Presentation/routes.php` — las rutas de un contexto viven en el contexto, no en `routes/`.

**Regla de oro**: cuando aparezca código bajo `*/Domain/`, no puede depender de `Illuminate`. `tests/Architecture/DomainArchitectureTest.php` lo vigila y hoy pasa vacuamente porque ningún `Domain/` existe todavía.

### Flujo de una petición

`TelegramWebhookHandler::recibir()` verifica la firma (`X-Telegram-Bot-Api-Secret-Token` contra `config('telegram.webhook_secret')`), hace `firstOrCreate` del `Cliente` por `telegram_chat_id`, y bifurca:

- Si existe una `Ejecucion` del cliente en estado `esperando_confirmacion`, **reanuda** ese pipeline: reconstruye el `ExecutionContext` desde `contexto_json`, guarda la respuesta bajo el alias `confirmacion`, y corre solo los bloques con `orden > bloque_actual`.
- Si no, busca la `Suscripcion` activa y arranca una `Ejecucion` nueva con todos los bloques del servicio.

`PipelineRunner::ejecutar()` recorre los bloques en orden, resuelve cada `tipo_bloque` vía `BlockExecutorRegistry`, y guarda el resultado en el `ExecutionContext` **bajo el `alias` del bloque**. Ese alias es lo que enlaza los bloques entre sí: las plantillas usan `{{alias}}` y el trait `ReemplazaAliasesEnTexto` los sustituye desde el contexto.

Estados de `Ejecucion`: `iniciada` → `en_progreso` → `completada` | `fallida` | `esperando_confirmacion`. Si un ejecutor lanza `BloqueException`, el runner marca `fallida` y la envuelve en `EjecucionFallidaException`. Si un ejecutor devuelve `ConfirmacionPendiente`, el runner **persiste el contexto y retorna** — el pipeline queda pausado hasta el siguiente mensaje del usuario.

### Añadir un tipo de bloque

1. Implementa `BlockExecutor` en `src/Pipeline/Infrastructure/Blocks/`. Recibe el `config_json` crudo del bloque y el contexto; lo que devuelvas se guarda bajo el alias.
2. Regístralo en el mapa de `PipelineServiceProvider::register()` con su clave `tipo_bloque`. Sin eso, `BlockExecutorRegistry` lanza `TipoBloqueSinImplementacionException`.
3. Envuelve cualquier fallo esperable en `BloqueException` con un código (`LLM_TIMEOUT`, `CANAL_NO_SOPORTADO`, …); solo esa excepción marca la ejecución como fallida limpiamente.

Existentes: `prompt_simple` (llama al LLM), `entrega` (envía por Telegram, parte el texto en trozos de 4096), `confirmacion` (pausa el pipeline).

## Convenciones

- **El código está en español**: nombres de clases, métodos, propiedades, columnas, comentarios y mensajes de excepción. Mantenlo así — `ejecutar`, `guardar`, `contexto`, `bloque_actual`. Las interfaces de framework y los contratos de Laravel siguen en inglés.
- `declare(strict_types=1);` en todos los archivos. Clases `final` salvo que exista razón para extender.
- Modelos Eloquent: `HasUuids`, atributo `#[Fillable([...])]` (no la propiedad `$fillable`), docblocks `@property` completos — phpstan nivel 5 depende de ellos.
- `Ejecucion` mapea a la tabla `ejecuciones`, y varios modelos necesitan `$table` explícito por la pluralización española.
- Fase 1 es solo Telegram y solo OpenAI: `EntregaExecutor` rechaza cualquier otro canal, y `PromptConfig->proveedor` se lee pero `OpenAiGateway` es la única implementación cableada.
