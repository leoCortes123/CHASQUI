<?php

declare(strict_types=1);

namespace Chasqui\Cliente\Infrastructure\Persistence;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $telefono
 * @property int|null $telegram_chat_id
 * @property string|null $nombre
 * @property string $canal_principal
 * @property bool $activo
 */
#[Fillable(['telefono', 'telegram_chat_id', 'nombre', 'canal_principal', 'activo'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): ClienteFactory
    {
        return ClienteFactory::new();
    }

    protected function casts(): array
    {
        return [
            'telegram_chat_id' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
