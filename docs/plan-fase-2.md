# Plan Fase 2 — Motor de servicios componibles

> Reemplaza cualquier documentación anterior. Parte de lo que ya está programado en Fase 1.

## 1. Premisa

Chasqui despliega **servicios** cuyo resultado se entrega por mensajería (texto, archivo, o un
link a un reporte). Un servicio se arma con **bloques prediseñados** que el administrador
combina desde la BD. La Fase 2 no construye "el producto para minimercados": construye el
**catálogo de bloques genéricos** que hace que ese caso —y los siguientes— sean únicamente
configuración.

**Criterio de diseño de todo el plan**: si para lanzar un servicio nuevo hay que escribir código,
el bloque no es lo bastante genérico. La única excepción legítima es añadir un **adaptador**
dentro de un bloque existente (una fuente de datos nueva, un formato nuevo), no un bloque nuevo
por servicio.

### La variabilidad vive en los adaptadores, no en el pipeline

El ejemplo que dispara esta fase: un documento puede llegar como foto de papel, como PDF, o como
XML descargado de una plataforma (DIAN, un banco, un ERP, lo que sea). **El análisis posterior es
idéntico** — cambia solo la pieza que lee y normaliza.

```
[ingestar: foto | PDF | XML | texto | API ]  →  estructura normalizada bajo un alias
                                                          ↓
                                    [analizar]  →  [entregar: mensaje | archivo | link]
```

El bloque de ingesta despacha a un adaptador según el formato de entrada y **siempre produce la
misma forma de datos**, definida por el `esquema` de su configuración. Todo lo que va después
—guardar, consultar, analizar, entregar— es indiferente al origen. Ese es el patrón que hay que
implementar bien una vez.

## 2. Punto de partida

Ya existe: motor de pipeline configurable por BD con pausa/reanudación, `BlockExecutorRegistry`,
bloques `prompt_simple` / `entrega` / `confirmacion`, canal Telegram con firma verificada,
`Cliente`/`Suscripcion`/`Ejecucion`, `OpenAiGateway`.

Brechas del motor a cerrar (detectadas leyendo el código):

| # | Brecha | Dónde |
|---|--------|-------|
| 1 | El webhook solo acepta `message.text`; ignora fotos y documentos | `TelegramWebhookHandler::recibir()` |
| 2 | `TelegramApiClient` solo envía texto: no descarga archivos, no envía archivos ni teclados | `TelegramApiClient` |
| 3 | `OpenAiGateway` no soporta entrada multimodal | `OpenAiGateway::completar()` |
| 4 | Un cliente solo puede tener un servicio: `->first()` sobre suscripciones, sin selector | `TelegramWebhookHandler` |
| 5 | El pipeline corre síncrono dentro del request del webhook | `TelegramWebhookHandler` |
| 6 | Bug: el parsing de confirmación toma el primer carácter y lo compara con `'si'` → siempre `'no'` | `TelegramWebhookHandler:103` |
| 7 | La pausa está cableada al alias `confirmacion`: no sirve para pedir cualquier dato | `reanudarPipeline()` |
| 8 | No hay dónde persistir los datos que un servicio captura | migraciones |
| 9 | Filament instalado sin panel: no hay forma de armar servicios ni corregir datos sin SQL | `app/` |

## 3. Arquitectura

### 3.1 Almacén genérico de datos (`Registro`)

Para que un servicio nuevo no exija migraciones nuevas, los datos que capturan los servicios
viven en un almacén genérico:

- `colecciones` — definidas por el administrador: `slug`, `nombre`, `esquema_json` (campos, tipos)
  y opcionalmente `servicio_id`. Es "la tabla" que el admin declara sin tocar el código.
- `registros` — `coleccion_id`, `cliente_id`, `datos_json` (jsonb), `origen` (qué adaptador lo
  produjo), `estado` (`extraido` | `confirmado` | `corregido`), `archivo_ref`, timestamps.
- Índices jsonb sobre las claves que se consultan; scoping obligatorio por `cliente_id`.

Un servicio de facturas y uno de lecturas de sensores usan la misma tabla con distinta colección.
Cuando un caso crezca lo suficiente para justificar tablas propias, se migra; no antes.

### 3.2 Nuevos bloques (el catálogo)

| tipo_bloque | Qué hace | Config clave |
|---|---|---|
| `ingestar` | Entrada cruda → estructura normalizada según `esquema`. Despacha a un **adaptador** por formato | `alias_entrada`, `esquema`, `adaptador` (o `auto`), `instrucciones` |
| `guardar_registro` | Persiste una estructura del contexto en una colección | `coleccion`, `alias_datos`, `estado` |
| `consultar_datos` | Consulta declarativa sobre una colección → resultado al contexto | `coleccion`, `filtros`, `agregaciones`, `rango`, `orden` |
| `entrada_usuario` | Generaliza `confirmacion`: pausa el pipeline pidiendo cualquier dato | `mensaje_template`, `alias_respuesta`, `opciones`, `validacion` |
| `publicar_reporte` | Renderiza una plantilla con datos del contexto y devuelve una **URL** firmada | `plantilla`, `alias_datos`, `expira_en` |
| `transformar` | Reformatea/combina aliases sin LLM (mapear, filtrar, calcular campos) | `operaciones` |

Notas de diseño:

- **`ingestar` es el corazón de la fase.** Un `IngestorRegistry` paralelo a `BlockExecutorRegistry`
  resuelve el adaptador: `xml` (XPath declarativo en config → esquema), `vision` (imagen/PDF vía
  LLM multimodal + modo JSON), `texto` (texto libre vía LLM), `tabular` (CSV/Excel), `api`
  (endpoint externo). Añadir una fuente nueva = una clase adaptadora, cero cambios en los
  servicios existentes. El adaptador `xml` debe ser configurable por mapeo, no específico de
  ningún emisor: `{"proveedor": "//AccountingSupplierParty//RegistrationName", ...}`.
