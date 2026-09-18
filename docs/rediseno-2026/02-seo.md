# Auditoría SEO — Pase cinematográfico 2026 (Pacha Viva)

Estado: **EN CURSO — este documento se actualiza a medida que se cierra cada punto** (instrucción del coordinador tras quedarme sin turnos tras la primera medición; todo lo de abajo ya está en disco, no solo en contexto de conversación).

Consumo el contrato de `docs/rediseno-2026/01-cro-contrato.md` (veredicto APTO) y no repito lo que ya midió `cro-validator`: WCAG del hero, overflow 375/768/1440, jerarquía de encabezados, consola, selector de idioma por clic real (9/9 fichas → 200), parallax, JS-off, reduced-motion, teclado, conmutador PEN/USD. Ver ese documento para el detalle.

Entorno de medición: sitio local `http://127.0.0.1:8087/`, PHP 8.2.1, catálogo real (3 tours + 3 destinos + 3 experiencias, todos publicados y destacados). Todo lo marcado "medido" abajo es curl/Playwright real contra ese entorno, no supuesto.

---

## 0. Dato que condiciona TODA esta auditoría

`config('cms.catalog_demo_content')` está en **`true`** hoy (`config/cms.php:67`). Mientras esté así:

- `components/layout.blade.php` imprime `<meta name="robots" content="noindex, nofollow">` en **TODAS** las páginas públicas (confirmado por curl en `/es/`, `/es/contacto`, fichas — ver más abajo).
- `/sitemap.xml` responde **404** a propósito (`App\Support\Indexability::siteIsIndexable()` → `false`), y `robots.txt` no declara la línea `Sitemap:`.
- Esto **ya es** una red de seguridad anti-indexación bien construida para la fase de contenido de muestra. Pero **no sirve como noindex de staging** para el despliegue en `limaviewtours.com/tour-word/` — ver `## 6. Receta de noindex del staging`, porque es la MISMA bandera que algún día se apaga para lanzar Pacha Viva de verdad, y ese día, si el build sigue viviendo en `/tour-word/`, el sitio quedaría indexable ahí sin ningún control dedicado.

---

## 1. Veredicto del punto de mayor riesgo — LCP del hero (medido, no estimado)

**El patrón NO es defendible tal cual.** Confirmado con captura real de red (Playwright, `/es/`), no con lectura de código:

```
GET /images/site/hero-machupicchu-amanecer-640.webp     200  (slide 1, eager/high — esperado)
GET /images/site/hero-valle-sagrado-panoramica-640.webp 200  (slide 2, loading="lazy" fetchpriority="low")
GET /images/site/hero-cordillera-rio-640.webp            200  (slide 3, loading="lazy" fetchpriority="low")
```

Las 3 se descargan en la carga inicial, en el orden 1-2-3, junto con el resto de los 13 requests estáticos de la Home (confirmado con `browser_network_requests`). `loading="lazy"` y `fetchpriority="low"` no evitan la descarga porque el CSS (`app.css:277-283`) esconde las diapositivas con `opacity: 0` sobre un `position: absolute; inset: 0` **dentro del contenedor del hero, que ya está en el viewport en la carga inicial**. El lazy-loading nativo del navegador decide por la **caja de layout**, no por opacidad/visibilidad — un elemento con `inset:0` dentro de una sección visible SIEMPRE cuenta como "cerca del viewport", así que el navegador lo descarga de inmediato pese al atributo. `fetchpriority="low"` solo baja su prioridad relativa en la cola, no evita la descarga.

**Coste medido de las 2 diapositivas invisibles, por breakpoint** (peso real de archivo, `stat` sobre `public/images/site/`):

| Breakpoint | 3 fotos del hero (1 visible + 2 invisibles) | Peso de las 2 invisibles |
|---|---|---|
| 640w (móvil) | 71 766 B (70 KB) | ~47 KB |
| 1024w (tablet) | 188 418 B (184 KB) | ~121 KB |
| 1440w (laptop) | 334 706 B (327 KB) | ~215 KB |
| 1920w (desktop) | 734 602 B (717 KB) | **~478 KB** |

A 1920w, **las 2 fotos que nadie ve pesan más que la Home entera declarada en el encargo (~561 KB a 375px)**. Esto no es una estimación: son los bytes reales de los archivos que el navegador efectivamente pidió y descargó, confirmados por `browser_network_requests` + `stat` cruzados.

**Consecuencia en CWV**: esas 2 descargas compiten por ancho de banda y conexiones HTTP con los recursos que sí importan para LCP (CSS, fuentes, la propia foto de la diapositiva 1, que es candidata a elemento LCP por ser la foto a pantalla completa del hero). En una conexión móvil real (no en Laragon local, donde todo es instantáneo) esto retrasa medibles el LCP del elemento que importa. No pude correr Lighthouse en este entorno (ver `## NO COMPROBADO`), así que no doy un número de LCP en milisegundos — pero el mecanismo y el peso están medidos y son inequívocos, no un "debería".

**Remedio concreto** (para `maquetador-frontend`, ya que toca `home.blade.php`/`app.js`, no lógica de negocio):
- No imprimir `src` real en el `<img>` de las diapositivas 2 y 3 en el HTML inicial (usar `data-src` o similar) — dejar que `heroSlider()` (Alpine, ya existe) asigne el `src` real de forma diferida: por ejemplo, tras el evento `load` de la página, o con un pequeño margen antes de que cada diapositiva vaya a activarse (el autoavance ya mide ~6.5 s por diapositiva, medido por el CRO — sobra tiempo para precargar la 2 antes de que se necesite sin competir con el LCP).
- Alternativa más simple si se prefiere no tocar Alpine: cargar las 3 con un IntersectionObserver sobre el propio `#hero` que solo inyecte `src` de la 2 y 3 cuando el hero ya terminó su primer render (p. ej. `requestIdleCallback` con fallback a `setTimeout`).
- Lo que NO resuelve esto: bajar más el `fetchpriority` o encadenar más `loading="lazy"` — ya están puestos y no alcanzan, es un problema de layout, no de atributos.
- Esfuerzo: bajo-medio (una función en `app.js` + quitar 2 atributos `src` del blade).

