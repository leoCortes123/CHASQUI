<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application;

final class ExecutionContext
{
    /** @var array<string, mixed> */
    private array $resultados;

    /**
     * @param  array<string, mixed>  $metadatos
     * @param  array<string, mixed>  $resultadosIniciales
     */
    public function __construct(
        public readonly string $ejecucionId,
        public readonly string $clienteId,
        public readonly string $servicioId,
        public readonly string $canal,
        public readonly string $mensajeOriginal,
        public readonly array $metadatos = [],
        array $resultadosIniciales = [],
    ) {
        $this->resultados = $resultadosIniciales;
    }

    public function guardar(string $alias, mixed $valor): void
    {
        $this->resultados[$alias] = $valor;
    }

    public function obtener(string $alias): mixed
    {
        return $this->resultados[$alias] ?? null;
    }

    public function tiene(string $alias): bool
    {
        return array_key_exists($alias, $this->resultados);
    }

    /** @return array<string, mixed> */
    public function todos(): array
    {
        return $this->resultados;
    }
}
