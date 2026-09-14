# Contenido demo del catálogo — registro de cifras inventadas

**Fecha:** 2026-09-14 · **Rama:** `lote-1-sistema-diseno` · **Fuente única:** `database/seeders/DemoTourSeeder.php`

---

## ⚠️ ADVERTENCIA — NADA DE ESTO PUEDE SALIR A PRODUCCIÓN SIN CONFIRMACIÓN DE LA CLIENTA

Todo el contenido del catálogo (3 tours, 3 destinos, 3 experiencias, en español e inglés)
es **material de relleno redactado por AnyersonDev** para que la maqueta pueda evaluarse por
su diseño y no por sus textos de placeholder. **La clienta no proporcionó ninguno de estos
textos, precios, duraciones ni descripciones.**

Hasta el lote anterior el relleno se distinguía solo porque cada título llevaba el prefijo
`[MUESTRA]`. Ese prefijo **ya no existe**: se quitó a propósito, porque era justamente lo que
estropeaba la pantalla. El rastro se mudó a **este documento**, que pasa a ser el único lugar
donde consta qué es inventado. Si este documento se pierde, se pierde el rastro.

### Lo que impide que esto se indexe hoy

`config('cms.catalog_demo_content') === true` fuerza `noindex, nofollow` en **las 12 URLs de
catálogo** (índice + ficha × tours/destinos/experiencias × ES/EN). Verificado por `curl` tras
sembrar: las 6 fichas revisadas devuelven `<meta name="robots" content="noindex, nofollow">`.

**No se debe bajar esa bandera como parte de un cambio de contenido.** Es la palanca que se
levanta el día que la clienta cargue su propio contenido, y es la única razón por la que fue
seguro escribir textos creíbles.

### Lo que SÍ queda expuesto (abierto, ver al final)

La **home (`/es`, `/en`) es indexable** y su carrusel "Tours destacados" muestra ahora los
3 tours con sus títulos y sus precios. Antes decía `[MUESTRA] …`; ahora se lee como oferta
real. Eso **no bloquea nada en local**, pero **sí bloquea el despliegue**. Ver O-1.

---

## 1. Cifras — tours

### Tour id=1 · «Camino Inca a Machu Picchu, 4 días» / «Inca Trail to Machu Picchu, 4 Days»

| Campo | Valor | Origen | ¿Lo tocó este lote? |
|---|---|---|---|
| `price_pen_cents` | `350000` → **S/ 3,500.00** | Inventado en el lote 3 | **No** (se respeta el existente) |
| `price_usd_cents` | `93333` → **US$ 933.33** | **Derivado**: `350000 / 3.75` | **SÍ — cambiado**, ver nota ▼ |
| `duration_label` | «4 días / 3 noches» / «4 days / 3 nights» | Inventado en el lote 3 | Solo se **tradujo** al inglés |
| `difficulty` | `Dificil` | Inventado en el lote 3 | No |
| «4 días» en título, slug, meta | — | **Derivado** de `duration_label` | Sí (texto nuevo) |
| Itinerario: **4 días** (Día 1 … Día 4) | — | **Inventado en este lote** | Sí |

> **Nota sobre el cambio de precio (la única cifra que este lote modifica).**
> La ficha mostraba **S/ 3,500.00 y US$ 95.00 juntos, en la misma pantalla**: un tipo de cambio
> implícito de 36.8 que se detecta a simple vista. Los otros dos tours ya cumplían exactamente
> `PEN / 3.75` (el valor guardado en `settings.exchange_rate_pen_usd`): `12000/3.75 = 3200` y
> `45000/3.75 = 12000`. Es decir, el `9500` de este tour era el outlier, no el patrón.
> Se corrigió a `350000 / 3.75 = 93333`. **Es aritmética sobre una cifra que ya existía, no una
> cifra nueva** — pero sigue siendo un número que la clienta tiene que confirmar.

### Tour id=2 · «Ruta de picanterías en Arequipa» / «Arequipa Picantería Food Trail»