---

## 2. Indexación y rastreo (Bloque 1) — sobre el 100% del inventario de rutas

### 2.1 `robots.txt` — `GET /robots.txt`

```
User-agent: *
Disallow: /admin
```

Dinámico (`RobotsController`), sin host cableado (usa `url()`), correcto. Sin línea `Sitemap:` porque `Indexability::siteIsIndexable()` es `false` hoy (por diseño, ver `## 0`) — cuando la bandera baje, la línea vuelve sola. `Disallow: /admin` es el único bloqueo, no bloquea CSS/JS/imágenes. **Sin defecto.**

### 2.2 `sitemap.xml` — `GET /sitemap.xml` → **404** (esperado hoy, ver `## 0`)

**S-01 · Alto · Bloque 1 · `app/Http/Controllers/SitemapController.php:52`** — `SitemapController::ROUTE_NAMES` solo contiene `['home', 'about', 'contact']`. El catálogo real (3 tours + 3 destinos + 3 experiencias + sus 3 índices) **no está en la lista**, aunque el propio docblock del archivo avisaba "el día que el lote 3 agregue tours/destinos como páginas públicas, se extiende `self::ROUTE_NAMES`" — ese día ya llegó (el catálogo es real desde hace lotes) y el archivo no se actualizó. Hoy es invisible porque el sitemap entero da 404 por la bandera de demo, pero el día que se publique contenido real y se apague `catalog_demo_content`, el sitemap **seguirá sin listar ni una sola ficha ni un solo índice de catálogo** — justo las páginas comerciales del sitio.
- Verificado con: lectura de `SitemapController.php` + `curl /sitemap.xml` (404 hoy, coherente con la bandera).
- Cómo reproducirlo cuando se apague la bandera: `curl /sitemap.xml` y contar `<url>` — deberían ser 6 (hoy) o más (6 + 3 índices + 9 fichas = 18) y solo listará 6.
- Qué cambiar: extender `ROUTE_NAMES` con `tours.index`, `destinations.index`, `experiences.index`, y agregar un bucle que recorra `Tour::published()`, `Destination::published()`, `Experience::published()` emitiendo `route('tours.show', [...])` etc. por cada registro y locale con traducción real (mismo criterio que ya usa `hreflangUrls` en las vistas — un tour sin traducción a un locale no debe entrar al sitemap de ese locale).
- Asignar a: `backend-laravel`.
- Esfuerzo: medio (extender un controlador ya existente, sin tocar su contrato).

### 2.3 Meta robots / `X-Robots-Tag` en producción

Confirmado por curl en `/es/`, `/es/contacto`, ficha de tour: **todas** traen `<meta name="robots" content="noindex, nofollow">` (bandera de demo activa, comportamiento esperado y correcto hoy). **Sin `X-Robots-Tag` como cabecera HTTP** en ningún caso — el noindex vive solo en el HTML. No es un defecto para el estado actual (meta robots basta), pero es relevante para la receta de staging (`## 6`), donde SÍ se pide la cabecera como capa adicional.

### 2.4 Canonicals — **defecto real, sitio entero**

**S-02 · Alto · Bloque 1 · todas las rutas bajo `{locale}` · duplicado con/sin barra final, live en las dos formas**

Medido con curl, no una URL: **todas** las rutas probadas responden `200` tanto con barra final como sin ella, y las dos versiones sirven el HTML idéntico:

| URL | Sin barra | Con barra |
|---|---|---|
| `/es` | 200 | — |
| `/es/` | — | 200 |
| `/es/tours` | 200 | 200 (`/es/tours/`) |
| `/es/contacto` | 200 | 200 (`/es/contacto/`) |
| `/es/nosotros` | — | 200 (`/es/nosotros/`) |
| `/en` | 200 | 200 (`/en/`) |
| `/es/tours/camino-inca-machu-picchu-4-dias` | 200 | 200 (con `/` final) |

El `<link rel="canonical">` de ambas versiones apunta **siempre a la forma sin barra** (`url()->current()` la normaliza internamente), así que la etiqueta en sí es consistente — pero **no hay ningún `301` que consolide las dos URLs en el servidor**. `rel="canonical"` es una señal, no una directriz: Google puede igual rastrear e indexar ambas por separado, sobre todo si algo externo enlaza con la barra (y de hecho, el propio encargo de este pase me dio como "Sitio local" la URL `http://127.0.0.1:8087/es/`, **con barra** — es la forma que el propio equipo está usando y compartiendo).

- Verificado con: `curl -o /dev/null -w '%{http_code}'` sobre 7 pares de URLs + `curl` del cuerpo comparando `<link rel="canonical">` en `/es` vs `/es/`.
- Qué cambiar: 301 server-side (o middleware Laravel) que redirija la forma con barra final a la forma sin barra (la que ya generan `route()`/`url()->current()` en todo el sitio, así que no hay ambigüedad de cuál es la "buena"). Revisar también el redirect de `/` en `routes/web.php:18` (`Route::redirect('/', '/es/', 301)` — aterriza en la forma CON barra, que es justo la que se quiere eliminar; cambiarlo a `/es` sin barra para no auto-generar el propio duplicado desde la home).
- Asignar a: `backend-laravel` (routing/middleware, no vista).
- Esfuerzo: bajo (una regla de normalización de trailing slash, patrón estándar de Laravel).
- No es Crítico porque hoy el sitio entero está en `noindex` por la bandera de demo — pero es Alto porque afecta el 100% de las URLs, incluida la Home, y hay que resolverlo **antes** de apagar esa bandera o de publicar en `/tour-word/`.

