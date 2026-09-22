# 10 · Lote 2 — apagar testimonios + los 3 listados (tour-grid Roavio)

**Agente:** `maquetador-frontend` · **Fecha:** 2026-09-21 · **Rama:** `lote-1-sistema-diseno`
**Estado del árbol:** sin commit (mismo criterio que los lotes 0 y 1 — decide Anyerson).
**Alcance:** apagado de la sección de testimonios en `home.blade.php` (orden explícita
del cliente) + rejilla y cabecera de `tours/index.blade.php`,
`destinations/index.blade.php` y `experiences/index.blade.php`. **No se tocó** el resto
de la home, fichas, Nosotros ni Contacto.

---

## 1. Apagar testimonios (orden explícita de Anyerson)

"Deja el testimonio apagado" — la sección se había reactivado en el lote 1 con 6
testimonios DEMO inventados. Se apaga de nuevo, con el mismo criterio de la decisión
original del proyecto (B2, antes del lote 1): sin modelo de reseñas reales no se publica
ninguna cara/nombre inventado.

- **Reversible y evidente, no borrado**: `$testimonialsEnabled = false;` en el `@php` de
  `home.blade.php`, con un comentario que explica por qué está apagada y los 3 pasos
  exactos para encenderla (cambiar el flag, reemplazar `$testimonials` por datos reales
  — idealmente un modelo `Review`, no seguir quemando el lang file — y borrar el
  comentario "DEMO"). La sección completa (markup, `x-ui.testimonial-card
  variant="tile"`, el carrusel) sigue intacta más abajo, envuelta en
  `@if($testimonialsEnabled) ... @endif` — el trabajo de maquetación del lote 1 no se
  pierde.
- **No se tocó ningún `config/`**: el flag vive en la vista, como exige el encargo (cero
  backend).
- **Verificado en el navegador, no asumido**: `document.getElementById('testimonios')`
  → `null` en la home renderizada. Gap real entre "Actividades" (la sección anterior) y
  "Aliados" (la siguiente), medido con `getBoundingClientRect()`:

  | Viewport | Gap Actividades→Aliados |
  |---|---|
  | 1440 | **0px** (tocan exacto, sin hueco ni doble margen) |
  | 375 | **0px** |

  Cada sección sigue poniendo su propio padding-block (`pb-2` en Actividades,
  `py-10 sm:py-12` en Aliados) — al quitar Testimonios del medio no queda ningún hueco
  de espaciado ni un padding duplicado.
- El marquee de aliados/sellos sigue encendido, sin tocar (no tiene contenido falso).
- Suite completa corrida después del cambio: **343/343 OK** (ver §6).

## 2. Los 3 listados — layout `tour-grid` Roavio

**Diagnóstico de partida**: `tours/index.blade.php` era la pantalla más débil del
embudo — rejilla de tarjetas ya existente, pero con el `variant="card"` (radio grande +
sombra) del sistema anterior en vez del lenguaje Roavio, y **un ancho de contenedor
compartido entre cabecera y rejilla** (ambas en `.shell`, 1280) — justo lo que la regla
del encargo prohíbe ("no uses el mismo ancho en toda la pantalla"). `destinations/index`
y `experiences/index` no tenían foto de cabecera (pendiente explícito de la clienta) y
usaban tarjetas con sombra/radio grande.

### 2.1 `x-ui.page-header`: ancho de contenedor aditivo

Único cambio a un componente compartido de verdad: prop nuevo `containerClass`
(default `'shell'` — **exacto** el único comportamiento que tenía el componente hasta
hoy). `x-ui.page-header` no tiene otros consumidores fuera de estos 3 índices
(verificado por grep antes de tocarlo), así que no hay riesgo de regresión en ningún
otro lugar del sitio.

### 2.2 Tarjetas: reutilizar, no inventar una cuarta

- **Tours**: `x-ui.tour-card` con el **prop aditivo `variant="flat"` que ya creó el
  lote 1** (radio 10px, cero `box-shadow`) — literalmente lo que pide el encargo. El
  `variant="card"` de siempre queda intacto en las fichas de tour (único otro
  consumidor).
- **Destinos**: en vez de inventar una tarjeta nueva, **`x-ui.destination-card` gana el
  mismo patrón `variant` aditivo** (default `'card'`, sin tocar — sigue siendo el que
  usan la Home ["Destinos imperdibles"] y `/_styleguide`). `variant="flat"` quita el
  `rounded-card shadow-e1/e3` y deja `rounded-tile` sin sombra, mismo criterio que
  `tour-card`.
