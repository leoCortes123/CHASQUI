<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Persistence;

use Chasqui\Cliente\Infrastructure\Persistence\Cliente;
use Chasqui\Cliente\Infrastructure\Persistence\Suscripcion;
use Database\Factories\EjecucionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $cliente_id
 * @property string $servicio_id
 * @property string|null $suscripcion_id
 * @property string $estado
 * @property array<string, mixed>|null $contexto_json
 * @property string|null $error_mensaje
 * @property int|null $bloque_actual
 * @property string $canal
 * @property string|null $mensaje_id_canal
 * @property-read Cliente $cliente
 */
#[Fillable([
    'cliente_id', 'servicio_id', 'suscripcion_id', 'estado', 'contexto_json',
    'error_mensaje', 'bloque_actual', 'canal', 'mensaje_id_canal',
])]
class Ejecucion extends Model
{
    /** @use HasFactory<EjecucionFactory> */
    use HasFactory, HasUuids;

    protected $table = 'ejecuciones';

    protected static function newFactory(): EjecucionFactory
    {
        return EjecucionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'contexto_json' => 'array',
        ];
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Servicio, $this> */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    /** @return BelongsTo<Suscripcion, $this> */
    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(Suscripcion::class);
    }
}
