<?php

declare(strict_types=1);

namespace Chasqui\Cliente\Infrastructure\Persistence;

use Chasqui\Pipeline\Infrastructure\Persistence\Servicio;
use Database\Factories\SuscripcionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $cliente_id
 * @property string|null $servicio_id
 * @property string $estado
 * @property string|null $plan
 * @property Carbon $inicio
 * @property Carbon|null $fin
 * @property-read Servicio|null $servicio
 */
#[Fillable(['cliente_id', 'servicio_id', 'estado', 'plan', 'inicio', 'fin'])]
class Suscripcion extends Model
{
    /** @use HasFactory<SuscripcionFactory> */
    use HasFactory, HasUuids;

    protected $table = 'suscripciones';

    protected static function newFactory(): SuscripcionFactory
    {
        return SuscripcionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'fin' => 'date',
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
}