### 2.5 Cadenas de redirección

`Route::redirect('/', '/es/', 301)` — un salto, correcto en principio (ver nota de la barra final arriba). Los 301 de slug canónico (`TourController`/`DestinationController`) van directo al slug vigente en un salto, verificado por lectura de código (usan el slug ya resuelto, no re-disparan otro lookup). **Sin cadenas >1 salto detectadas.**

### 2.6 Duplicación de host / parámetros / paginación

No verificable en este entorno (local, un solo host `127.0.0.1:8087`, sin `www`/no-`www` real todavía — el dominio de producción de Pacha Viva ni siquiera está decidido, ver `00-contexto.md`). Filtros de `/tours` (`?destino=`, `?experiencia=`) y paginación (`?page=`) **no fuerzan un canonical a la URL base** — cada combinación se autocanonicaliza (`url()->current()` incluye el query string). Con pocos tours esto no genera contenido delgado grave hoy, pero es un patrón a vigilar cuando el catálogo crezca. **Medio, no bloqueante** — recomendación: canonicalizar `/tours` con filtros a la URL sin query string (Google no necesita indexar cada combinación de filtro como página propia), asignar a `backend-laravel` cuando el catálogo crezca lo suficiente para que importe.

### 2.7 Páginas 404 con enlaces entrantes internos

No encontré ninguna: todos los enlaces internos recorridos (nav, footer, tarjetas de Home, CTAs de fichas) usan `route()`/`Route::has()` con respaldo a `#` cuando la ruta no existe — nunca una URL muerta cableada a mano. **Sin defecto.**

---

## 3. Enlazado interno (Bloque 2)

Catálogo real: 3 tours (los 3 publicados y **destacados** → los 3 aparecen en Home), 3 destinos, 3 experiencias (las 3 aparecen en Home). Mapa completo:

| URL | Clics desde Home | Enlaces internos entrantes (fuentes) | Enlaces salientes relevantes |
|---|---|---|---|
| `/` → `/es/` (home) | 0 | nav (todas las páginas), footer (todas), logo | tours/destinos/experiencias destacados, nav, footer |
| `/es/tours` (índice) | 1 (nav+footer+CTA Home) | Home (CTA "ver más"), nav, footer, cada ficha de tour (CTA banner "más tours") | 3 fichas de tour |
| `/es/destinos` (índice) | 1 | nav, footer | 3 fichas de destino |
| `/es/experiencias` (índice) | 1 | nav, footer | 3 fichas de experiencia |
| `/es/nosotros` | 1 | nav, footer | — |
| `/es/contacto` | 1 | nav, footer, botón "contacto" del header, CTA de reserva de cada ficha de tour | — |
| 3 fichas de tour (`/es/tours/{slug}`) | **1** (tarjeta directa en Home — los 3 tours están destacados) | Home, `/es/tours`, ficha de su destino (sección "tours relacionados") | destino padre, `/es/contacto?tour=`, `/es/tours` filtrado |
| 3 fichas de destino (`/es/destinos/{slug}`) | **1** (tarjeta directa en Home) | Home, `/es/destinos`, CTA banner de cada tour de ese destino | `/es/tours` filtrado, tours relacionados |
| 3 fichas de experiencia (`/es/experiencias/{slug}`) | **1** (tarjeta directa en Home) | Home, `/es/experiencias` | — |

**Sin páginas huérfanas.** Con solo 3 elementos por categoría (todos publicados y destacados), el catálogo entero queda a 1 clic de Home — no hay profundidad ≥4 que reportar. Esto es una consecuencia favorable del tamaño actual del catálogo, no necesariamente algo que se sostenga solo cuando crezca (con más tours no destacados, la profundidad real pasaría a depender de `/es/tours` + paginación — vigilar cuando el catálogo crezca).

**Anchors**: revisados los CTAs de Home, fichas y footer — ninguno usa "aquí"/"ver más" a secas como único texto del enlace; los botones tipo "ver más" siempre van dentro de un `<x-ui.section-title>` cuyo contexto visual (encabezado de sección) lo acompaña, y el texto real del enlace declarado en `lang/es/site.php` incluye el sustantivo (`site.home.featured_tours.cta`, etc. — no inspeccioné el string literal de cada clave, ver `NO COMPROBADO`). **Parcial, no bloqueante.**

**Páginas de negocio vs. secundarias**: no hay desbalance — el catálogo (tours/destinos/experiencias) concentra los enlaces entrantes, `/nosotros` y `/contacto` reciben solo nav+footer (proporcional a su rol). **Sin defecto.**

---

## 4. Intención de búsqueda y mapeo (Bloque 3)

No tengo acceso a Search Console ni a ninguna herramienta de volumen de búsqueda para este dominio (sitio no publicado, greenfield) — **no invento cifras**, trabajo solo con intención cualitativa a partir del catálogo real.

| Intención (cualitativa) | URL objetivo | Nota |
|---|---|---|
| "tours en Perú / Cusco" (genérica) | `/tours` | única URL con esta intención, sin competencia interna |
| Tour específico ("camino inca 4 días", "ruta picanterías Arequipa", "valle sagrado tour") | cada ficha de tour | 1:1, sin canibalización |
| Destino ("qué hacer en Cusco", "Valle Sagrado", "Arequipa turismo") | cada ficha de destino | 1:1 |
| Tipo de experiencia ("trekking Perú", "gastronomía peruana", "turismo cultural Perú") | cada ficha de experiencia | 1:1 |
| Agencia / marca ("Pacha Viva", "agencia de turismo Cusco") | Home | correcto, Home es la única con ese enfoque |
| Contacto/reserva | `/contacto` | correcto |

