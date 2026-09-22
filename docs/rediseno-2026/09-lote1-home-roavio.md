# 09 · Lote 1 — home Roavio: tours, about, actividades, testimonios, marquee + cierre del hero

**Agente:** `maquetador-frontend` · **Fecha:** 2026-09-21 · **Rama:** `lote-1-sistema-diseno`
**Estado del árbol:** sin commit (mismo criterio que el lote 0 — decide Anyerson).
**Alcance:** `resources/views/home.blade.php`, de la sección que sigue al mosaico hacia
abajo (tours, about, actividades, testimonios, marquee), más el cierre del hero pedido
a mitad de sesión. **No se tocó** `listados`, `fichas`, `Nosotros` ni `Contacto`.

---

## 0. Cierre del hero (decisión de Anyerson, a mitad de sesión)

Anyerson decidió **V2 · pantalla completa** y pidió tres cosas puntuales:

1. **Fijar V2, borrar la rama V1.** Hecho: `$heroVariant` y los dos `@class([...])`
   condicionales desaparecieron. El `<section id="hero">` ahora lleva una sola clase fija
   (`min-h-[calc(100svh-4rem)]`), sin ternarios. V1 queda en el historial de git.
2. **Verificar que el defecto de V1 a 375 no pasó a V2.** Verificado con captura real a
   375×812 en las 3 diapositivas: subtítulo completo (3 líneas, sin cortar), los dos CTA
   (`Explorar tours` / `Ver destinos`) dentro de la caja, franja de confianza visible y
   deslizable debajo. **No hay rastro del defecto** — capturas: `pv-hero-v2-375-slide1.png`,
   `...slide2.png`, `...slide3.png`.
3. **`object-position` específico para 375**, no el de escritorio reutilizado.

### El hallazgo técnico detrás del punto 3

A 375 el hero mide **836px de alto** (medido, `getBoundingClientRect`), lo que da un marco
de **0,449:1** (ancho:alto) — mucho más angosto que el AR nativo de las 3 fotos (todas
&gt;1,4:1, o sea apaisadas). Con `object-fit: cover`, cuando el marco es proporcionalmente
**más angosto** que la foto, el recorte es matemáticamente forzoso: se muestra el **alto
completo** de la foto siempre, y se recorta por los **lados**. Se verificó con
`getComputedStyle` en las 3 fotos: a 375 el `object-position` renderizado nunca cambia en Y
aunque la Y declarada varíe — **el eje que manda a este ancho es X, no Y** (a diferencia de
escritorio, donde el marco es más ancho que las fotos y manda Y). Es la razón real por la
que "reusar el valor de escritorio" no tenía sentido: a 375 la mitad de ese valor (la Y) es
inerte.

Se resolvió con un prop aditivo nuevo en `x-ui.picture` (`positionSm`, ver §5) y se
recalculó **X mirando cada foto en el navegador a 375** (nunca a ciegas):

| Foto | `object-position` escritorio (sin tocar) | `object-position` móvil (nuevo) | Por qué |
|---|---|---|---|
| Machu Picchu amanecer | `center 42%` | **`52%`** | Con solo ~30% del ancho nativo visible, 52% deja el pico de Huayna Picchu completo + el arranque de las terrazas/ruinas a su derecha (verificado: la lectura es "montaña + ruinas", no "montaña genérica" o "solo roca"). |
| Valle Sagrado panorámica | `center 60%` | **`75%`** | Foto muy panorámica (2,39:1 nativo): a 375 solo queda visible ~19% del ancho. Centrado (50%) caía en una ladera genérica; 75% mete el pueblo + los campos cultivados + el río, que es el contenido con más lectura de la foto. |
| Cordillera-río | `center 46%` | **`center`** (50%) | El conjunto de picos nevados y el río que converge ya caen centrados; no hacía falta mover el eje. |

Verificado con `getComputedStyle(img).objectPosition`: `52% 50%` a 375 vs `50% 42%` a 1440
para la diapositiva 1 (Machu Picchu) — confirma que el mecanismo responsive funciona (no es
solo el valor de escritorio "que por casualidad se ve bien" a 375).

