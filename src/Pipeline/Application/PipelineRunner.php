<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Application;

use Chasqui\Pipeline\Application\Exceptions\BloqueException;
use Chasqui\Pipeline\Application\Exceptions\EjecucionFallidaException;
use Chasqui\Pipeline\Infrastructure\BlockExecutorRegistry;
use Chasqui\Pipeline\Infrastructure\Persistence\BloqueServicio;
use Chasqui\Pipeline\Infrastructure\Persistence\Ejecucion;
use Illuminate\Support\Collection;

final class PipelineRunner
{
    public function __construct(private readonly BlockExecutorRegistry $registry) {}

    /**
     * @param  Collection<int, BloqueServicio>  $bloques
     *
     * @throws EjecucionFallidaException
     */
    public function ejecutar(Ejecucion $ejecucion, Collection $bloques, ExecutionContext $contexto): void
    {
        foreach ($bloques as $bloque) {
            $ejecucion->update(['estado' => 'en_progreso', 'bloque_actual' => $bloque->orden]);
            $executor = $this->registry->resolver($bloque->tipo_bloque);

            try {
                $resultado = $executor->ejecutar($bloque->config_json, $contexto);
            } catch (BloqueException $e) {
                $ejecucion->update(['estado' => 'fallida', 'error_mensaje' => $e->getMessage()]);
                throw new EjecucionFallidaException($ejecucion->id, $bloque->alias, $e);
            }

            $contexto->guardar($bloque->alias, $resultado);

            if ($resultado instanceof ConfirmacionPendiente) {
                $ejecucion->update([
                    'estado' => 'esperando_confirmacion',
                    'contexto_json' => $contexto->todos(),
                ]);

                return;
            }
        }

        $ejecucion->update([
            'estado' => 'completada',
            'contexto_json' => $contexto->todos(),
        ]);
    }
}