**Sin canibalización detectada** — el catálogo es lo bastante pequeño y cada ficha tiene un `meta_title`/`meta_description` propio (editable en Filament, con respaldo honesto al nombre/resumen, confirmado leyendo `TourController`/`DestinationController`). **Huecos de contenido**: con solo 3 tours, 3 destinos y 3 experiencias, el sitio no cubre variantes de intención más largas (p. ej. duración, presupuesto, dificultad como filtros indexables, o contenido tipo blog/guías) — esto es una limitación de catálogo/contenido, no un defecto de código, y no invento qué "debería" existir sin datos de demanda real.

---

## 5. Datos estructurados (Bloque 4)

### 5.1 Lo que existe

- **`BreadcrumbList`** (`x-seo.breadcrumb-jsonld`): presente en fichas de tour, destino y experiencia. JSON válido (revisado el output real vía curl, ver ejemplo abajo), escapado con `JSON_HEX_*` (mitiga XSS, ya documentado en el propio componente y con test dedicado `tests/Feature/StructuredDataTest.php` según el comentario del archivo — no re-ejecuté ese test, ver `NO COMPROBADO`).

  ```json
  {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Inicio","item":"http://127.0.0.1:8087/es"},
    {"@type":"ListItem","position":2,"name":"Tours","item":"http://127.0.0.1:8087/es/tours"},
    {"@type":"ListItem","position":3,"name":"Camino Inca a Machu Picchu, 4 días"}]}
  ```
  Nota menor: el `item` del primer nivel usa la URL SIN barra final (`/es`), consistente con el canonical (ver `2.4`) — cuando se arregle la barra final, este JSON-LD no necesita ningún cambio, ya está alineado con la forma correcta.

- **`FAQPage`** (`x-seo.faq-jsonld`): presente **solo en `/contacto`**, con las 5 preguntas reales de `site.contacto.faq.items` (confirmado el JSON completo por curl, contenido real, no inventado — incluye honestamente "Todavía no publicamos una política de cancelación general" en vez de inventar una).

### 5.2 Lo que falta — **hallazgo real**

**S-03 · Alto · Bloque 4 · fichas de tour (`tours/show.blade.php`) y sitio entero · sin `Organization`/`WebSite` ni `TouristTrip`/`Product`/`Offer`**

- `grep -rn "Organization\|LocalBusiness\|WebSite\"" resources/views/` → **cero resultados en todo el proyecto**. Ninguna página (ni Home) declara quién es Pacha Viva como entidad — esto es lo primero que Google usa para el Knowledge Panel / sitelinks search box y para vincular la marca con reseñas futuras.
- Las fichas de tour tienen datos estructurados de sobra para un schema de producto/experiencia (`price_pen_cents`, `price_usd_cents`, `duration_label`, `meeting_point`, galería, descripción) y **hoy solo emiten `BreadcrumbList`** — no hay `TouristTrip` ni `Product`+`Offer`. Es contenido de muestra (noindex hoy), así que no urge para este pase, pero el componente `x-seo.breadcrumb-jsonld` ya establece el patrón correcto (JSON válido, escapado seguro) — extenderlo es consistente con lo que ya existe, no un cambio de arquitectura.
- Qué cambiar:
  1. `Organization` (o `TravelAgency`, tipo más específico de schema.org) en el layout (`components/layout.blade.php`), con `name`, `url`, `logo`, y `sameAs` cuando existan redes sociales reales (no inventar perfiles).
  2. `TouristTrip` (o `Product` + `Offer` anidado, ambos vocabularios son válidos para tours — `TouristTrip` es más específico y preferible aquí) en `tours/show.blade.php`, con `offers.price` y `offers.priceCurrency` — **ver el punto siguiente sobre qué precio declarar**.
- Asignar a: `backend-laravel` (nuevo componente Blade siguiendo el patrón de `x-seo.breadcrumb-jsonld`, con datos que ya vienen del modelo `Tour`).
- Esfuerzo: medio.

### 5.3 Riesgo de precio JSON-LD vs. precio visible (encargo explícito) — **verificado, hoy sin riesgo porque el schema no existe todavía**

No hay ningún `Offer`/`Product`/`TouristTrip` en el código hoy, así que **no hay ningún precio declarado en JSON-LD que pueda no coincidir con el visible** — el riesgo que pedía el encargo verificar está cerrado *porque la superficie que lo causaría no existe todavía*, no porque se haya comprobado una coincidencia. Dejo esto anotado explícitamente para que no se lea como "ya está validado":

- **Cuando se implemente el punto 5.2**, el precio de `Offer.price` debe salir de la MISMA fuente que ya pinta la página (`$tour['price_pen_cents']`/`price_usd_cents` vía `App\Support\Money`, la fuente única del proyecto — confirmado en `00-contexto.md`), nunca un valor recalculado aparte.
- El conmutador PEN/USD es client-side (Alpine + `localStorage`, confirmado por el CRO en su Ronda 3) — JSON-LD es estático en el HTML servido, no puede "seguir" al conmutador. Recomendación concreta: declarar **PEN como `priceCurrency`** (es la moneda en la que el negocio realmente cobra, según `00-contexto.md`: "USD es solo conversión de referencia, el tipo de cambio es editable en el CMS") — nunca USD como principal, porque un tipo de cambio editable manualmente podría desincronizarse del valor cacheado por Google en su índice de resultados enriquecidos.
- Esto es una recomendación de implementación, no un defecto medido hoy — lo marco `Medio`, a implementar junto con 5.2, no antes.

---

## 6. Receta de noindex para el staging en `limaviewtours.com/tour-word/`

Encargo explícito, lista para aplicar (no la ejecuto). Contexto: julio 2026, ese mismo dominio ya tuvo un incidente real de staging indexado (`feedback_staging_noindex` en memoria del equipo) — cero margen para repetirlo.

### 6.1 `X-Robots-Tag` — capa de servidor, cubre TODO (HTML, imágenes, JS, CSS, el propio `sitemap.xml` si alguien lo pide directo)