- **Experiencias**: en vez de una tercera variante de tarjeta con foto+scrim (que se
  vería casi igual a la de Destinos y dejaría las 3 pantallas "planas" entre sí — la
  queja original del cliente), esta rejilla **reutiliza `x-ui.mosaic-tile`**, el mismo
  componente que ya usa "Actividades" en la Home para esta misma colección
  (`$experiences`, mismo dato, cero query nueva) — tile ancho `aspect-video` con
  pastilla de ícono, en vez de inventar una cuarta tarjeta. `x-ui.experience-card`
  queda sin tocar, vivo en `/_styleguide`.

### 2.3 Cabecera con foto en Destinos y Experiencias (pendiente de la clienta)

Las 3 fotos de `public/images/site/` ya las agotan Home y `tours/index` (cinematic,
`hero-cordillera-rio.jpg`). En vez de repetir una de esas, cada cabecera nueva usa una
foto de la **galería real** del destino/experiencia (ya en BD — los controllers ya
cargan `->with('gallery')` — y sin ninguna migración ni controller nuevo: la vista solo
recorre la colección que ya llega) que **no aparece en ningún otro lugar del sitio
todavía**:

| Pantalla | Foto | Fuente | Alt (BD, traducible) |
|---|---|---|---|
| Destinos | `destinations/destino-cusco-galeria-03.jpg` | 3ra foto de galería de Cusco | "Plaza de Armas del Cusco iluminada de noche, con gente paseando" |
| Experiencias | `experiences/experiencia-trekking-vinicunca.jpg` | 1ra (única) foto de galería de Trekking | "Caminantes ascendiendo la Montaña de Siete Colores (Vinicunca)" |

Si el catálogo llegara sin fotos de galería, la vista cae a `coverImageUrl()`; sin
ninguna de las dos, `$headerImage` queda `null` y `x-ui.page-header` entero cae a su
variante sin foto (banda cálida) — nunca una imagen de relleno inventada. Nunca se
hardcodeó la ruta del archivo: se resolvió con el accessor `->src` del modelo
`DestinationImage`/`ExperienceImage` (`Storage::disk('public')->url()`).

**`object-position` mirado en el navegador, no "center" a ciegas** (regla dura del
encargo): a la relación de aspecto de estas bandas (~5,5:1 a 1440), el default del
componente (`center 45%`) recortaba lo menos interesante de las dos fotos (cielo
nublado + torres de catedral en silueta; cielo plano sobre Vinicunca). Se ajustó
mirando cada foto:

| Foto | Antes (default) | Ahora | Por qué |
|---|---|---|---|
| Cusco (Destinos) | `center 45%` | **`center 62%`** | Baja el encuadre hasta la fachada iluminada, el empedrado con reflejos y la gente caminando — el contenido con más lectura de la foto. |
| Vinicunca (Experiencias) | `center 45%` | **`center 55%`** | Baja el encuadre hasta la cresta de franjas de color y el arranque del sendero con caminantes. |

## 3. Anchos — alternancia verificada (medido, `getBoundingClientRect`, no asumido)

Regla del encargo: los 3 anchos Roavio son 1290/1392/1432, ninguna pantalla repite el
mismo ancho en cabecera y rejilla, y entre las 3 pantallas se usan los 3 tokens.

| Pantalla | Cabecera | Rejilla |
|---|---|---|
| Tours | `.shell` (**1280**, sin tocar — es la firma `cinematic` del pase anterior) | `.shell-boxed` (**1392**) |
| Destinos | `.shell-bleed` (**1432**) | `.shell-narrow` (**1290**) |
| Experiencias | `.shell-boxed` (**1392**) | `.shell-bleed` (**1432**) |

Los 3 tokens Roavio (1290/1392/1432) quedan en uso; ninguna pantalla comparte ancho
entre su cabecera y su rejilla.

## 4. Alturas y geometría de tarjeta (medido a 1440×900 y 375×812)

| | Tours | Destinos | Experiencias |
|---|---|---|---|
| Alto cabecera @1440 | **630px** (cinematic) | **264px** | **264px** |
| Alto cabecera @375 | 471px | 220px | 220px |
| Ancho contenedor rejilla @1440 | 1392 | 1290 | 1432 |
| Tarjetas @1440 (3, todo el catálogo de hoy) | **432×544** (0,79, informativa: foto+precio+CTA) | **395×494** (0,80, retrato con scrim) | **467×262** (1,78 = 16:9, tile ancho + ícono) |
| Tarjetas @375 (1 columna) | 343×472 | 343×429 | 351×197 |

Rango de alto de tarjeta @1440: **262–544px (2,08×)** entre los 3 tratamientos —
informativa (tours) vs retrato con scrim (destinos) vs tile ancho horizontal
(experiencias): 3 lenguajes de tarjeta distintos, no la misma caja con otro color.
Cabeceras: **264–630px (2,39×)** entre la cinematic de tours y las bandas estándar de
destinos/experiencias.

## 5. Defecto encontrado y corregido en el propio pase: columna vacía en Tours

