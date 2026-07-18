<?php

declare(strict_types=1);

namespace Database\Factories;

use Chasqui\Pipeline\Infrastructure\Persistence\BloqueServicio;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloqueServicio>
 */
class BloqueServicioFactory extends Factory
{
    protected $model = BloqueServicio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'servicio_id' => Servicio::factory(),
            'orden' => 1,
            'alias' => 'resultado',
            'tipo_bloque' => 'prompt_simple',
            'config_json' => [],
            'activo' => true,
        ];
    }

    public function promptSimple(): static
    {
        return $this->state(fn (array $attributes) => [
            'alias' => 'respuesta_ia',
            'tipo_bloque' => 'prompt_simple',
            'config_json' => [
                'proveedor' => 'openai',
                'modelo' => 'gpt-4o-mini',
                'prompt_sistema' => 'Eres el Asistente General de Chasqui. Responde de forma breve y útil.',
                'prompt_usuario_template' => '{{mensaje_usuario}}',
                'temperatura' => 0.7,
                'max_tokens' => 800,
                'formato_respuesta' => 'texto',
            ],
        ]);
    }

    public function entrega(): static
    {
        return $this->state(fn (array $attributes) => [
            'alias' => 'entrega_final',
            'tipo_bloque' => 'entrega',
            'config_json' => [
                'alias_contenido' => 'respuesta_ia',
                'tipo_mensaje' => 'texto',
                'max_caracteres_telegram' => 4096,
                'dividir_si_largo' => true,
            ],
        ]);
    }

    public function confirmacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'alias' => 'confirmacion_usuario',
            'tipo_bloque' => 'confirmacion',
            'config_json' => [
                'mensaje_template' => '¿Confirmas la acción sobre {{alias_dato}}?',
                'alias_datos' => 'datos_extraidos',
                'opciones' => ['Sí, confirmar', 'No, cancelar'],
                'timeout_minutos' => 60,
            ],
        ]);
    }
}