### Cómo se implementó (sin tocar los ~20 otros llamadores de `x-ui.picture`)

Tailwind no puede ver un valor de `object-position` interpolado en runtime (viene de PHP),
así que una utilidad `[object-position:...]` con el valor dinámico como clase arbitraria
NO se generaría en el build. Se resolvió con **custom properties + una regla CSS fija**
(no una utilidad Tailwind):

- `x-ui.picture` gana un prop aditivo `positionSm` (default `null` — sin él, CERO cambio de
  comportamiento en los ~20 llamadores existentes: mosaico, tarjetas, destinos...). Con
  `positionSm`, el `<img>` recibe `style="--pos-sm: 52%; --pos-lg: center 42%;"` y la clase
  `.obj-pos-responsive`.
- `app.css`: `.obj-pos-responsive { object-position: var(--pos-sm); }` con
  `@media (min-width: 640px) { object-position: var(--pos-lg); }` — regla CSS literal, el
  escáner de Tailwind no necesita verla porque no es una utilidad generada.

Archivo nuevo/tocado: `resources/views/components/ui/picture.blade.php`,
`resources/css/app.css`.

---

## 1. Grid de tours (antes carrusel de "Tours destacados")

Roavio: rejilla estática de 8 tarjetas `col-xl-3` (358px), no un carrusel. El carrusel de
Pacha Viva existía porque el catálogo de hoy es chico (3 tours) — con `auto-fit` (mismo
truco que ya usaban "Destinos"/"Experiencias") una sola rejilla resuelve las dos cosas:
con pocas tarjetas colapsa columnas vacías y reparte el ancho completo del contenedor; con
8+ simplemente agrega filas. Se retiró `x-ui.carousel-shell` de esta sección (sigue viva y
sin tocar para Testimonios, más abajo, y para donde ya la usaba Nosotros).

- **Contenedor:** `.shell-boxed` (1392px) — primera sección boxed después del mosaico a
  sangre (regla del encargo), y contraste fuerte contra "Destinos" (`.shell`, 1280) que
  sigue justo debajo, sin tocar.
- **Tarjetas:** `x-ui.tour-card` con un **prop aditivo nuevo** `variant="flat"` (radio 10px,
  CERO `box-shadow`). El `variant="card"` de siempre (radio grande + sombra) queda intacto
  y es el que se sigue usando en `tours/index.blade.php` y las fichas — verificado por grep
  antes de tocar el componente: son los únicos otros consumidores.

**Medido @1440:** contenedor 1392px, 3 tarjetas (todo el catálogo de hoy), sin huecos.
**Medido @375:** 1 columna, sin recorte de texto, sin scroll horizontal.

## 2. Bloque "about" (antes "¿Por qué elegir viajar con nosotros?")

Columnas **5/7** (`lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]`, con `minmax(0,...)` para no
repetir el desborde de un `1fr` sin tope — lección de un lote anterior). Medido con
`getBoundingClientRect`: **533px / 747px = ratio 0,714 = 5/7 exacto**.

- **Contenedor:** `.shell-boxed` (1392) — mismo ancho que "Tours" (no adyacentes, sin
  problema) y contraste contra "Destinos" (1280) justo arriba.
- **Radio/sombra:** esta sección ya existía (pase cinematográfico anterior, con
  `rounded-panel shadow-e3` en la foto y la tarjeta flotante) — se ajustó a `rounded-tile`
  y **CERO box-shadow** en toda la sección para cumplir la regla dura del encargo. El
  parallax, la holgura del marco (26%) y la velocidad (0.26) **no se tocaron**.
- `x-ui.feature-card` (único consumidor: esta sección, verificado por grep) pasó de
  `shadow-e1/hover:shadow-e2` a `rounded-tile` sin sombra, hover por color de borde.

## 3. Actividades (antes "Experiencias únicas")

