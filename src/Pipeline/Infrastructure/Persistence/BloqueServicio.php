<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Persistence;

use Database\Factories\BloqueServicioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $servicio_id
 * @property int $orden
 * @property string $alias
 * @property string $tipo_bloque
 * @property array<string, mixed> $config_json
 * @property bool $activo
 */
#[Fillable(['servicio_id', 'orden', 'alias', 'tipo_bloque', 'config_json', 'activo'])]
class BloqueServicio extends Model
{
    /** @use HasFactory<BloqueServicioFactory> */
    use HasFactory, HasUuids;

    protected $table = 'bloques_servicio';

    public $timestamps = false;

    protected static function newFactory(): BloqueServicioFactory
    {
        return BloqueServicioFactory::new();
    }

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'activo' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Servicio, $this> */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
