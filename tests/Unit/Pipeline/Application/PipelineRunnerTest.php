<?php

declare(strict_types=1);

use Chasqui\Pipeline\Application\Contracts\BlockExecutor;
use Chasqui\Pipeline\Application\Exceptions\BloqueException;
use Chasqui\Pipeline\Application\Exceptions\EjecucionFallidaException;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Application\PipelineRunner;
use Chasqui\Pipeline\Infrastructure\BlockExecutorRegistry;
use Chasqui\Pipeline\Infrastructure\Persistence\BloqueServicio;
use Chasqui\Pipeline\Infrastructure\Persistence\Ejecucion;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;

it('itera los bloques en orden, acumula el contexto y marca la ejecución como completada', function () {
    $servicio = Servicio::factory()->create();
    $bloque1 = BloqueServicio::factory()->promptSimple()->create(['servicio_id' => $servicio->id, 'orden' => 1]);
    $bloque2 = BloqueServicio::factory()->entrega()->create(['servicio_id' => $servicio->id, 'orden' => 2]);
    $ejecucion = Ejecucion::factory()->create(['servicio_id' => $servicio->id]);

    $registry = new BlockExecutorRegistry([
        'prompt_simple' => new class implements BlockExecutor
        {
            public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
            {
                return 'respuesta de prueba';
            }
        },
        'entrega' => new class implements BlockExecutor
        {
            public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
            {
                return true;
            }
        },
    ]);

    $runner = new PipelineRunner($registry);

    $contexto = new ExecutionContext(
        ejecucionId: $ejecucion->id,
        clienteId: fake()->uuid(),
        servicioId: $servicio->id,
        canal: 'telegram',
        mensajeOriginal: 'hola',
    );

    $runner->ejecutar($ejecucion, collect([$bloque1, $bloque2]), $contexto);

    expect($contexto->obtener('respuesta_ia'))->toBe('respuesta de prueba');
    expect($contexto->obtener('entrega_final'))->toBeTrue();
    expect($ejecucion->fresh()->estado)->toBe('completada');
});

it('marca la ejecución como fallida cuando un bloque lanza BloqueException', function () {
    $servicio = Servicio::factory()->create();
    $bloque = BloqueServicio::factory()->promptSimple()->create(['servicio_id' => $servicio->id, 'orden' => 1]);
    $ejecucion = Ejecucion::factory()->create(['servicio_id' => $servicio->id]);

    $registry = new BlockExecutorRegistry([
        'prompt_simple' => new class implements BlockExecutor
        {
            public function ejecutar(array $configuracion, ExecutionContext $contexto): mixed
            {
                throw new BloqueException('fallo simulado', 'TEST_ERROR');
            }
        },
    ]);

    $runner = new PipelineRunner($registry);

    $contexto = new ExecutionContext(
        ejecucionId: $ejecucion->id,
        clienteId: fake()->uuid(),
        servicioId: $servicio->id,
        canal: 'telegram',
        mensajeOriginal: 'hola',
    );

    expect(fn () => $runner->ejecutar($ejecucion, collect([$bloque]), $contexto))
        ->toThrow(EjecucionFallidaException::class);

    expect($ejecucion->fresh()->estado)->toBe('fallida');
});