Mismo dato de siempre (`$experiences`, sin query nueva) — cambia la presentación: de
tarjeta con borde/sombra (`x-ui.experience-card`, que sigue viva sin tocar en
`/experiencias`) a **tile plano a sangre completa** reutilizando `x-ui.mosaic-tile` (el
mismo componente del mosaico bajo el hero — "Actividades" se lee a propósito como un eco
del mosaico). `mosaic-tile` ganó un prop aditivo `icon` (pastilla circular arriba-izquierda,
sin sombra) para no perder los íconos de trekking/gastronomía/cultura que ya existían.

- **Contenedor:** `.shell-bleed` (1432, a sangre) — contraste fuerte contra el "about"
  boxed de arriba.
- **Tiles:** `aspect-video` (16:9), no `aspect-[3/4]` como el mosaico — a propósito, para
  que la sección sea BAJA y rompa la monotonía de alturas frente a "Tours"/"About".
- **Medido:** radio 10px, `box-shadow: none`, ratio 1,778 (=16/9 exacto) @1440.

## 4. Testimonios (nuevos — la sección estaba apagada)

Decisión anterior del proyecto (B2): esta sección **no se renderizaba** — sin modelo de
reseñas y con 3 testimonios de mockup que eran caras de IA con nombres inventados. El
encargo de este lote **anula esa decisión explícitamente** y pide contenido estático
quemado en la vista, marcado como demo. Se implementó así, pero **sin repetir el problema
original**: nombre + inicial de apellido (nunca una identidad completa inventada) y
**CERO foto de banco/IA** — el avatar es la inicial del nombre sobre un cuadrado de color
(`x-ui.testimonial-card`, prop aditivo nuevo `variant="tile"`).

- **Avatar:** medido **100×101px, `border-radius: 0`** — el spec exacto del encargo
  ("cuadrados 100×101"). El primer intento lo tenía invertido (101×100); corregido y
  reverificado con `getBoundingClientRect`.
- **Carrusel:** Roavio usa un Swiper de 6; acá, `x-ui.carousel-shell` (el mismo componente
  real — scroll-snap, flechas, puntos con `aria-current` — que ya usaba tours). Cero
  librería nueva.
- **Contenedor:** `.shell-narrow` (1290px) — el tercer ancho Roavio del lote 0, declarado
  desde entonces sin consumidor. Lo estrena esta sección.
- Cada tarjeta lleva `variant="tile"` (radio 10px, sin sombra) — el `variant="default"`
  (el que enseña `/_styleguide`, `rounded-2xl`/`shadow-sm`/avatar circular) queda intacto.
- Contenido marcado `DEMO: pendiente modelo de reseñas` en `home.blade.php` y en
  `lang/{es,en}/site.php`.

## 5. Marquee de aliados y sellos oficiales (nuevo)