Primer intento: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` (columnas
fijas, buscando el "4 por fila" de Roavio a `xl`). Medido con `getBoundingClientRect()`
a 1440: **318px de espacio en blanco a la derecha** — con solo 3 tours publicados (todo
el catálogo de hoy) y una rejilla de 4 columnas *fijas*, la 4ta columna queda vacía en
vez de colapsar. Se corrigió al mismo patrón `auto-fit` que ya usan Destinos/
Actividades/Tours-destacados en la Home (`[grid-template-columns:repeat(auto-fit,
minmax(min(100%,18rem),1fr))]`): con 3 tarjetas, la columna sin contenido colapsa a 0 y
las 3 reales reparten el ancho completo (432px cada una, **sin hueco** — reverificado);
con 4+ tours simplemente agrega columnas hasta el tope. Documentado por si en algún
lote futuro el catálogo crece a un número no múltiplo de 4: **auto-fit no rellena una
última fila parcial** (limitación conocida de CSS Grid, ya aceptada en el resto del
proyecto en las rejillas equivalentes de la Home) — no es un defecto nuevo de este
lote, es el mismo trade-off que ya corre en producción en Destinos/Actividades.

## 6. Gate de regresión

- **Suite completa: 343/343 OK, 1507 assertions** — corrida dos veces (antes de tocar
  el grid de tours y después de corregir el defecto de la columna vacía), con
  `G:\laragon\bin\php\php8.2.1\php.exe`. Mismo número que el baseline de los lotes 0/1.
- `php artisan view:cache` corrido antes de cada `npm run build` (3 veces en esta
  sesión) — Tailwind lee vistas compiladas en este proyecto; el primer intento del
  grid `auto-fit` se probó SIN rebuild y la clase arbitraria no estaba en el CSS
  compilado todavía (las tarjetas se veían apiladas en una sola columna) — vuelto a
  compilar y reverificado antes de dar el fix por bueno.
- Sin overflow horizontal: `document.documentElement.scrollWidth ===
  window.innerWidth` verificado en **4 pantallas × 5 anchos (375/640/768/1024/1440) =
  20 combinaciones, las 20 en OK** (home, tours, destinos, experiencias).
- Consola sin errores ni warnings en `/es/tours`, `/es/destinos`, `/es/experiencias`,
  `/en`, `/en/destinos` (MCP `browser_console_messages`, 0 mensajes en las 5).
- `HomeCatalogLinksTest`/`LocaleSwitcherLinksTest`: dentro de los 343 — pasan; todas las
  tarjetas de los 3 listados usan `<a href>` reales vía `route()`, ninguna con
  `onclick`/JS-only.
- No se corrió `security-engineer` — cambio puramente visual (Blade/CSS), sin tocar
  backend/auth/datos/archivos.

## 7. Lo que no se hizo / quedó pendiente

- No se decidió el "modo nocturno" — fuera de alcance de este lote por instrucción
  explícita, ningún color nuevo se cableó (todo sale de `tokens.css`, `--brand-h: 155`).
- No se generó ningún modelo ni migración de reseñas — el flag de testimonios sigue
  apagado hasta que la clienta entregue reseñas reales (ver §1, los 3 pasos para
  encenderla).
- `tours/index.blade.php`: el filtro (destino/experiencia) y la paginación no se
  tocaron — solo el contenedor y las tarjetas.
- No se desplegó nada — todo vive en el working tree local, sin commit.

---

## Archivos tocados

- `resources/views/home.blade.php` — flag `$testimonialsEnabled = false`, sección
  envuelta en `@if`.
- `resources/views/components/ui/page-header.blade.php` — prop aditivo
  `containerClass` (default `'shell'`).
- `resources/views/components/ui/destination-card.blade.php` — prop aditivo `variant`
  (default `'card'`).
- `resources/views/tours/index.blade.php` — rejilla a `.shell-boxed`, `variant="flat"`,
  grid `auto-fit` (reemplaza el `grid-cols-4` fijo que dejaba una columna vacía).
- `resources/views/destinations/index.blade.php` — cabecera con foto de galería real
  (`.shell-bleed`), rejilla `.shell-narrow` con `variant="flat"`.
- `resources/views/experiences/index.blade.php` — cabecera con foto de galería real
  (`.shell-boxed`), rejilla `.shell-bleed` reutilizando `x-ui.mosaic-tile`.

## Capturas (scratchpad de la sesión, no se commitean)

- `lote2-tours-1440.png`, `lote2-tours-375.png`, `lote2-tours-cards-1440.png`
- `lote2-destinos-1440.png`, `lote2-destinos-375.png`
- `lote2-experiencias-1440.png`, `lote2-experiencias-375.png`
- `lote2-home-testimonios-gap-1440.png` — Actividades→Aliados sin Testimonios, sin hueco