En el `.htaccess` que sirve `/tour-word/` (el `public/.htaccess` de este build de Laravel, desplegado como raíz de ese subdirectorio):

```apache
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>
```

`always` es importante: sin él, Apache no añade la cabecera en respuestas de error (404, etc.), y una 404 de este mismo subdirectorio también debe salir con noindex. `noarchive` de más: evita que Google guarde una copia en caché del staging aunque alguien lo comparta por accidente.

### 6.2 Meta robots en el layout — condicionado para que NUNCA se cuele en producción

El proyecto ya tiene el mecanismo correcto (`App\Support\Indexability`, ver `## 0`), pero **no lo reutilicen tal cual para el staging**: `catalog_demo_content` tiene su propio ciclo de vida (se apaga cuando la clienta cargue contenido real) y el día que se apague, si el build TODAVÍA vive en `/tour-word/`, el staging quedaría indexable sin ningún control dedicado — dos problemas distintos no deben compartir la misma bandera.

Recomendación concreta:

1. Nueva clave dedicada en `config/app.php` (o `config/cms.php`, junto a `catalog_demo_content`):
   ```php
   'is_staging_mirror' => env('IS_STAGING_MIRROR', false),
   ```
2. En el `.env` del despliegue de `/tour-word/` (y SOLO ahí, nunca en el `.env` del dominio real de Pacha Viva cuando exista):
   ```
   IS_STAGING_MIRROR=true
   ```
3. `App\Support\Indexability::siteIsIndexable()` pasa a considerar las dos banderas:
   ```php
   public static function siteIsIndexable(): bool
   {
       return ! config('cms.catalog_demo_content') && ! config('cms.is_staging_mirror');
   }
   ```
   Con este único cambio, **toda la infraestructura que ya existe se hereda gratis**: meta robots `noindex, nofollow` sitio entero, `/sitemap.xml` sigue dando 404, y `robots.txt` sigue sin declarar `Sitemap:` — sin tocar `components/layout.blade.php` ni `RobotsController`/`SitemapController` de nuevo.
4. Esto es un cambio de código: va a `backend-laravel`, con el flag ya definido arriba para que la implementación no tenga que decidir nada de arquitectura.

### 6.3 Por qué NO va `Disallow` en el `robots.txt` de limaviewtours.com — confirmo el criterio del encargo, con la razón técnica completa

**Correcto, no lo pongan.** Dos razones, no solo una:

1. **La razón que ya cita el encargo**: si el crawler no puede descargar la página (bloqueada por `Disallow`), nunca llega a leer el `noindex` — ni el meta ni el `X-Robots-Tag`. Una URL bloqueada por `robots.txt` que además recibe enlaces externos (aunque sea uno solo, por accidente) puede terminar apareciendo en resultados de Google como una entrada "sin descripción disponible" (el propio `RobotsController` de este proyecto ya documenta exactamente este razonamiento en su comentario — es el mismo patrón, aplicado ahora al robots.txt del dominio anfitrión).
2. **Razón adicional, específica de este caso**: `robots.txt` **solo se lee desde la raíz del host** (`https://www.limaviewtours.com/robots.txt`), nunca desde un subdirectorio — así que el `robots.txt` que manda aquí es el de **Lima View Tours**, un proyecto ajeno a este repo. El propio `robots.txt` dinámico que genera ESTE proyecto (`RobotsController`, ver `2.1`) quedaría desplegado en `https://www.limaviewtours.com/tour-word/robots.txt` — una URL que **ningún crawler va a leer nunca**, porque no es la raíz del host. No hace falta desactivarlo ni tocarlo: es inofensivo por definición del estándar, simplemente irrelevante en esa posición.

Lo único que hace falta del lado de Lima View Tours: confirmar que su `robots.txt` real (el de la raíz) **no** tenga una línea `Disallow: /tour-word/` — y que su `sitemap.xml` real **no** liste ninguna URL bajo `/tour-word/`. Esto es responsabilidad del otro proyecto, fuera de este repo — dejar la verificación como pendiente explícito de quien administre `limaviewtours.com`.

### 6.4 Riesgo de canibalización / confusión con el dominio anfitrión

- **Canonical**: cada página de Pacha Viva bajo `/tour-word/` autogenera su canonical con `url()->current()` — que usa el HOST real de la request. Desplegado en `limaviewtours.com/tour-word/`, el canonical de cada página será `https://www.limaviewtours.com/tour-word/es/...`, **correcto y autorreferencial por construcción** (no hay URL cableada a mano que pueda apuntar a otro sitio) — sin necesidad de tocar código para esto. Confirmado por lectura de `components/layout.blade.php:91` (`$canonicalUrl = $canonical ?? url()->current();`).
- **Enlaces salientes**: todo el enlazado interno de Pacha Viva usa `route()` (relativo al host+prefijo actual, ver `2.7`) — ninguna URL absoluta cableada a otro dominio. **Sin riesgo de fuga de enlaces** hacia fuera de `/tour-word/` ni hacia el dominio real de Lima View.
- **`sitemap.xml` propio**: ya neutralizado en cuanto se aplique `6.2` (sigue dando 404 mientras `is_staging_mirror` esté activo) — no hace falta ningún paso adicional.
- **Riesgo real que SÍ queda abierto**: si el despliegue publica el `.htaccess`/código de `6.1`/`6.2` en un paso posterior al que hace público el subdirectorio (por ejemplo, DNS/enlace ya compartido antes de que el noindex esté activo), hay una ventana — aunque sea de horas — en la que Google puede rastrear `/tour-word/` sin ninguna de las dos capas. Recomendación operativa: los dos cambios (`.htaccess` + flag) deben ir en el **mismo despliegue**, y verificarse con `curl -I` (cabecera `X-Robots-Tag` presente) **antes** de compartir el enlace con nadie fuera del equipo — mismo criterio que ya usa el proyecto para "humo post-deploy" (`00-contexto.md`, regla 10).
- **Riesgo de marca, no de indexación** (para que quede dicho, aunque no es mi bloque): el `og:image`/`og:title` de Pacha Viva se van a compartir desde una URL que vive en el dominio de Lima View Tours durante la demo — alguien que reciba el link por WhatsApp puede confundir a qué empresa pertenece. No bloqueante, pero vale que el cliente lo sepa antes de repartir el link.

