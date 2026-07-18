<?php

declare(strict_types=1);

namespace Database\Factories;

use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Cliente\Infrastructure\Persistence\Suscripcion;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suscripcion>
 */
class SuscripcionFactory extends Factory
{
    protected $model = Suscripcion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'servicio_id' => Servicio::factory(),
            'estado' => 'activa',
            'plan' => 'free',
            'inicio' => now()->toDateString(),
            'fin' => null,
        ];
    }

    public function activa(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'activa']);
    }
}
