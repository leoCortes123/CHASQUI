# AGENTS.md

## Commands

```bash
composer ci              # lint + stan + test — run before completing any change
composer test            # all Pest suites (SQLite in-memory)
composer test:unit       # --testsuite=Unit (also :feature, :integration)
vendor/bin/pest tests/Unit/Pipeline/Application/PipelineRunnerTest.php
vendor/bin/pest --filter="reanuda"
composer lint            # pint --test (verify, not fix)
composer fmt             # pint (apply fixes)
composer stan            # phpstan level 5 on src/ and app/
composer dev             # serve + queue:listen + pail + vite in parallel
docker compose up -d     # postgres:5433, redis:6380, nginx:8080
php artisan db:seed --class=AsistenteGeneralSeeder
```

## Architecture

- **`src/` (namespace `Chasqui\`)** contains bounded contexts; **`app/`** is a Laravel skeleton.
- Contexts: `Pipeline` (core), `Canal` (Telegram), `Cliente`, `LlmGateway`. `Billing`, `Sync`, `Shared` are empty (Phase 2).
- Each context has layers: `Application/` (logic, no Eloquent), `Infrastructure/` (Eloquent, HTTP, executors), `Presentation/` (ServiceProvider + routes).
- Context providers register in `bootstrap/providers.php`. Route files live inside their context (e.g. `src/Canal/Presentation/routes.php`), not in `routes/`.
- **Domain rule**: any code under `*/Domain/` must not depend on `Illuminate`. `tests/Architecture/DomainArchitectureTest.php` enforces this (passes vacuously today).

## Request flow

`TelegramWebhookHandler::recibir()` → verifies webhook secret → finds/creates `Cliente` → either resumes a paused `Ejecucion` (state `esperando_confirmacion`) or starts a new pipeline from the active `Suscripcion`.

`PipelineRunner::ejecutar()` iterates blocks in order, resolves each `tipo_bloque` via `BlockExecutorRegistry`, stores results in `ExecutionContext` under the block's `alias`. Templates use `{{alias}}` and `ReemplazaAliasesEnTexto` substitutes them.

## Block executors

To add a type: implement `BlockExecutor` in `src/Pipeline/Infrastructure/Blocks/`, register in `PipelineServiceProvider::register()`. Wrap expected failures in `BloqueException`.

Existing types: `prompt_simple`, `entrega` (Telegram only; 4096-char chunks), `confirmacion` (pauses pipeline).

## Conventions

- **All code in Spanish**: class/method/property/column names, comments, exceptions. Laravel contracts stay in English.
- `declare(strict_types=1);` in every file. Classes `final` unless intentionally extensible.
- Eloquent models: `HasUuids`, `#[Fillable([...])]` attribute, complete `@property` docblocks (phpstan needs them).
- `Ejecucion` maps to table `ejecuciones`; several models need explicit `$table` due to Spanish pluralization.
- Phase 1: Telegram only, OpenAI only. `EntregaExecutor` rejects other channels; only `OpenAiGateway` is wired.
- Pint preset is `laravel` with `ordered_imports` and `declare_strict_types` enforced.
- `.npmrc` sets `ignore-scripts=true` — `npm install` needs `--ignore-scripts` or nothing extra.