**Sin ningún archivo real en el proyecto** — se buscó en `public/images/` antes de
maquetar: no hay logo de ningún aliado ni el sello RNAVT/MINCETUR. Regla dura del encargo:
"si el sello no está, deja el hueco maquetado, nunca un placeholder con apariencia de sello
oficial". Se construyó el mecanismo completo (bucle CSS puro, sin Swiper/JS — `.marquee-
track` duplica el array y se traslada -50%, pausa con `prefers-reduced-motion` y también al
hover/foco) con **wordmarks de texto neutro y borde punteado** para los 8 tiles (6 "Aliado
0N" + RNAVT + MINCETUR) — nada que se pueda confundir con un logo o sello real.
`aria-hidden="true"` en el track completo: es contenido decorativo y duplicado, sin eso un
lector de pantalla lo anunciaría dos veces.

- **Contenedor:** `.shell-bleed` (1432) — la fila de logos pide ancho completo.
- El pendiente queda anotado en el código (comentarios) y en este informe — **no** como
  copy visible en la página (mismo criterio que el resto del sitio: nunca lenguaje de obra
  de cara al visitante).

## 6. Lo que NO se tocó (fuera de alcance, deliberado)

- **"Destinos imperdibles"** — no está en la lista de 5 secciones del encargo. Se dejó
  exactamente como estaba (`.shell`, 1280, tarjeta con scrim). Sirve además como uno de los
  anchos "wide-margin" que hacen contrastar a Tours/About alrededor.
- **Newsletter** — tampoco está en la lista; se dejó igual (banda a sangre con foto +
  parallax, ya existente).
- **Sección de noticias de Roavio** — explícitamente fuera (Pacha Viva no tiene blog).

---

## 7. Anchos verificados (medido, no asumido)

`getBoundingClientRect()` sobre cada `<section>` y su `.shell*` interno, a 1440×900:

| Sección | Contenedor | Ancho medido @1440 |
|---|---|---|
| Hero | (bleed, sin tocar) | 1440 (full) |
| Mosaico | `.shell-bleed` (sin tocar) | 1432 |
| **Tours destacados** | `.shell-boxed` | **1392** |
| Destinos (sin tocar) | `.shell` | 1280 |
| **About** | `.shell-boxed` | **1392** |
| **Actividades** | `.shell-bleed` | **1432** |
| **Testimonios** | `.shell-narrow` | **1290** |
| **Aliados (marquee)** | `.shell-bleed` | **1432** |
| Newsletter (sin tocar) | `.shell` | 1280 |

Alternancia real entre pares adyacentes controlados por este lote: Tours(1392)→
Destinos(1280, fijo)→About(1392)→Actividades(1432)→Testimonios(1290)→Aliados(1432)→
Newsletter(1280, fijo). El único par adyacente que repite ancho "puro" es
Aliados→Newsletter (ambos a sangre) — inevitable porque Newsletter está fuera de alcance;
se mitiga con una diferencia de alto fuerte (192px vs 272px @1440) y de fondo (blanco vs
verde oscuro con foto).

## 8. Alturas verificadas (medido, `getBoundingClientRect`, sin scroll)

| Sección | Alto @1440 | Alto @375 |
|---|---|---|
| Hero | 836 | 836 |
| Mosaico | 714 | 749 |
| **Tours destacados** | **848** | 1733 |
| Destinos | 794 | 1596 |
| **About** | **643** | 1277 |
| **Actividades** | **511** | 868 |
| **Testimonios** | **622** | 552 |
| **Aliados (marquee)** | **192** | 176 |
| Newsletter | 272 | 291 |

**Rango @1440: 192–848px → ratio 4,42×** (supera el 4× de Roavio, que iba 310–1226 =
3,95×). **Rango @375: 176–1733px → ratio 9,8×** (Tours sale alto a 375 porque son 3
tarjetas apiladas en 1 columna — consecuencia esperada de un grid de tarjetas en móvil, no
un defecto).

## 9. Sin scroll horizontal (verificado, no asumido)

`document.documentElement.scrollWidth === window.innerWidth` en **5 anchos**: 375, 640,
768, 1024, 1440 — **igual en los 5** (sin overflow-x en ninguno).

## 10. Fondo computado de cada sección nueva (verificado, no asumido)

| Sección | `background-color` computado |
|---|---|
| Tours destacados | `rgb(255,255,255)` — bg-surface |
| About | `rgb(255,255,255)` — bg-surface |
| Actividades | `rgb(255,255,255)` — bg-surface |
| Testimonios | `rgb(247,243,236)` — bg-sand |
| Aliados | `rgb(255,255,255)` — bg-surface |

Sin blanco-sobre-blanco de texto en ninguna (todas con texto `--ink`/`--text-2` sobre
fondo claro, o blanco sobre las franjas oscuras de hero/newsletter, sin tocar). Varias
secciones nuevas comparten `bg-surface` blanco de forma consecutiva (Tours→About→
Actividades→Aliados, con Destinos/Testimonios en `sand` entre medio) — aceptado a
propósito: la variedad la dan el ancho del contenedor y la geometría del contenido
(tarjetas vs tiles a sangre vs foto+texto), no un cuarto color de fondo nuevo fuera de los
tokens ya existentes.

## 11. Componentes tocados — todos con prop aditivo o único consumidor (nada roto)

| Componente | Cambio | Otros consumidores (verificado por grep) |
|---|---|---|
| `x-ui.picture` | prop `positionSm` (default `null`) | ~20 llamadores — sin el prop, cero cambio |
| `x-ui.tour-card` | prop `variant` (default `'card'`) | `tours/index.blade.php`, fichas — usan el default, sin tocar |
| `x-ui.testimonial-card` | prop `variant` (default `'default'`) | `/_styleguide` — usa el default, sin tocar |
| `x-ui.mosaic-tile` | prop `icon` (default `null`) | el mosaico de home — no lo usa, sin cambio |
| `x-ui.feature-card` | radio/sombra ajustados directo (sin variant) | único consumidor es esta misma home (verificado) |

## 12. Gate de regresión

- **Suite completa: 343/343 OK, 1507 assertions** — corrida DOS VECES (antes y después del
  cierre del hero), con `G:\laragon\bin\php\php8.2.1\php.exe`. Mismo número que el baseline
  del lote 0 (no se tocó ningún test).
- `php artisan view:cache` corrido antes de cada `npm run build` (tres veces en esta
  sesión).
- `HomeCatalogLinksTest`/`LocaleSwitcherLinksTest`: dentro de los 343 — pasan; se verificó
  además a mano que ningún `href="#"` quedó en las 5 secciones nuevas (todas las tarjetas
  usan `route(...)` real).
- Sin errores/warnings de consola en home (ES y EN), verificado en el navegador.
- ES y EN verificados (`/es`, `/en`) — la sección de Actividades usa la clave `activities`
  nueva en los dos idiomas, con paridad de estructura.

## 13. Contenido estático / pendiente de modelo

- **Testimonios** (`lang/{es,en}/site.php#home.testimonials.items`, 6 items): copy de
  marketing plausible, marcado `DEMO: pendiente modelo de reseñas`. Nombre + inicial +
  ciudad de origen; avatar = inicial sobre cuadrado de color (nunca una foto).
