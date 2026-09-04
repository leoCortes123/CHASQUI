---
description: Crea un nuevo tipo_bloque siguiendo el patrón BlockExecutor del pipeline
agent: build
---

Crea el tipo de bloque `$ARGUMENTS` siguiendo exactamente el patrón existente. Antes de escribir,
lee `src/Pipeline/Infrastructure/Blocks/ConfirmacionExecutor.php` como referencia mínima y
`PromptSimpleExecutor.php` como referencia de un ejecutor con dependencias.

**1. El ejecutor** — `src/Pipeline/Infrastructure/Blocks/<Nombre>Executor.php`:

- `final class`, `declare(strict_types=1)`, implementa
  `Chasqui\Pipeline\Application\Contracts\BlockExecutor`.
- Firma: `ejecutar(array $configuracion, ExecutionContext $contexto): mixed`. `$configuracion` es el
  `config_json` crudo del `BloqueServicio`; **lo que devuelvas se guarda en el contexto bajo el
  `alias` del bloque** y queda disponible para los bloques siguientes.
- Si la config lleva texto con plantillas, usa el trait
  `Blocks\Concerns\ReemplazaAliasesEnTexto` y su `reemplazarAliases()` — es lo que sustituye
  `{{alias}}` con los resultados del contexto.
- Dependencias por constructor (gateways, clientes HTTP), nunca resueltas dentro de `ejecutar`.

**2. El registro** — `src/Pipeline/Presentation/PipelineServiceProvider::register()`: añade la
entrada `'<tipo_bloque>' => $app->make(<Nombre>Executor::class)` al array del
`BlockExecutorRegistry`. Sin esto el runner lanza `TipoBloqueSinImplementacionException` en runtime.

**3. Los fallos** — envuelve cualquier error esperable en
`Chasqui\Pipeline\Application\Exceptions\BloqueException` con un código en mayúsculas al estilo de
los existentes (`LLM_TIMEOUT`, `CANAL_NO_SOPORTADO`). Solo esa excepción hace que `PipelineRunner`
marque la ejecución como `fallida` limpiamente; cualquier otra se propaga sin control.

Si el bloque debe **pausar** el pipeline esperando al usuario, devuelve un
`ConfirmacionPendiente` en vez de lanzar: el runner persiste el contexto y retorna, y el pipeline se
reanuda en el siguiente mensaje.

**4. El test** — `tests/Unit/Pipeline/Application/Blocks/<Nombre>ExecutorTest.php`, en el estilo de
los tests hermanos de ese directorio. Cubre: camino feliz, sustitución de aliases si aplica, y el
`BloqueException` con su código.

Cierra con `/ci`.
