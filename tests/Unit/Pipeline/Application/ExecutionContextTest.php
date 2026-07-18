<?php

declare(strict_types=1);

use Chasqui\Pipeline\Application\ExecutionContext;

it('guarda y recupera un resultado por alias', function () {
    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'telegram',
        mensajeOriginal: 'hola',
    );

    $ctx->guardar('resultado', 'texto generado');

    expect($ctx->obtener('resultado'))->toBe('texto generado');
    expect($ctx->tiene('resultado'))->toBeTrue();
    expect($ctx->tiene('otro'))->toBeFalse();
});

it('expone los resultados iniciales pasados al construir el contexto', function () {
    $ctx = new ExecutionContext(
        ejecucionId: fake()->uuid(),
        clienteId: fake()->uuid(),
        servicioId: fake()->uuid(),
        canal: 'telegram',
        mensajeOriginal: 'hola',
        resultadosIniciales: ['previo' => 'valor'],
    );

    expect($ctx->obtener('previo'))->toBe('valor');
    expect($ctx->todos())->toBe(['previo' => 'valor']);
});
