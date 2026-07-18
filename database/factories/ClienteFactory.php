<?php

declare(strict_types=1);

namespace Database\Factories;

use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telefono' => '+57'.fake()->unique()->numerify('3##########'),
            'telegram_chat_id' => fake()->unique()->numberBetween(100000000, 999999999),
            'nombre' => fake()->name(),
            'canal_principal' => 'telegram',
            'activo' => true,
        ];
    }
}
