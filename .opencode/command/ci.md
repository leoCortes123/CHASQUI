---
description: Corre el gate completo (pint --test + phpstan + pest) y arregla lo que falle
agent: build
---

Ejecuta el gate del proyecto:

```bash
bash bin/oc-php ci
```

Eso encadena pint `--test`, phpstan nivel 5 sobre `src/` y `app/`, y Pest (SQLite en memoria). El
wrapper levanta el contenedor `app` si hace falta.

**No uses `composer ci` ni `vendor/bin/<tool>` directamente**: el host no tiene PHP, y el disco es
NTFS, así que ningún binario de `vendor/bin/` tiene bit de ejecución (`pint: Permission denied`).
Todo pasa por `bash bin/oc-php`, que invoca `php vendor/bin/<tool>` como root dentro del contenedor.

Si algo falla:

1. **lint** → `bash bin/oc-php fmt` aplica el fix y sigue.
2. **stan** → arregla el tipo de verdad. Casi siempre falta un docblock `@property` en un modelo o
   un tipo genérico en un array; no silencies con `@phpstan-ignore` sin justificarlo.
3. **test** → reproduce el caso concreto antes de tocar nada:
   `bash bin/oc-php test --filter="<nombre del test>"`.

Repite hasta que el gate pase entero. No des el cambio por terminado antes.

$ARGUMENTS
