<?php

declare(strict_types=1);

namespace Database\Factories;

use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Pipeline\Infrastructure\Persistence\Ejecucion;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ejecucion>
 */
class EjecucionFactory extends Factory
{
    protected $model = Ejecucion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'servicio_id' => Servicio::factory(),
            'suscripcion_id' => null,
            'estado' => 'iniciada',
            'contexto_json' => null,
            'error_mensaje' => null,
            'bloque_actual' => null,
            'canal' => 'telegram',
            'mensaje_id_canal' => (string) fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