- **`consultar_datos` no acepta SQL en la config** (superficie de ataque). Es una gramática
  declarativa acotada — filtros por campo/rango, agregaciones (`suma`, `promedio`, `conteo`,
  `agrupar_por`, `serie_temporal`) — que se traduce a query builder con `cliente_id` forzado.
- **`publicar_reporte`** existe porque el entregable no siempre cabe en un mensaje. Devuelve un
  link a una vista firmada y temporal; `entrega` lo manda como cualquier otro texto.
- `entrega` se amplía para mandar archivos, no solo texto.

Con este catálogo, un servicio es: *ingestar algo → guardarlo → consultarlo → analizarlo con un
prompt → entregarlo*. Combinaciones distintas de los mismos siete bloques.

### 3.3 Capacidades transversales del motor

1. **Entrada polimórfica**: el webhook acepta texto, foto y documento; descarga el archivo a
   storage y lo deja en el contexto bajo `archivo_entrada` con su tipo MIME. El tipo MIME es lo
   que permite a `ingestar` elegir adaptador en modo `auto`.
2. **Ejecución asíncrona**: el webhook encola `ProcesarMensajeEntrante` y responde 200 al
   instante. La tabla `jobs` y `queue:listen` ya existen.
3. **Enrutamiento de servicios**: con varias suscripciones activas, una clase en `Canal` decide
   qué hacer con el mensaje — reanudar ejecución pausada, atender un comando, o mostrar menú.
4. **Pausa generalizada**: estado `esperando_entrada`; el alias donde se guarda la respuesta lo
   dicta la config del bloque que pausó (cierra las brechas 6 y 7).
5. **Canal agnóstico**: ningún bloque nuevo puede asumir Telegram. Solo `entrega` y el enrutador
   conocen el canal; la abstracción ya está en el código y la columna `canal` en BD.
6. **Salida multimodal**: enviar archivos y teclados desde `TelegramApiClient`.

### 3.4 Panel de administración (Filament)

Es la herramienta del administrador de Chasqui, no un extra: **armar servicios encadenando
bloques** (con la config de cada bloque validada contra su esquema), definir colecciones,
inspeccionar ejecuciones para depurar, y corregir registros mal extraídos. Sin esto, cada
servicio nuevo sigue exigiendo SQL a mano — que es exactamente lo que el motor promete evitar.

## 4. Etapas

| Etapa | Contenido | Resultado |
|---|---|---|
| **0. Robustez del motor** | Cola, fix del bug de confirmación, `entrada_usuario` con alias configurable, enrutador y menú de servicios | El motor soporta varios servicios y usuarios reales |
| **1. Ingesta** | Entrada polimórfica en el webhook + descarga, `IngestorRegistry` con adaptadores `xml`, `vision` y `texto`, bloque `ingestar` | Cualquier documento entra y sale normalizado |
| **2. Datos** | Colecciones y registros, bloques `guardar_registro` y `consultar_datos` | Los servicios pueden acumular y consultar sin código |
| **3. Salida** | `entrega` con archivos, `publicar_reporte` con links firmados, `transformar` | El entregable ya no está limitado al tamaño de un mensaje |
| **4. Panel** | Filament: servicios/bloques, colecciones, ejecuciones, corrección de registros | El administrador arma servicios sin tocar la BD |
| **5. Servicios semilla** | Seeders del caso minimercado sobre el catálogo ya construido | Validación: N servicios, cero código nuevo |

Las etapas 0–3 son secuenciales; la 4 puede avanzar en paralelo desde la 2. La **etapa 5 es la
prueba del diseño**: si algún servicio semilla obliga a escribir un ejecutor, el catálogo falló.

## 5. Caso semilla (etapa 5, solo configuración)

Minimercados de barrio, como primer banco de pruebas. Todos son seeders:

**Captura** — mismo pipeline, distinto adaptador y colección:

| slug | Pipeline |
|---|---|
| `registrar-factura-compra` | `ingestar` (auto: xml \| vision) → `entrada_usuario` (confirmar) → `guardar_registro` → `entrega` |
| `registrar-factura-servicio` | idéntico, otra colección y esquema |
| `registrar-inventario-aparatos` | `ingestar` (texto) → `entrada_usuario` → `guardar_registro` → `entrega` |
| `registrar-venta-dia` | `ingestar` (texto) → `guardar_registro` → `entrega` |

Que una factura llegue en XML o en foto cambia **solo el adaptador**; el pipeline es el mismo.

**Análisis** — todos comparten el patrón `consultar_datos` → `prompt_simple` → `entrega`
(con `publicar_reporte` intercalado cuando el informe es largo):

`analisis-proveedores`, `analisis-compras`, `analisis-ventas`, `diagnostico-energia`,
`pronostico-compras`.

Que cinco análisis distintos sean el mismo pipeline con otra configuración es la señal de que el
motor está bien construido.

## 6. Riesgos técnicos

- **Fuga de scoping**: `consultar_datos` debe forzar `cliente_id` en toda consulta; es la
  condición de seguridad del almacén genérico. Test de arquitectura que lo verifique.
- **Consultas sobre jsonb**: sin índices adecuados, las agregaciones se degradan al crecer los
  registros. Medir en la etapa 2 con volumen simulado.
- **Config inválida en BD**: un bloque mal configurado hoy revienta en ejecución. Cada tipo de
  bloque debe declarar su esquema de configuración, validado al guardar desde Filament.
- **Costo del adaptador `vision`**: medir por documento; el adaptador `xml` es determinista y
  gratis, y por eso es la vía preferente cuando la fuente lo permite.
