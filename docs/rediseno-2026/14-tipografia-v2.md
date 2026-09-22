# 14 · Tipografía v2 — reemplazo de Inter Tight + Fraunces

**Fecha:** 2026-09-22
**Rama:** `lote-1-sistema-diseno`, sobre `dab09f7`
**Encargo:** Anyerson, sobre la maqueta ya vista: *"me parece muy IA, necesitamos
hacerla mas profesional y natural como si un experto en ui ux lo fuera
realizado"*. Es el **tercer rechazo visual** de este proyecto (ver
`08-lote0-roavio.md` y `13-pase-staging-roavio.md`).

## 0. Diagnóstico de partida

La hipótesis del encargo se sostuvo: el problema no era solo el serif, era
la **pareja**. Fraunces + Inter (Tight) es, hoy, la combinación por defecto
de facto en sitios generados/maquetados con asistencia de IA — Fraunces es
el serif "wonky" de moda en identidades de startup reciente, Inter es la
sans de interfaz por defecto de toda la industria (Tailwind UI, shadcn,
Linear, Vercel...). Verlas juntas dispara reconocimiento de patrón aunque
cada una, aislada, sea una fuente seria y bien dibujada.

Se construyeron **tres parejas alternativas, cada una implementada de
verdad** (código real, recompilado, servido, medido en navegador — nada de
mockup) sobre el mismo árbol, en secuencia. Las tres cargan y aplican
correctamente; la elección final es de dirección de arte, no un descarte
técnico.

## 1. Mecánica (no se tocó, según instrucción del proyecto)

- Fuentes **self-hosted** en `public/fonts/<slug>/`, nunca
  `fonts.googleapis.com`.
- `@font-face` inline en `<style>` dentro de `resources/views/components/layout.blade.php`,
  `src` construido con `asset()` (fix de subdirectorio,
  `11-fix-fuentes-subdirectorio.md` — necesario para que resuelva bajo
  `limaviewtours.com/tour-word/`).
- `@theme` en `resources/css/app.css` mapea `--font-sans` / `--font-display`.
- Tuning de titular (line-height / letter-spacing / peso) vive en
  `resources/css/tokens.css` (`--lh-display`, `--lh-title`, `--ls-display`)
  y, cuando hacía falta tocar el peso del H1 del hero, en la clase del
  `<h1>` de `resources/views/home.blade.php`.

## 2. Las tres parejas

### Opción 1 — Editorial de alto contraste (ELEGIDA, el árbol queda aquí)

**Bodoni Moda** (display, titulares) + **Public Sans** (sans, cuerpo/UI).

- Licencia: **SIL Open Font License, Version 1.1** las dos.
  - Bodoni Moda — Copyright 2020 The Bodoni Moda Project Authors
    (github.com/indestructible-type/Bodoni).
  - Public Sans — Copyright 2015 The Public Sans Project Authors
    (github.com/uswds/public-sans) — es la tipografía del US Web Design
    System, diseñada específicamente para UI/lectura en pantalla.
- Peso en disco: `BodoniModa-Variable.woff2` 45.2 KB + `PublicSans-Variable.woff2`
  26.2 KB = **71.4 KB total** (variable, subset latin — cubre acentos/ñ).
  Comparado con el par anterior (Fraunces 65.7 KB + Inter Tight 43.8 KB =
  109.6 KB, medido en el commit `dab09f7`): **38 KB menos**, no es un
  intercambio de peso por estética.
- Ajuste de métrica (`tokens.css`): `--ls-display` de `-0.02em` a `0em` —
  los trazos finos de un Didone se cierran si se aprietan con tracking
  negativo, el valor de Fraunces dejaba los remates casi tocándose a tamaño
  hero. `--lh-display` de `1.08` a `1.14` — los ascendentes/descendentes de
  Bodoni Moda son más largos que los de Fraunces; a 1.08 el diacrítico de
  una mayúscula acentuada rozaba la línea de arriba en el H1 de dos líneas
  del hero. Peso del H1: se mantuvo `font-semibold` (600) — a diferencia de
  un grotesco, un Didone ya lee "pesado" por el contraste de trazo, subir a
  700 lo hacía sentir "de cartel" en vez de editorial.