---

## 7. hreflang / canonical y el segmento de ruta EN (punto abierto del CRO)

**Dictamen: NO rompe el hreflang recíproco ni el canonical — es una oportunidad de palabra clave perdida en la URL, no un defecto técnico.**

Verificado leyendo `routes/web.php` (rutas `/destinos/{slug}` y `/experiencias/{slug}` literales, sin traducción de segmento por locale) y `components/layout.blade.php` (el hreflang de cada alternate se genera con `route($currentRouteName, [...mismos parámetros, locale => X])` — el MISMO nombre de ruta para los dos idiomas, así que el segmento `/destinos`/`/experiencias` es idéntico en ES y EN por construcción, nunca diverge entre los dos). Cada alternate:

- Es una URL real, 200, indexable (cuando el flag lo permita) — no una URL inventada.
- Es recíproca: el ES apunta al EN de la MISMA ruta+parámetros, y el EN apunta de vuelta al ES de la misma forma (confirmado por lectura del `@foreach($hreflangAlternates as ...)`, que recorre `config('cms.active_locales')` completo incluyendo el locale actual — la propia página se autorreferencia dentro del bloque).
- El canonical de cada versión sigue siendo autorreferencial (`url()->current()`), sin cruzarse entre idiomas.

Lo que SÍ es cierto: **"tours" no está traducido a propósito** — es una palabra que coincide en ES y EN, así que **da la falsa impresión** de que el esquema sí traduce segmentos de ruta cuando en realidad ningún segmento se traduce en ningún caso (ni siquiera "tours" — coincide por casualidad de idioma, no por lógica de código). `/en/destinos/cusco` y `/en/experiencias/trekking` son, en ese sentido, más honestos sobre cómo funciona el sistema que `/en/tours/inca-trail-machu-picchu-4-days` (ahí sí traduce el **slug**, que es una columna JSON traducible por diseño — eso es un mecanismo distinto y correcto, no lo confundan con traducción de segmento de ruta).

**Recomendación** (no bloqueante, prioridad baja-media, valor SEO real pero menor: la keyword en el path pesa poco frente a title/H1/contenido, que ya están bien traducidos): si se quiere URL 100% en inglés para el mercado EN, cambiar el segmento fijo por uno resuelto por locale (`/en/destinations/{slug}`, `/en/experiences/{slug}`), con 301 desde la forma vieja si el sitio ya estuviera indexado (hoy no lo está, así que el costo de cambiarlo ahora es cero — es el mejor momento para decidirlo, antes de la primera indexación real). Asignar a `backend-laravel` si Anyerson decide que vale la pena; **Medio**, no bloqueante.

---

## 8. On-page por URL (Tabla B completada — parte que faltaba del contrato)

| URL | title (largo) | meta description (largo) | canonical | OG completo | schema |
|---|---|---|---|---|---|
| `/es/` | "Agencia de turismo en Cusco, Perú · Pacha Viva" (47) | "Agencia de turismo en Cusco, Perú. Diseñamos experiencias auténticas e inolvidables en los destinos más increíbles del país, guiadas por expertos locales." (156) | `http://127.0.0.1:8087/es` — **no autorreferencial para `/es/`** (ver S-02) | sí (type/site_name/locale/title/description/url/image+dimensiones+type, Twitter card) | ninguno (sin Organization/WebSite, ver S-03) |
| `/es/tours/camino-inca-machu-picchu-4-dias` | "Camino Inca a Machu Picchu, 4 días \| Pacha Viva" (49) | "Trekking de cuatro días por el Camino Inca original hasta Machu Picchu, con guía oficial, porteadores y campamento. Sale desde Cusco." (137) | autorreferencial, correcto | sí, completo | `BreadcrumbList` únicamente (sin `TouristTrip`/`Offer`, ver S-03) |
| `/en/tours/inca-trail-machu-picchu-4-days` | "Inca Trail to Machu Picchu, 4 Days \| Pacha Viva" (49) | no releída en esta pasada (ver `NO COMPROBADO`) | no releído en esta pasada | no releído | no releído |
| `/es/contacto` | "Contacto · Pacha Viva" (22) | "Pacha Viva es una agencia de turismo en Cusco, Perú. Diseñamos tours y experiencias auténticas por los destinos del país con expertos locales." (145) | autorreferencial, correcto | sí | `FAQPage` con 5 preguntas reales |

Todos los `title`/`description` medidos están dentro de rangos razonables (títulos 22-49 caracteres, descriptions 137-156) — ninguno truncado ni vacío, ninguno duplicado entre las URLs medidas.

---

## 9. Contenido (Bloque 8) — límites reales, sin inventar recomendaciones de relleno