| Campo | Valor | Origen | ¿Lo tocó este lote? |
|---|---|---|---|
| `price_pen_cents` | `12000` → **S/ 120.00** | Inventado en el lote 3 | No |
| `price_usd_cents` | `3200` → **US$ 32.00** | Inventado en el lote 3 (coherente con 3.75) | No |
| `duration_label` | «4 horas» / «4 hours» | Inventado en el lote 3 | Solo se **tradujo** al inglés |
| `difficulty` | `Facil` | Inventado en el lote 3 | No |
| Itinerario: **3 paradas** | — | **Inventado en este lote** | Sí |

### Tour id=3 · «Valle Sagrado: Pisac, Maras y Moray» / «Sacred Valley: Pisac, Maras and Moray»

| Campo | Valor | Origen | ¿Lo tocó este lote? |
|---|---|---|---|
| `price_pen_cents` | `45000` → **S/ 450.00** | Inventado en el lote 3 | No |
| `price_usd_cents` | `12000` → **US$ 120.00** | Inventado en el lote 3 (coherente con 3.75) | No |
| `duration_label` | «1 día» / «Full day» | Inventado en el lote 3 | Solo se **tradujo** al inglés |
| `difficulty` | `Facil` | Inventado en el lote 3 | No |
| Itinerario: **4 paradas** | — | **Inventado en este lote** | Sí |

### Cambio de estado (no es cifra, pero es dato inventado)

`is_featured` pasó de `false` a `true` en los tours **id=2** e **id=3**. El tour id=1 ya lo tenía.
Motivo: "Tours destacados" en la home es una rejilla de tres columnas y con un solo tour
renderizaba una tarjeta suelta y centrada, que se leía como fallo de maquetación. **Que los tres
tours estén destacados es una decisión de relleno, no una decisión comercial de la clienta.**

---

## 2. Cifras que deliberadamente NO se inventaron

La prosa (descripciones, itinerarios, incluye/no incluye) se redactó **sin una sola medida
inventada**. No hay en ningún registro:

- altitudes ni cotas (no se escribió «4,215 m» para el abra de Warmiwañusca, aunque el dato es público);
- distancias ni kilometraje (no se escribió «Km 82», ni «43 km de ruta»);
- número de plazas, tamaño de grupo ni ratio guía/pasajero;
- horas de salida, de recojo ni de retorno;
- años de experiencia, número de viajeros atendidos, premios ni certificaciones
  (regla 3 de `.claude/proyecto/00-contexto.md`);
- nombres de personas (guías, equipo). El control de
  `TeamMemberCatalogCoverageTest::test_the_real_seeder_pipeline_never_introduces_the_mockup_placeholder_people`
  sigue en verde: el seeder no crea ninguna persona.

Lo que **sí** aparece y **no** es invención: topónimos, sitios arqueológicos (Runkurakay,
Sayacmarca, Phuyupatamarca, Wiñay Wayna, Llactapata, Moray, Maras, Ollantaytambo, Pisac),
mercados (San Camilo) y nombres de platos e insumos (rocoto relleno, adobo, chupe, chicha de
guiñapo, huacatay, queso helado). Es geografía y patrimonio público, verificable, no una
afirmación fabricada sobre el negocio de la clienta.

---

## 3. Texto inventado — inventario por registro

Todo lo listado abajo es **prosa escrita por AnyersonDev**, en ES y EN. El inglés está
**redactado**, no traducido literal: las frases no son calco del español (p. ej. ES «El Camino
Inca no es el atajo a Machu Picchu» → EN «The Inca Trail is not the shortcut… It is the long
way round — the one the Incas built so that you would arrive on foot»).

### Tours (id 1, 2, 3) — campos inventados en ES y EN

`title`, `slug`, `summary`, `description` (HTML), `duration_label` (EN), `meeting_point`,
`inclusions`, `exclusions`, `itinerary` (títulos + descripciones HTML), `meta_title`,
`meta_description`.

### Destinos (id 1 Cusco, 2 Valle Sagrado, 3 Arequipa) — campos inventados en ES y EN

`description`, `meta_title`, `meta_description`. Además `name` y `slug` en **EN**
(`Sacred Valley` / `sacred-valley`; Cusco y Arequipa no cambian de nombre entre idiomas).
El `name`/`slug` en ES **no se tocó**.

