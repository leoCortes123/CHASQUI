<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Blocks\Concerns;

use Chasqui\Pipeline\Application\ExecutionContext;

trait ReemplazaAliasesEnTexto
{
    private function reemplazarAliases(string $template, ExecutionContext $contexto): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function (array $m) use ($contexto): string {
            $valor = $contexto->obtener($m[1]);

            return is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : (string) $valor;
        }, $template);
    }
}