- Capturas (`scratchpad/opcion1-*.png`, equivalentes a `scratchpad/final-*.png`
  tras dejar el árbol acá):
  - `opcion1-home-1440.png` — hero a 1440.
  - `opcion1-home-375.png` — hero a 375.
  - `opcion1-about-1440.png` — "¿Por qué elegir viajar con nosotros?": título
    + 4 tarjetas con texto corrido real (contenido de `lang/es/site.php`, no
    depende del catálogo demo).
  - `opcion1-tours-1440.png` / `opcion1-tours-375.png` — "Tours destacados":
    el catálogo demo de esta base QA vino con 0 filas (ver §4), así que solo
    prueba eyebrow + `section-title`, no las tarjetas.

### Opción 2 — Serif humanista cálido en los dos roles (descartada, reconstruible)

**Lora** sola, para `--font-sans` y `--font-display` a la vez.

- Licencia: **SIL OFL 1.1** — Copyright 2011 The Lora Project Authors
  (github.com/cyrealtype/Lora-Cyrillic).
- Peso: `Lora-Variable.woff2` **39.8 KB** — un solo archivo para los dos
  roles, la opción **más liviana de descarga** de las tres (un preload
  menos que hoy).
- Ajuste de métrica: `--ls-display` de `-0.02em` a `-0.008em` (Lora es
  calligráfica de origen, más tracking negativo apretaba comillas/acentos
  contra la letra siguiente). `--lh-display` de `1.08` a `1.16` (x-height
  más alta y contraformas más abiertas que Fraunces; a 1.08 el H1 de dos
  líneas se leía apretado). Peso sin cambio (`font-semibold`, dentro del
  rango variable 400-700 de Lora).
- Capturas: `opcion2-home-1440.png`, `opcion2-home-375.png`,
  `opcion2-about-1440.png`, `opcion2-tours-{1440,375}.png`.
- Nota de lectura: a regular weight, Lora tiene un flujo calligráfico
  (ligero avance hacia adelante en los trazos) que en la captura puede
  leerse como itálica — **se verificó con `getComputedStyle().fontStyle`
  que es `normal`**, es el dibujo propio de la fuente, no un bug de CSS.

### Opción 3 — Grotesca sola, jerarquía por peso y tracking (descartada, reconstruible)

**Archivo** sola, para `--font-sans` y `--font-display` a la vez.

- Licencia: **SIL OFL 1.1** — Copyright 2020 The Archivo Project Authors
  (github.com/Omnibus-Type/Archivo).
- Peso: `Archivo-Variable.woff2` **34.1 KB** — un solo archivo, la más
  liviana de las tres.
- Ajuste de métrica: `--ls-display` de `-0.02em` a `-0.032em` (una grotesca
  sin serifas tolera más tracking negativo a tamaño display que un serif —
  el valor de Fraunces se quedaba corto para que un H1 en peso 900 leyera
  "trabajado" en vez de "Arial grande"). `--lh-display` de `1.08` a `1.02`
  (las mayúsculas de una grotesca son más compactas verticalmente). **Peso
  del H1 del hero: de `font-semibold` a `font-black` (900)** — es la pieza
  central de esta dirección, la jerarquía "display" la da el peso extremo,
  no un cambio de familia. `--lh-title` también bajó de 1.18 a 1.15.
- Capturas: `opcion3-home-1440.png`, `opcion3-home-375.png`,
  `opcion3-about-1440.png`, `opcion3-tours-{1440,375}.png`.

### Cómo reconstruir la 2 o la 3

Los archivos `.woff2` ya NO están en el árbol (se retiraron para no dejar
tres parejas cableadas — regla del encargo). Para reconstruir cualquiera:

1. Descargar el subset "latin" (cubre acentos/ñ, `U+0000-00FF`) de la
   variante variable desde Google Fonts:
   - Lora: `https://fonts.gstatic.com/s/lora/v37/0QIhMX1D_JOuMw_LIftL.woff2`
   - Archivo: `https://fonts.gstatic.com/s/archivo/v25/k3kPo8UDI-1M0wlSV9XAw6lQkqWY8Q82sLydOxI.woff2`
   (URLs versionadas de Google Fonts — si Google reindexa la versión, volver
   a resolver vía `https://fonts.googleapis.com/css2?family=<Nombre>` con
   un User-Agent de navegador moderno y tomar el bloque `unicode-range`
   `U+0000-00FF`).
