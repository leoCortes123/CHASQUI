---
description: Crea migración + modelo Eloquent con las convenciones del proyecto
agent: build
---

Crea la migración y el modelo para `$ARGUMENTS`. Lee primero
`src/Pipeline/Infrastructure/Persistence/Ejecucion.php`: es el ejemplar canónico y de ahí sale todo
lo de abajo.

**Migración** — `bash bin/oc-php php artisan make:migration crear_tabla_<nombre>`
(nunca `php artisan` a secas: en el host no hay PHP):

- Nombres de tabla y columnas **en español**, en plural español (`ejecuciones`, `suscripciones`).
- Clave primaria `uuid('id')->primary()`, sin autoincremento.
- Foreign keys explícitas con `constrained()` y el nombre de tabla correcto — la pluralización
  automática de Laravel se equivoca con el español.

**Modelo** — en `src/<Contexto>/Infrastructure/Persistence/`:

- `declare(strict_types=1)`, namespace `Chasqui\<Contexto>\Infrastructure\Persistence`.
- `use HasFactory, HasUuids;` y `protected $table = '<tabla>';` **explícito** (obligatorio: la
  pluralización española no coincide con la que infiere Eloquent).
- Atributo `#[Fillable([...])]` de `Illuminate\Database\Eloquent\Attributes\Fillable` — **no** la
  propiedad `$fillable`.
- Docblock `@property` completo con todas las columnas y `@property-read` para las relaciones:
  phpstan nivel 5 depende de ellos y `composer stan` falla si faltan.
- Casts vía el método `protected function casts(): array`, no la propiedad.
- Relaciones tipadas con su docblock genérico (`@return BelongsTo<Cliente, $this>`).

Si el modelo lleva factory, créala en `database/factories/` y engánchala con
`protected static function newFactory()`.

Ejecuta la migración con `bash bin/oc-php php artisan migrate` y cierra con `/ci`.
