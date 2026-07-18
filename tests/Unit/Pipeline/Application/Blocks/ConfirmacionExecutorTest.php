<?php

declare(strict_types=1);

use Chasqui\Pipeline\Application\ConfirmacionPendiente;
use Chasqui\Pipeline\Application\ExecutionContext;
use Chasqui\Pipeline\Infrastructure\Blocks\ConfirmacionExecutor;

it('devuelve una ConfirmacionPendiente con el mensaje templado', function () {
    $executor = new ConfirmacionExecutor;

    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'telegram',
        mensajeOriginal: 'hola',
    );
    $ctx->guardar('datos_extraidos', 'la playlist X');

    $resultado = $executor->ejecutar([
        'mensaje_template' => '¿Confirmas la acción sobre {{datos_extraidos}}?',
        'alias_datos' => 'datos_extraidos',
        'opciones' => ['Sí, confirmar', 'No, cancelar'],
    ], $ctx);

    expect($resultado)->toBeInstanceOf(ConfirmacionPendiente::class);
    expect($resultado->mensaje)->toBe('¿Confirmas la acción sobre la playlist X?');
    expect($resultado->opciones)->toBe(['Sí, confirmar', 'No, cancelar']);
});