2. Colocar en `public/fonts/<slug>/<Nombre>-Variable.woff2`.
3. En `layout.blade.php`: un solo `<link rel="preload">` y un solo
   `@font-face` (mismo `font-family` para las dos variables de tema).
4. En `app.css` `@theme`: `--font-sans` y `--font-display` a la misma
   familia, con fallback coherente (serif para Lora, sans para Archivo).
5. En `tokens.css`: los valores de `--lh-display`/`--ls-display`/`--lh-title`
   documentados arriba para cada opción.
6. Solo para la Opción 3: cambiar la clase del `<h1>` del hero en
   `home.blade.php` de `font-semibold` a `font-black`.
7. `php artisan view:clear && npm run build`.

## 3. Verificación (no solo declarada)

Con el MCP de Playwright asignado a este agente no hay `browser_evaluate`
ni forma de hacer scroll real (solo `navigate/snapshot/screenshot/resize/
click/console_messages`) — insuficiente para probar carga de fuente o
disparar los `data-reveal` por `IntersectionObserver` bajo el pliegue. Se
usó un `playwright-core` ya cacheado por un `npx` anterior
(`AppData/Local/npm-cache/_npx/9833c18b2d85bc59/node_modules/playwright-core`)
lanzando el Chrome del sistema por `executablePath` — proceso aparte del
navegador del MCP, sin pisarlo.

Para cada una de las tres opciones (script `scratchpad/font-check.js`):

- `document.fonts` con `FontFace.load()` real → `status: "loaded"` para las
  dos familias, en las tres opciones.
- Petición de red del `.woff2` real → **200** en las tres (confirmado por
  `page.on('response')`, no solo por no ver un error en consola).
- **Control negativo**: una `FontFace` apuntando a una URL que no existe →
  `rejected` con error real (prueba que el check "loaded" no es vacuamente
  cierto — ver nota siguiente).
- **Ancho renderizado real vs. genérico**: se clonó el H1 del hero en una
  sola línea (`white-space: nowrap`, sin ancho fijo) y se midió `scrollWidth`
  con la fuente real, y forzada a `serif`/`sans-serif` genérico.
  - *Primer intento, descartado*: medir con el contenedor a su ancho de caja
    (672px) y `scrollWidth` daba **672 en los tres casos** — un "check que
    no puede fallar": con ancho fijo el texto envuelve igual sea cual sea la
    fuente, así que la igualdad no prueba nada. Corregido midiendo en una
    sola línea.
  - Con la medición corregida, los tres pares dieron anchos **distintos
    entre fuente real / serif genérico / sans genérico** (prueba de que el
    parser de aplicación de fuente es sensible, no un artefacto):
    - Opción 1 (Bodoni Moda): real 1275px · serif genérico 1150px · sans
      genérico 1301px.
    - Opción 2 (Lora): real 1192px · serif genérico 1128px · sans genérico
      1279px.
    - Opción 3 (Archivo): real 1338px · serif genérico 1064px · sans
      genérico 1414px.
  - En los tres casos el ancho real no coincide con ninguno de los dos
    genéricos → la fuente declarada está aplicando de verdad, no cayendo a
    fallback.

### Dos falsos defectos descartados con medición (no con la vista)

- **Fringing de color en el texto** (visible en algunas capturas de Opción
  2, letras con tinte verde/naranja alternado dentro de una misma palabra):
  se midió `getComputedStyle(nodo).color` y dio un único valor sólido
  (`rgb(20, 32, 26)`) para todo el nodo — el color CSS es uniforme. Es un
  artefacto de antialiasing subpíxel del Chrome headless usado para la
  captura (geometría LCD sin pantalla física real detrás), no un defecto de
  la maqueta. No se reporta como pending diff.
- **Subtítulo del hero con aspecto itálico** en Opción 2 a 375px: se midió
  `getComputedStyle(nodo).fontStyle` → `"normal"`. Es el dibujo calligráfico
  propio de Lora en peso regular, no una itálica accidental.

## 4. Nota de entorno — catálogo demo vacío en esta base QA

