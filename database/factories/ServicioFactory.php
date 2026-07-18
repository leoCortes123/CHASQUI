<?php

declare(strict_types=1);

namespace Database\Factories;

use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'audiencia' => 'ambos',
            'activo' => true,
            'requiere_suscripcion' => true,
        ];
    }
}