### Experiencias (id 1 Trekking, 2 Gastronomía, 3 Cultura) — campos inventados en ES y EN

`description`, `meta_title`, `meta_description`. Además `name` y `slug` en **EN**:
Gastronomía → `Cuisine` / `cuisine`, Cultura → `Culture` / `culture`, Trekking → `Trekking` /
`trekking` (misma palabra en los dos idiomas). El `name`/`slug` en ES **no se tocó**.

### Lo que NO tocó este lote

- **Fotos.** `tour_images` (14), `destination_images` (12), `experience_images` (6) y las
  portadas siguen exactamente como las dejó `DemoCatalogImageSeeder`, con su `alt` ES+EN.
  Conteo verificado antes y después de sembrar: idéntico.
- **`resources/views/` y `resources/css/`.** Cero archivos tocados. Todo el cambio es de datos.
- **`config('cms.catalog_demo_content')`.** Sigue en `true`.

---

## 4. Cambio de slugs y redirecciones

Los slugs en ES llevaban el marcador dentro de la URL. Se renombraron, y el mecanismo de
historial que ya existía (`tour_slug_histories` + el 301 en `TourController::show()`) absorbió
el cambio solo:

| Slug anterior | Slug nuevo (ES) | Slug nuevo (EN) | HTTP del anterior |
|---|---|---|---|
| `muestra-camino-inca-4-dias` | `camino-inca-machu-picchu-4-dias` | `inca-trail-machu-picchu-4-days` | **301** |
| `muestra-tour-gastronomico-arequipa` | `ruta-de-picanterias-arequipa` | `arequipa-picanteria-food-trail` | **301** |
| `muestra-tour-valle-sagrado` | `valle-sagrado-pisac-maras-moray` | `sacred-valley-pisac-maras-moray` | **301** |

Los 3 slugs viejos **siguen guardados en `tour_slug_histories`** y ahí es donde queda el único
`muestra-…` que sobrevive en la base. Es intencional: es lo que hace que la URL vieja redirija.
No se renderiza en ninguna página.

---

## 5. Idempotencia del seeder

`DemoTourSeeder` busca cada registro por su slug ES actual y, si no lo encuentra, por el slug
anterior — por eso una base ya sembrada **se migra en vez de duplicarse**. Después hace
`fill()` + `save()` con el mismo payload, así que la segunda corrida no encuentra nada sucio y
no escribe.

Comprobado corriendo `php artisan db:seed --class=DemoTourSeeder` **dos veces seguidas**:

| Métrica | Tras 1ª corrida | Tras 2ª corrida |
|---|---|---|
| tours / destinos / experiencias | 3 / 3 / 3 | 3 / 3 / 3 |
| filas en `tour_slug_histories` | 3 | **3** (no se duplica el historial) |
| filas en `experience_tour` | 4 | 4 |
| imágenes tour / destino / experiencia | 14 / 12 / 6 | 14 / 12 / 6 |

La relación tour↔experiencia usa `sync()` (no `syncWithoutDetaching()`) justamente para que la
segunda corrida converja al estado declarado y no al estado declarado *más lo que hubiera*.

---

## 6. Verificación

