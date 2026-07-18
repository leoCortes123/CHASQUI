<?php

declare(strict_types=1);

namespace Chasqui\Pipeline\Infrastructure\Persistence;

use Database\Factories\ServicioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $slug
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $audiencia
 * @property bool $activo
 * @property bool $requiere_suscripcion
 * @property-read Collection<int, BloqueServicio> $bloques
 */
#[Fillable(['slug', 'nombre', 'descripcion', 'audiencia', 'activo', 'requiere_suscripcion'])]
class Servicio extends Model
{
    /** @use HasFactory<ServicioFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): ServicioFactory
    {
        return ServicioFactory::new();
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'requiere_suscripcion' => 'boolean',
        ];
    }

    /** @return HasMany<BloqueServicio, $this> */
    public function bloques(): HasMany
    {
        return $this->hasMany(BloqueServicio::class)->orderBy('orden');
    }
}