- **Aliados y sellos** (`$partners` en `home.blade.php`): 6 wordmarks genéricos "Aliado 0N"
  + RNAVT + MINCETUR, todos con borde punteado y `title` indicando que están pendientes de
  archivo real. Sin logo ni sello inventado.

## 14. Lo que no se hizo / quedó pendiente

- No se generó ningún modelo/migración para reseñas ni partners (prohibido por el encargo).
- No hay archivo real de RNAVT/MINCETUR en el proyecto — el hueco queda maquetado, el
  archivo real es un pendiente de Anyerson/cliente.
- No se tocó Destinos ni Newsletter (fuera de la lista de 5 secciones).
- No se corrió `security-engineer` — cambio puramente visual (Blade/CSS/lang), sin backend.
- No se desplegó nada — todo vive en el working tree local, sin commit.

---

## Archivos tocados

- `resources/views/home.blade.php` — hero (V2 fija, sin V1), tours grid, about 5/7,
  actividades, testimonios, marquee, ids nuevos (`about`, `destinos`, `aliados`) para QA.
- `resources/views/components/ui/picture.blade.php` — prop `positionSm`.
- `resources/views/components/ui/tour-card.blade.php` — prop `variant`.
- `resources/views/components/ui/testimonial-card.blade.php` — prop `variant`,
  `avatarInitial`, `avatarTone`.
- `resources/views/components/ui/mosaic-tile.blade.php` — prop `icon`.
- `resources/views/components/ui/feature-card.blade.php` — radio/sombra ajustados.
- `resources/css/app.css` — `.shell-narrow`, `.obj-pos-responsive`, `.marquee-track`/
  `.marquee-viewport` + keyframe `pv-marquee`.
- `lang/es/site.php`, `lang/en/site.php` — claves `activities` (renombrada de
  `experiences`), `testimonials`, `partners`.

## Capturas (scratchpad de la sesión, no se commitean)

- `pv-hero-v2-375-slide1.png` / `...slide2.png` / `...slide3.png` — hero V2 a 375, las 3
  diapositivas, verificación del cierre del hero.
- `pv-02-tours-1440.png` / `pv-02-tours-375.png`
- `pv-03-about-1440.png` / `pv-03-about-375.png`
- `pv-04-actividades-1440.png` / `pv-04-actividades-375.png`
- `pv-05-aliados-1440.png` / `pv-05-testimonios-375.png`