| Qué | Cómo se comprobó | Resultado |
|---|---|---|
| No queda `[MUESTRA]` en el catálogo | Consulta a MySQL sobre **26 columnas** traducibles de `tours`, `destinations`, `experiences`, `tour_images`, `destination_images`, `experience_images`, con `LIKE '%MUESTRA%'` y `LIKE '%muestra%'` | **0 filas** |
| …y ese check puede fallar | La misma consulta buscando `'%Machu%'` en `tours.title` | **1 fila** (el mecanismo de búsqueda sí detecta) |
| `/en/` sirve título en inglés en los 3 tours | `curl` a las 3 fichas EN, extrayendo `<title>` y `<h1>` | «Inca Trail to Machu Picchu, 4 Days», «Arequipa Picantería Food Trail», «Sacred Valley: Pisac, Maras and Moray» |
| Listados `/en/` sin español | `curl` a `/en/tours`, `/en/destinos`, `/en/experiencias` | Cero apariciones de «Valle Sagrado», «Gastronomía», «Cultura» |
| Itinerario EN no cae al español | `curl` a la ficha EN del Camino Inca, buscando «Día N», «Guía oficial», «Propinas», «Seguro de viaje» | **0 apariciones**; salen los 4 «Day N ·» en inglés y `inclusions`/`exclusions` en inglés |
| El aviso de contenido sin traducir ya no aparece en EN | mismo HTML | 0 apariciones |
| Tres tarjetas en "Tours destacados" | `curl` a `/es`, extrayendo enlaces `/es/tours/…` | los 3 slugs nuevos |
| Descripciones de destino y experiencia ya no vacías | `curl` a `/es|/en` × `destinos/cusco`, `experiencias/gastronomia`, `destinos/sacred-valley` | párrafo presente en las 5 |
| `noindex` sigue puesto | mismo HTML de las 6 fichas | `content="noindex, nofollow"` presente en todas |
| Longitud de campos dentro de los `maxLength` del CMS | Script sobre los 9 registros: `summary` ≤300, `meta_title` ≤160, `meta_description` ≤320 | Ningún campo excede (la clienta podrá editarlos en Filament sin toparse con el límite) |
| Suite | `vendor/phpunit/phpunit/phpunit`, driver verificado `mysql` / `pachaviva_test` | **245 tests, 917 aserciones — OK** |

---

## 7. Abiertos (no se resolvieron en este lote)

**O-1 · Bloquea despliegue. La home es indexable y ahora publica tres ofertas que no son de la
clienta.** Las fichas están protegidas por `catalog_demo_content`, la home no. Antes el
carrusel decía `[MUESTRA] …` y era inofensivo; ahora dice «Camino Inca a Machu Picchu, 4 días —
S/ 3,500.00». Opciones: (a) no desplegar hasta tener contenido real, (b) `noindex` temporal en
la home, (c) ocultar el carrusel mientras `catalog_demo_content` sea `true`. **La (c) toca una
vista — la decide el maquetador, no yo.** Lo levanto para que `anyerson-seo` y el `deployer` lo
vean antes de cualquier pase.

**O-2 · `php artisan data:audit-sample` ahora reporta las 9 filas del catálogo como "sin prefijo
`[MUESTRA]`".** Es el comportamiento correcto y documentado del comando (su propio docblock dice
que no adivina qué contenido es seguro y que la revisión la hace un humano), pero conviene saber
que el informe pre-despliegue pasó de 0 filas a 9. Este documento es lo que hay que leer junto a
ese informe.

**O-3 · El slug de un locale resuelve también bajo el otro prefijo.** `/en/destinos/valle-sagrado`
devuelve 200 sirviendo el mismo contenido que `/en/destinos/sacred-valley`, por el fallback de
slug de `ResolvesBySlugByLocale`. Es comportamiento preexistente (lote i18n), no algo que
introduzca este lote, y hoy no cuesta nada porque esas URLs son `noindex`. El día que se levante
`catalog_demo_content` pasa a ser contenido duplicado y hay que decidir entre canonical o 301.
Decisión de `anyerson-seo`.

**O-4 · Documentos de lotes anteriores quedan desactualizados.** `docs/lote-2/00-contrato-datos.md`
y `docs/lote-1/00-sistema-diseno.md` afirman que los tours sembrados llevan el prefijo
`[MUESTRA]`. No se editaron (son actas de su lote). Este documento los reemplaza en ese punto.

**O-5 · Ya no hay ningún registro sembrado sin itinerario.** El tour id=2 antes se dejaba
deliberadamente sin `itinerary` para ejercitar en datos reales el caso "la clienta no llenó
esto". Ahora los 3 lo tienen. El caso sigue cubierto por test
(`TourPublicCatalogRouteRestoredTest::test_a_tour_with_no_images_and_no_itinerary_renders_without_errors`),
así que no se perdió cobertura — pero ya no se ve en pantalla al navegar el sitio local.