- Los 3 tours, 3 destinos y 3 experiencias tienen fotos reales cargadas hoy (confirmado: `tour-camino-inca-400.webp`, `tour-gastronomia-arequipa-400.webp`, `tour-valle-sagrado-400.webp` en el network capture de Home) — el placeholder SVG (`PlaceholderImage::svg`) es el respaldo defensivo, no el estado actual visible. El límite real de posicionamiento hoy **no es falta de fotos**, es el tamaño del catálogo: 3+3+3 fichas no compiten en volumen de contenido con una agencia establecida — esto es esperado en una fase de lanzamiento, no un defecto de código.
- `nosotros.blade.php` (equipo): confirmado por el CRO en Ronda 3 que la sección se oculta entera cuando no hay miembros cargados (sin hueco roto). Esto significa que la página "Nosotros" hoy **no aporta señales E-E-A-T** (experiencia/autoridad del equipo) que Google valora especialmente en turismo (compras de alto valor, YMYL-adyacente) — es una limitación de contenido real del cliente, no de código. Cuando se cargue el equipo, cada miembro es candidato a su propio `Person` en JSON-LD (fuera de alcance de este pase).
- H1/jerarquía: confirmado por el CRO (Ronda 2) sin saltos de nivel en las 3 pantallas del pase, 1 solo H1 por página en las 9 fichas — no repito esa medición.

---

## Tabla A — Lighthouse por URL

**No se pudo correr Lighthouse en este entorno** (intento con `npx lighthouse` sin confirmación de instalación en el tiempo disponible — no fuerzo una descarga que puede colgar la sesión). Sustituyo con medición directa de red (Playwright + `stat` de archivos, ver `## 1`), que es más concreta para el hallazgo que importaba (peso real transferido), pero **no reemplaza** las métricas de laboratorio de Lighthouse (Performance/A11y/Best Practices/SEO como puntaje 0-100, INP). Marcado explícitamente como `NO COMPROBADO`, no estimado.

Tampoco hay datos de campo (CrUX/Search Console): el sitio no está publicado, así que **no puede existir** dato de campo todavía — esto no es una omisión, es un hecho del estado del proyecto.

| URL | Perf | A11y | Best Practices | SEO | LCP | CLS | INP |
|---|---|---|---|---|---|---|---|
| /es/ | no medido (Lighthouse no disponible) | — | — | — | no medido en ms — mecanismo y peso confirmados, ver `## 1` | no medido | no medido |
| /es/tours | no medido | — | — | — | no medido | no medido | no medido |
| /es/tours/camino-inca-machu-picchu-4-dias | no medido | — | — | — | no medido | no medido | no medido |

## Tabla C — Los 7 gates de discoverability (completada)

| # | Gate | Estado | Evidencia |
|---|---|---|---|
| 1 | `robots.txt` existe y no bloquea rutas productivas | ✅ | `curl /robots.txt`, ver `2.1` |
| 2 | `sitemap.xml` existe, responde 200 y lista solo URLs canónicas 200 | ⚠️ 404 hoy por diseño (bandera demo); cuando esté activo, **le faltará el catálogo entero** (S-01) | `curl /sitemap.xml` + lectura de `SitemapController::ROUTE_NAMES` |
| 3 | Toda URL indexable tiene canonical apuntando a sí misma | ⚠️ Parcial: la etiqueta es consistente pero **la URL con barra final no es autorreferencial de la que realmente se sirvió** (S-02) | `curl` cuerpo de `/es/` vs `/es` |
| 4 | Ninguna URL productiva trae `noindex` en meta ni en header | N/A hoy (todo el sitio está en `noindex` intencional por la bandera de demo, correcto para esta fase) | `curl` de 4 URLs, todas con la meta |
| 5 | Cero 4xx y cero cadenas de redirección >1 salto en el enlazado interno | ✅ (dentro del inventario recorrido) | Mapa de `## 3`, todos los enlaces internos resuelven directo |
| 6 | `title` y `H1` presentes, únicos y distintos entre sí en cada URL | ✅ (confirmado por CRO en las 9 fichas + por mí en Home/tour/contacto) | Ver `## 8` y contrato CRO |
| 7 | Datos estructurados presentes y sin errores de validación | ⚠️ Parcial: lo que existe (`BreadcrumbList`, `FAQPage`) es JSON válido y correcto; falta `Organization`/`TouristTrip` (S-03) | `curl` + inspección del JSON emitido |

---

## Hallazgos priorizados

| # | Severidad | Bloque | Alcance | Hallazgo | Remedio | Agente | Esfuerzo |
|---|---|---|---|---|---|---|---|
| S-01 | Alto | 1 (Indexación) | `SitemapController::ROUTE_NAMES` | Sitemap no incluye catálogo (tours/destinos/experiencias, 12 URLs) | Extender `ROUTE_NAMES` + loop sobre modelos publicados por locale con traducción real | `backend-laravel` | Medio |
| S-02 | Alto | 1 (Indexación) | Todo el sitio (`{locale}` group) | `/es` y `/es/` (y equivalentes en todas las rutas) ambas 200, sin 301 que consolide; canonical no autorreferencial en la forma con barra | 301 server-side de la forma con barra a la forma sin barra + corregir `Route::redirect('/', '/es/', 301)` a `/es` | `backend-laravel` | Bajo |
| S-03 | Alto | 4 (Datos estructurados) | Layout + `tours/show.blade.php` | Sin `Organization`/`WebSite` en ningún lado; sin `TouristTrip`/`Offer` en fichas de tour | Nuevo componente JSON-LD siguiendo el patrón de `x-seo.breadcrumb-jsonld`; precio desde `Money`, moneda PEN | `backend-laravel` | Medio |
| — | Alto (rendimiento, ver `## 1`) | 5 (CWV) | Hero de Home (`home.blade.php` + `app.js` + `app.css`) | Las 3 diapositivas del slider descargan siempre pese a `loading="lazy"`/`fetchpriority="low"` — hasta 478 KB de fotos invisibles a 1920w | Diferir `src` de diapositivas 2/3 vía JS (no confiar en atributos nativos sobre un elemento con `inset:0` en viewport) | `maquetador-frontend` | Bajo-medio |
| — | Bloqueante para publicación | Encargo (staging) | Despliegue en `/tour-word/` | Sin flag dedicado, el noindex de staging depende de `catalog_demo_content`, que tiene otro propósito y se apagará algún día | Ver receta completa `## 6` (flag `is_staging_mirror` + `X-Robots-Tag` en `.htaccess`) | `backend-laravel` (código) + quien despliega (`.htaccess`) | Bajo |
| — | Medio | 6 (bilingüe) | Rutas `/destinos`, `/experiencias` en EN | Segmento de ruta no traducido (observación ya anotada por el CRO); no rompe hreflang/canonical, es oportunidad de keyword | Cambiar a `/en/destinations/`, `/en/experiences/` con 301 si se decide (costo mínimo ahora, antes de indexar) | `backend-laravel`, decide Anyerson | Medio |
| — | Medio | 4 (Datos estructurados) | `Offer.priceCurrency` cuando se implemente S-03 | Riesgo futuro si se declara USD (tipo de cambio editable a mano) en vez de PEN | Declarar PEN como moneda del `Offer` | `backend-laravel` (junto con S-03) | Bajo |
| — | Medio | 1 (Indexación) | `/tours?destino=`/`?experiencia=`/`?page=` | Cada combinación de filtro se autocanonicaliza en vez de apuntar a `/tours` | Canonical fijo a la URL base sin query string cuando el catálogo crezca | `backend-laravel` | Bajo |
| — | Bajo | 8 (Contenido) | `/es/nosotros` | Equipo vacío hoy → sin señal E-E-A-T; se oculta bien (sin defecto visual), pero limita posicionamiento | Ninguno de código — depende de que el cliente cargue el equipo | cliente | N/A |

