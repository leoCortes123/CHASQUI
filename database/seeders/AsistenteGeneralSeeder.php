<?php

declare(strict_types=1);

namespace Database\Seeders;

use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Cliente\Infrastructure\Persistence\Suscripcion;
use Chasqui\Pipeline\Infrastructure\Persistence\BloqueServicio;
use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Illuminate\Database\Seeder;

class AsistenteGeneralSeeder extends Seeder
{
    public function run(): void
    {
        $servicio = Servicio::query()->firstOrCreate(
            ['slug' => 'asistente-general'],
            [
                'nombre' => 'Asistente General',
                'descripcion' => 'Responde preguntas generales usando IA.',
                'audiencia' => 'ambos',
                'activo' => true,
                'requiere_suscripcion' => true,
            ],
        );

        BloqueServicio::query()->firstOrCreate(
            ['servicio_id' => $servicio->id, 'orden' => 1],
            [
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
                'activo' => true,
            ],
        );

        BloqueServicio::query()->firstOrCreate(
            ['servicio_id' => $servicio->id, 'orden' => 2],
            [
                'alias' => 'entrega_final',
                'tipo_bloque' => 'entrega',
                'config_json' => [
                    'alias_contenido' => 'respuesta_ia',
                    'tipo_mensaje' => 'texto',
                    'max_caracteres_telegram' => 4096,
                    'dividir_si_largo' => true,
                ],
                'activo' => true,
            ],
        );

        $cliente = Cliente::query()->firstOrCreate(
            ['telefono' => '+573000000000'],
            [
                'telegram_chat_id' => 999999999,
                'nombre' => 'Cliente de Prueba',
                'canal_principal' => 'telegram',
                'activo' => true,
            ],
        );

        Suscripcion::query()->firstOrCreate(
            ['cliente_id' => $cliente->id, 'servicio_id' => $servicio->id],
            [
                'estado' => 'activa',
                'plan' => 'free',
                'inicio' => now()->toDateString(),
            ],
        );
    }
}