La base SQLite de QA (`scratchpad/pachaviva-qa.sqlite`) tenía el esquema
migrado pero **0 filas** en `tours`/`destinations`/`experiences`/`settings`
— contradice la nota de que "ya está sembrada". `DemoTourSeeder` es
idempotente y aditivo por diseño (no hay riesgo de duplicar), pero
`php artisan db:seed` fue bloqueado por el clasificador de auto-mode
("Irreversible Local Destruction") en este entorno — no se intentó rodear
el bloqueo. Como consecuencia, la sección "Tours destacados" renderiza su
`empty-state` real (código legítimo, no roto) en vez de tarjetas de tour.
Se sustituyó, para la captura de "tarjetas + texto corrido", la sección
**"¿Por qué elegir viajar con nosotros?"** (`#about`): mismo tratamiento
tipográfico (`x-ui.feature-card`, título `font-display text-h3` + párrafo
`font-sans`), con contenido real de `lang/es/site.php` que no depende del
catálogo. Pendiente para quien retome: sembrar esa base o pedir una nueva
antes de un QA de catálogo real.

## 5. Recomendación

**Opción 1 — Bodoni Moda + Public Sans.**

Argumento contra la queja concreta ("muy IA", "poco profesional"), no en
abstracto:

- Un Didone de contraste extremo emparejado con una grotesca neutral de UI
  es la pareja clásica de revistas y marcas de viaje premium (piénsese
  Condé Nast Traveler, marcas de hotelería boutique) — es una decisión
  tipográfica que casi ningún generador automático toma, porque un Didone
  con trazos finos es más difícil de hacer respirar en responsive (por eso
  el ajuste de tracking/line-height de arriba no era opcional). Elegirlo
  señala **oficio**, que es exactamente lo que "como si un experto en UI/UX
  lo hubiera hecho" pide.
- A diferencia de la Opción 2, cambia de familia entre titular y cuerpo
  (mismo principio estructural que ya tenía el sitio: contraste display/UI),
  así que el resto del sistema (pesos, tamaños, componentes) no necesita
  reaprenderse — pero el contraste ya no es "serif wonky + sans de
  interfaz genérica", es "serif editorial clásico + sans de UI con
  identidad propia" (Public Sans nace para gobierno/UI, no para
  "parecer una interfaz de SaaS").
- A diferencia de la Opción 3, conserva la calidez y la "gravedad cultural"
  que pide el brief para turismo en Perú — una grotesca sola, por bien
  trabajada que esté, tira hacia registro de agencia/tech antes que de
  viaje/patrimonio.
- Es, además, la más liviana en KB de las tres si se cuenta que YA se
  precargaban dos familias antes (71.4 KB vs. 109.6 KB del par anterior);
  las Opciones 2 y 3 son más livianas todavía (un solo archivo) pero eso no
  pesó más que el argumento de dirección de arte.

## 6. Archivos tocados (estado final, Opción 1)

- `resources/views/components/layout.blade.php` — preloads + `@font-face`
  (Public Sans + Bodoni Moda).
- `resources/css/app.css` — `--font-sans`/`--font-display`, comentarios de
  contexto (3 bloques), sin cambios de estructura fuera de tipografía.
- `resources/css/tokens.css` — `--lh-display`, `--ls-display` (titular).
- `resources/views/styleguide.blade.php` — etiquetas de la guía de estilos
  corregidas a los nombres reales (venían desactualizadas incluso antes de
  este lote: decían "Figtree", que ya no se usaba tampoco).
- `public/fonts/bodoni-moda/BodoniModa-Variable.woff2` (nuevo, 45.2 KB).
- `public/fonts/public-sans/PublicSans-Variable.woff2` (nuevo, 26.2 KB).
- `public/fonts/fraunces/`, `public/fonts/inter-tight/` — eliminados (ya no
  hay ningún `@font-face` que los referencie).
- `public/fonts/lora/`, `public/fonts/archivo/` — usados durante la prueba
  en código real de las Opciones 2/3 y retirados al cerrar en la Opción 1
  (ver §2 "Cómo reconstruir" para volver a bajarlos).

No se tocó `resources/views/home.blade.php` en el estado final (el cambio
de peso del H1 fue solo para probar la Opción 3 y se revirtió a
`font-semibold`).