## Matriz Impacto × Esfuerzo

| | Esfuerzo bajo | Esfuerzo medio |
|---|---|---|
| **Impacto alto** | S-02 (barra final) · Hero slider diferido · Receta noindex staging (flag) | S-01 (sitemap) · S-03 (JSON-LD Organization+TouristTrip) |
| **Impacto medio** | Canonical de filtros | Segmento de ruta EN destinos/experiencias |
| **Impacto bajo** | — | Equipo/Nosotros (no es código) |

**Orden recomendado de ataque**: 1) receta de noindex de staging (bloquea publicación) 2) S-02 barra final (afecta el 100% de las URLs, arreglo barato) 3) hero slider (impacto de negocio real en LCP, arreglo barato) 4) S-01 sitemap (antes de apagar `catalog_demo_content`) 5) S-03 datos estructurados (mejora, no bloqueante) 6) el resto, sin prisa.

---

## Conflictos con CRO

**Ninguno detectado.** El único punto donde el CRO y yo tocamos la misma superficie es el segmento de ruta EN de destinos/experiencias — el CRO lo dejó anotado como observación para mí (`## 7` arriba), no como defecto propio, y mi dictamen (no rompe nada técnico, es oportunidad de keyword) no contradice ninguna recomendación de conversión del CRO. No hay tensión entre "más texto para SEO" y "menos texto para conversión" en nada de lo medido en este pase.

---

## NO COMPROBADO (explícito, para que quien retome no repita)

1. **Lighthouse (Perf/A11y/Best Practices/SEO, LCP en ms, CLS, INP)** — no se pudo instalar/ejecutar `npx lighthouse` en el tiempo disponible sin riesgo de colgar la sesión. El mecanismo y el peso del defecto del hero SÍ están medidos por otra vía (`## 1`), pero no hay un número de Lighthouse.
2. **`/en/tours/inca-trail-machu-picchu-4-days` completo**: solo se releyó `title` (ya lo tenía el CRO); meta description, canonical, OG y schema de esa URL específica no se releyeron en esta pasada — por patrón de código son iguales al ES equivalente (mismo componente, mismas rutas de datos), pero no está confirmado con curl línea por línea.
3. **Las 6 fichas de destino/experiencia restantes** (solo se releyó 1 destino a fondo) — no se repitió title/description/canonical/OG/schema en las 5 restantes; por patrón de código (mismo controller, mismo blade) debería ser consistente, pero no está confirmado una por una.
4. **Texto literal de cada anchor "ver más"/CTA** en `lang/es/site.php` — no se abrió el archivo de idioma para confirmar que ningún string es un "aquí" genérico a secas; se infirió del contexto visual (encabezado de sección acompañando el CTA).
5. **`tests/Feature/StructuredDataTest.php`** — no se ejecutó; el comentario del propio componente dice que cubre el escapado seguro del JSON-LD, no se verificó que siga pasando.
6. **SEO local (Bloque 7)**: NAP, Google Business Profile — no verificable hoy porque los `Setting` de dirección/RUC/RNAVT están vacíos (pendientes del cliente, ya documentado en `00-contexto.md` como bloqueante de producción, no defecto de este pase). No hay LocalBusiness que auditar todavía sin datos reales que inventar.
7. **Brecha competitiva (Bloque 9)**: sin herramienta de análisis de competencia disponible en este entorno — no invento competidores ni gaps de contenido sin evidencia verificable.
8. **Duplicación de host real (www/no-www, http/https)**: no aplicable en local (un solo host `127.0.0.1:8087`); pendiente para cuando exista el dominio real de Pacha Viva.
9. **`npx lighthouse` podría haber quedado descargando en segundo plano** tras mi intento con timeout — si alguien retoma esta auditoría, verificar `npm cache`/procesos colgados antes de reintentar.

---

## Veredicto

**No bloquea el pase visual ya aprobado por el CRO** (los hallazgos aquí son de infraestructura SEO, no de la maquetación cinematográfica en sí). **Sí bloquea la publicación en `/tour-word/`** hasta que se aplique la receta de `## 6` (flag `is_staging_mirror` + `X-Robots-Tag`) — sin eso, dado el antecedente de julio en el mismo dominio, el riesgo es real y ya documentado por el propio equipo. El resto (S-01, S-02, S-03, hero slider) son mejoras de fondo a resolver antes de la indexación real (cuando se apague `catalog_demo_content`), no antes de esta demo en particular.
